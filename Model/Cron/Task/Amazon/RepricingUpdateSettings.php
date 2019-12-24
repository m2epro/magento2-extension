<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingUpdateSettings
 */
class RepricingUpdateSettings extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/repricing_update_settings';
    const MAX_MEMORY_LIMIT = 512;

    const MAX_COUNT_OF_ITERATIONS     = 10;
    const MAX_ITEMS_COUNT_PER_REQUEST = 500;

    //####################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

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
    private function getPermittedAccounts()
    {
        $accountCollection = $this->activeRecordFactory->getObject('Account')->getCollection();
        $accountCollection->getSelect()->joinInner(
            [
                'aar' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_amazon_account_repricing')
            ],
            'aar.account_id=main_table.id',
            []
        );

        return $accountCollection->getItems();
    }

    private function processAccount(\Ess\M2ePro\Model\Account $acc)
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
            $this->getLockItem()->activate();

            /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing $resource */
            $resource = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')->getResource();
            $resource->resetProcessRequired(array_unique(array_keys($products)));

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
    private function getProcessRequiredProducts(\Ess\M2ePro\Model\Account $account)
    {
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        )->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
            'l.id=main_table.listing_id',
            []
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter(
            'status',
            ['in' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN
            ]]
        );

        $listingProductCollection->getSelect()->joinInner(
            [
                'alpr' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_amazon_listing_product_repricing')
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

        $listingProductRepricingCollection = $this->activeRecordFactory->getObject(
            'Amazon_Listing_Product_Repricing'
        )->getCollection();
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
