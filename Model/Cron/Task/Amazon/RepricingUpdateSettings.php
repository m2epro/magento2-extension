<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon;

class RepricingUpdateSettings extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/repricing_update_settings';
    const MAX_MEMORY_LIMIT = 512;

    const MAX_ITEMS_COUNT_PER_REQUEST = 100;

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
            array('aar' => $this->resource->getTableName('m2epro_amazon_account_repricing')),
            'aar.account_id=main_table.id', array()
        );

        return $accountCollection->getItems();
    }

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Updating $repricingUpdating */
        $repricingUpdating = $this->modelFactory->getObject('Amazon\Repricing\Updating');
        $repricingUpdating->setAccount($account);

        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General $repricingSynchronization */
        $repricingSynchronization = $this->modelFactory->getObject('Amazon\Repricing\Synchronization\General');
        $repricingSynchronization->setAccount($account);

        while ($listingsProductsRepricing = $this->getProcessRequiredListingsProductsRepricing($account)) {
            $updatedSkus = $repricingUpdating->process($listingsProductsRepricing);

            $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
                ->getResource()->resetProcessRequired(array_unique(array_keys($listingsProductsRepricing)));

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
    private function getProcessRequiredListingsProductsRepricing(\Ess\M2ePro\Model\Account $account)
    {
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product'
        )->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            array('l' => $this->resource->getTableName('m2epro_listing')),
            'l.id=main_table.listing_id',
            array()
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter(
            'status',
            array('in' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN
            ))
        );

        $listingProductCollection->getSelect()->joinInner(
            array('alpr' => $this->resource->getTableName('m2epro_amazon_listing_product_repricing')),
            'alpr.listing_product_id=main_table.id',
            array()
        );
        $listingProductCollection->addFieldToFilter('alpr.is_process_required', true);

        $listingProductCollection->getSelect()->limit(self::MAX_ITEMS_COUNT_PER_REQUEST);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $listingProductCollection->getItems();
        if (empty($listingsProducts)) {
            return array();
        }

        $listingProductRepricingCollection = $this->activeRecordFactory->getObject(
            'Amazon\Listing\Product\Repricing'
        )->getCollection();
        $listingProductRepricingCollection->addFieldToFilter(
            'listing_product_id', array('in' => $listingProductCollection->getColumnValues('id'))
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