<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Repricing;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\UpdateSettings
 */
class UpdateSettings extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/repricing/update_settings';

    public const MAX_COUNT_OF_ITERATIONS = 10;
    public const MAX_ITEMS_COUNT_PER_REQUEST = 500;

    /** @var int (in seconds) */
    protected $interval = 180;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    //####################################

    public function performActions()
    {
        $permittedAccounts = $this->accountCollectionFactory
            ->create()
            ->getAccountsWithValidRepricingAccount();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $permittedAccount) {
            $this->processAccount($permittedAccount);
        }
    }

    //####################################

    protected function processAccount(\Ess\M2ePro\Model\Account $acc)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Updating $repricingUpdating */
        $repricingUpdating = $this->modelFactory->getObject('Amazon_Repricing_Updating');
        $repricingUpdating->setAccount($acc);
        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General $repricingSynchronization */
        $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
        $repricingSynchronization->setAccount($acc);

        $iteration = 0;
        while (($products = $this->getProcessRequiredProducts($acc)) && $iteration <= self::MAX_COUNT_OF_ITERATIONS) {
            $iteration++;

            $updatedSkus = $repricingUpdating->process($products);

            $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')->getResource()
                                      ->resetProcessRequired(array_unique(array_keys($products)));

            if (empty($updatedSkus)) {
                continue;
            }

            $repricingSynchronization->run($updatedSkus);
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[]
     */
    protected function getProcessRequiredProducts(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        )->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'l.id=main_table.listing_id',
            []
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());

        $statusListed = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        $statusStopped = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
        $statusUnknown = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
        $listingProductCollection
            ->getSelect()
            ->where(
                "((is_afn_channel = 0 AND status = $statusListed)"
                . " OR (is_afn_channel = 1 AND status IN ($statusListed, $statusStopped, $statusUnknown)))"
            );

        $listingProductCollection->getSelect()->joinInner(
            [
                'alpr' => $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                                                    ->getResource()->getMainTable(),
            ],
            'alpr.listing_product_id=main_table.id',
            []
        );
        $listingProductCollection->addFieldToFilter('alpr.is_process_required', true);

        $listingProductCollection->getSelect()->limit(self::MAX_ITEMS_COUNT_PER_REQUEST);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $listingProductCollection->getItems();
        if (empty($listingsProducts)) {
            return [];
        }

        $listingProductRepricingCollection = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                                                                       ->getCollection();
        $listingProductRepricingCollection->addFieldToFilter(
            'listing_product_id',
            ['in' => $listingProductCollection->getColumnValues('id')]
        );

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $listingsProductsRepricing */
        $listingsProductsRepricing = $listingProductRepricingCollection->getItems();

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            $listingProductRepricing->setListingProduct(
                $listingProductCollection->getItemById($listingProductRepricing->getListingProductId())
            );
        }

        return $listingsProductsRepricing;
    }

    //####################################
}
