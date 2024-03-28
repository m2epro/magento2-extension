<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\KTypes;

use Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor as ChangeProcessor;
use Ess\M2ePro\Model\Ebay\Listing\Product as EbayListingProduct;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product;

class UpdateFromTecDoc extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/listing/product/ktypes/updateFromTecDoc';

    private const MAX_LISTING_PRODUCTS_COUNT_PER_ONE_TIME = 300;
    private const MAX_MPNS_COUNT_PER_ONE_TIME = 100;
    private const MAX_ATTEMPTS = 3;

    /** @var int (in seconds) */
    protected $interval = 120;

    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product */
    private $ebayListingProductResource;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
    private $ebayDispatcher;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $componentEbayConfiguration;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instructionResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $ebayDispatcher,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->componentEbayMotors = $componentEbayMotors;
        $this->ebayDispatcher = $ebayDispatcher;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->componentEbayConfiguration = $componentEbayConfiguration;
        $this->instructionResource = $instructionResource;
    }

    protected function performActions(): void
    {
        if (!$this->componentEbayConfiguration->isKTypeTecDocConfigured()) {
            return;
        }

        try {
            $this->processKTypes();
        } catch (\Throwable $exception) {
            $message = (string)__('The "Update" Action for KTypes TecDoc was completed with error.');

            $this->processTaskAccountException($message, __FILE__, __LINE__);
            $this->processTaskException($exception);
        }
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function processKTypes(): void
    {
        $listingProducts = $this->getListingProducts();

        $affectedListingProducts = [];
        $mpnKTypes = [];
        foreach ($listingProducts as $listingProduct) {
            $productKTypes = $listingProduct->getMagentoProduct()->getAttributeValue(
                $this->componentEbayConfiguration->getKTypesAttribute()
            );
            $productMpn = $listingProduct->getMagentoProduct()->getAttributeValue(
                $this->componentEbayConfiguration->getTecDocKTypesProductMpnAttribute()
            );

            if (empty($productKTypes) && !empty($productMpn)) {
                $affectedListingProducts[] = $listingProduct;
                $mpnKTypes[$productMpn] = $productMpn;

                if (count($mpnKTypes) === self::MAX_MPNS_COUNT_PER_ONE_TIME) {
                    break;
                }

                continue;
            }

            if (!empty($productKTypes)) {
                $listingProduct->getChildObject()
                               ->setResolveKTypeStatus(EbayListingProduct::RESOLVE_KTYPE_STATUS_FINISHED)
                               ->save();

                continue;
            }

            if (empty($productMpn)) {
                $listingProduct->getChildObject()
                               ->setResolveKTypeStatus(EbayListingProduct::RESOLVE_KTYPE_STATUS_IN_PROGRESS)
                               ->setResolveKTypeLastUpdateDate(\Ess\M2ePro\Helper\Date::createCurrentGmt())
                               ->save();
            }
        }

        if (empty($mpnKTypes)) {
            return;
        }

        $receivedKTypes = $this->receiveKTypesFromEbay(array_values($mpnKTypes));

        $this->saveKTypes($affectedListingProducts, $receivedKTypes);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function getListingProducts(): array
    {
        $listingsProductsCollection = $this->listingProductCollectionFactory->create();
        $listingsProductsCollection->joinListingTable();
        $listingsProductsCollection->join(
            ['elp' => $this->ebayListingProductResource->getMainTable()],
            '`elp`.`listing_product_id`=`main_table`.`id`'
        );
        $listingsProductsCollection->addFieldToFilter(
            'l.marketplace_id',
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_IT
        );
        $listingsProductsCollection->addFieldToFilter(
            'main_table.status',
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
        );

        $resolveKTypeStatusUnprocessed = EbayListingProduct::RESOLVE_KTYPE_STATUS_UNPROCESSED;
        $resolveKTypeStatusWithoutResponse = EbayListingProduct::RESOLVE_KTYPE_STATUS_IN_PROGRESS;
        $columnKtypesResolveStatus = Product::COLUMN_KTYPES_RESOLVE_STATUS;
        $columnKtypesResolveLastTryDate = Product::COLUMN_KTYPES_RESOLVE_LAST_TRY_DATE;

        $tempDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $tempDate->modify('-1 day');
        $date = $tempDate->format('Y-m-d H:i:s');

        $listingsProductsCollection->getSelect()->where(
            new \Zend_Db_Expr(
                "(`elp`.`$columnKtypesResolveStatus` = $resolveKTypeStatusUnprocessed) OR " .
                "(`elp`.`$columnKtypesResolveStatus` = $resolveKTypeStatusWithoutResponse AND " .
                "`elp`.`$columnKtypesResolveLastTryDate` < '$date')"
            )
        );

        $listingsProductsCollection->getSelect()->limit(self::MAX_LISTING_PRODUCTS_COUNT_PER_ONE_TIME);

        return $listingsProductsCollection->getItems();
    }

    private function receiveKTypesFromEbay(array $mpns): array
    {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\TecDoc\Get\Ktypes $connectorObj */
        $connectorObj = $this->ebayDispatcher->getConnector(
            'tecDoc',
            'get',
            'ktypes',
            [
                'vat_id' => $this->componentEbayConfiguration->getTecDocVatIdForIT(),
                'mpns' => $mpns,
            ]
        );

        $this->ebayDispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $affectedListingProducts
     * @param array $kTypes
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function saveKTypes(
        array $affectedListingProducts,
        array $kTypes
    ): void {
        foreach ($affectedListingProducts as $listingProduct) {
            $productMpn = $listingProduct->getMagentoProduct()->getAttributeValue(
                $this->componentEbayConfiguration->getTecDocKTypesProductMpnAttribute()
            );

            if (empty($kTypes[$productMpn])) {
                $attempt = $listingProduct->getChildObject()->getResolveKTypeAttempt() + 1;

                $listingProduct->getChildObject()
                               ->setResolveKTypeLastUpdateDate(\Ess\M2ePro\Helper\Date::createCurrentGmt())
                               ->setResolveKTypeAttempt($attempt)
                               ->setResolveKTypeStatus(
                                   $attempt === self::MAX_ATTEMPTS
                                       ? EbayListingProduct::RESOLVE_KTYPE_NOT_RESOLVED
                                       : EbayListingProduct::RESOLVE_KTYPE_STATUS_IN_PROGRESS
                               )->save();

                continue;
            }

            $listingProduct->getChildObject()
                           ->setResolveKTypeStatus(EbayListingProduct::RESOLVE_KTYPE_STATUS_FINISHED)
                           ->save();

            $listingProduct->getMagentoProduct()->setAttributeValue(
                $this->componentEbayConfiguration->getKTypesAttribute(),
                $this->prepareKTypesAttributeValue($kTypes[$productMpn])
            );

            $this->instructionResource->add(
                [
                    [
                        'listing_product_id' => $listingProduct->getId(),
                        'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'type' => ChangeProcessor::INSTRUCTION_TYPE_PARTS_DATA_CHANGED,
                        'initiator' => 'ebay_listing_product_ktypes_update',
                        'priority' => 100,
                    ],
                ]
            );
        }
    }

    private function prepareKTypesAttributeValue($productMpn): string
    {
        $itemsData = [];
        foreach ($productMpn as $id) {
            $itemsData[] = [
                'id' => $id,
            ];
        }

        return $this->componentEbayMotors->buildItemsAttributeValue($itemsData);
    }
}
