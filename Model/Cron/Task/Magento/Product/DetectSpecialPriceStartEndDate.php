<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Magento\Product;

class DetectSpecialPriceStartEndDate extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'magento/product/detect_special_price_start_end_date';

    /** @var int (in seconds) */
    protected $interval = 7200;
    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    protected $publicService;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory  */
    protected $listingProductCollectionFactory;
    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  */
    protected $catalogProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory  */
    protected $listingCollectionFactory;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $catalogProductCollectionFactory,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
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

        $this->publicService = $publicService;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->catalogProductCollectionFactory = $catalogProductCollectionFactory;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->registryManager = $registryManager;
    }

    protected function performActions(): void
    {
        if ($this->getLastProcessedProductId() === null) {
            $this->setLastProcessedProductId(0);
        }

        $changedProductsPrice = $this->getAllChangedProductsPrice();

        if (!$changedProductsPrice) {
            $this->setLastProcessedProductId(0);

            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', ['in' => array_keys($changedProductsPrice)]);
        $collection->addFieldToFilter('status', ['neq' => 0]);

        /** @var  \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $currentPrice = (float)$this->getCurrentPrice($listingProduct);
            $newPrice = (float)$changedProductsPrice[$listingProduct->getProductId()]['price'];

            if ($currentPrice == $newPrice) {
                continue;
            }

            $this->publicService->markPriceChanged($listingProduct->getProductId());
        }

        $this->publicService->applyChanges();

        $lastMagentoProduct = $this->getArrayKeyLast($changedProductsPrice);
        $this->setLastProcessedProductId((int)$lastMagentoProduct);
    }

    private function getAllChangedProductsPrice(): array
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $toDate = clone $currentDate;
        $toDate->modify('-1 day');

        $specialFromDateResults = $this->getChangedProductPricesByDate('special_from_date', $currentDate);
        $specialToDateResults = $this->getChangedProductPricesByDate('special_to_date', $toDate);

        $allChangedProductsPrice = $specialToDateResults + $specialFromDateResults;

        ksort($allChangedProductsPrice);

        return array_slice($allChangedProductsPrice, 0, 1000, true);
    }

    private function getChangedProductPricesByDate(string $attributeCode, \DateTime $date): array
    {
        $changedProductsPrice = [];

        foreach ($this->getAllStoreIds() as $storeId) {
            $productCollection = $this->getProductCollection($attributeCode, $storeId, $date);

            /** @var \Magento\Catalog\Model\Product $magentoProduct */
            foreach ($productCollection->getItems() as $magentoProduct) {
                $magentoProductId = $magentoProduct->getId();
                $price = ($attributeCode === 'special_from_date')
                    ? $magentoProduct->getSpecialPrice()
                    : $magentoProduct->getPrice();

                $changedProductsPrice[$magentoProductId] = [
                    'price' => $price,
                ];
            }
        }

        return $changedProductsPrice;
    }

    private function getProductCollection(
        string $attributeCode,
        string $storeId,
        \DateTime $date
    ): \Magento\Catalog\Model\ResourceModel\Product\Collection {
        $collection = $this->catalogProductCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToFilter('special_price', ['notnull' => true]);
        $collection->addFieldToFilter($attributeCode, ['notnull' => true]);
        $collection->addFieldToFilter($attributeCode, ['lt' => $date->format('Y-m-d H:i:s')]);
        $collection->addFieldToFilter('entity_id', ['gt' => (int)$this->getLastProcessedProductId()]);
        $collection->setOrder('entity_id', 'asc');
        $collection->getSelect()->limit(1000);

        return $collection;
    }

    private function getArrayKeyLast(array $array): ?int
    {
        if (empty($array)) {
            return null;
        }

        $arrayKeys = array_keys($array);

        return $arrayKeys[count($array) - 1];
    }

    private function getCurrentPrice(\Ess\M2ePro\Model\Listing\Product $listingProduct): ?float
    {
        if ($listingProduct->isComponentModeAmazon()) {
            return $listingProduct->getChildObject()->getOnlineRegularPrice();
        } elseif ($listingProduct->isComponentModeEbay()) {
            return $listingProduct->getChildObject()->getOnlineCurrentPrice();
        } elseif ($listingProduct->isComponentModeWalmart()) {
            return $listingProduct->getChildObject()->getOnlinePrice();
        } else {
            throw new \Ess\M2ePro\Model\Exception\Logic('Component Mode is not defined.');
        }
    }

    private function getAllStoreIds(): array
    {
        $storeIds = [];

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $collectionListing */
        $collectionListing = $this->listingCollectionFactory->create();
        $collectionListing->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collectionListing->getSelect()->columns(['store_id' => 'store_id']);
        $collectionListing->getSelect()->group('store_id');

        foreach ($collectionListing->getData() as $item) {
            $storeIds[] = $item['store_id'];
        }

        return $storeIds;
    }

    // ---------------------------------------

    private function getLastProcessedProductId(): ?string
    {
        return $this->registryManager->getValue(
            '/magento/product/detect_special_price_start_end_date/last_magento_product_id/'
        );
    }

    private function setLastProcessedProductId(int $magentoProductId): void
    {
        $this->registryManager->setValue(
            '/magento/product/detect_special_price_start_end_date/last_magento_product_id/',
            $magentoProductId
        );
    }
}
