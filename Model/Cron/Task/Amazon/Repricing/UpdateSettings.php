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
    const NICK = 'amazon/repricing/update_settings';

    /**
     * @var int (in seconds)
     */
    protected $interval = 180;

    const MAX_COUNT_OF_ITERATIONS     = 10;
    const MAX_ITEMS_COUNT_PER_REQUEST = 500;

    //####################################

    public function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $permittedAccount) {
            $this->processAccount($permittedAccount);
        }
    }

    //####################################

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    protected function getPermittedAccounts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();
        $accountCollection->getSelect()->joinInner(
            [
                'aar' => $this->activeRecordFactory->getObject('Amazon_Account_Repricing')
                ->getResource()->getMainTable()
            ],
            'aar.account_id=main_table.id',
            []
        );

        return $accountCollection->getItems();
    }

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
     * @param $account \Ess\M2ePro\Model\Account
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
        $listingProductCollection->addFieldToFilter(
            'status',
            [
                'in' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN
                ]
            ]
        );

        $listingProductCollection->getSelect()->joinInner(
            [
                'alpr' => $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                ->getResource()->getMainTable()
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
