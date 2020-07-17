<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Repricing;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\Synchronize
 */
class Synchronize extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/repricing/synchronize';

    const REGISTRY_GENERAL_START_DATE = '/amazon/repricing/synchronize/general/start_date/';

    const REGISTRY_GENERAL_LAST_LISTING_PRODUCT_ID = '/amazon/repricing/synchronize/general/last_listing_product_id/';
    const REGISTRY_GENERAL_LAST_LISTING_OTHER_ID = '/amazon/repricing/synchronize/general/last_other_product_id/';

    const REGISTRY_ACTUAL_PRICE_START_DATE = '/amazon/repricing/synchronize/actual_price/start_date/';

    const REGISTRY_ACTUAL_PRICE_LAST_LISTING_PRODUCT_ID =
        '/amazon/repricing/synchronize/actual_price/last_listing_product_id/';
    const REGISTRY_ACTUAL_PRICE_LAST_LISTING_OTHER_ID =
        '/amazon/repricing/synchronize/actual_price/last_other_product_id/';

    const SYNCHRONIZE_GENERAL_INTERVAL = 60;
    const SYNCHRONIZE_ACTUAL_PRICE_INTERVAL = 60;

    const PRODUCTS_COUNT_BY_ACCOUNT_AND_PRODUCT_TYPE = 5000;

    //####################################

    public function performActions()
    {
        $accounts = $this->getPermittedAccounts();
        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            if ($this->isPossibleToSynchronizeGeneral($account)) {
                $this->synchronizeGeneral($account);
            }

            if ($this->isPossibleToSynchronizeActualPrice($account)) {
                $this->synchronizeActualPrice($account);
            }
        }
    }

    //####################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     */
    protected function synchronizeGeneral($account)
    {
        // Listing Products
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        )->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'l.id = main_table.listing_id'
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter('second_table.sku', ['notnull' => true]);
        $listingProductCollection->addFieldToFilter('second_table.online_regular_price', ['notnull' => true]);

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            [
                'id'   => 'main_table.id',
                'sku'  => 'second_table.sku'
            ]
        );

        $lastListingProductId = $this->getAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_PRODUCT_ID);
        $listingProductCollection->getSelect()->where('main_table.id > ?', $lastListingProductId);
        $listingProductCollection->getSelect()->limit(self::PRODUCTS_COUNT_BY_ACCOUNT_AND_PRODUCT_TYPE);
        $listingProductCollection->getSelect()->order('id ASC');

        // Listing Others
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other\Collection $listingOtherCollection */
        $listingOtherCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Other'
        )->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $account->getId());

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            [
                'id'   => 'main_table.id',
                'sku'  => 'second_table.sku'
            ]
        );

        $lastListingOtherId = $this->getAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_OTHER_ID);
        $listingOtherCollection->getSelect()->where('main_table.id > ?', $lastListingOtherId);
        $listingOtherCollection->getSelect()->limit(self::PRODUCTS_COUNT_BY_ACCOUNT_AND_PRODUCT_TYPE);
        $listingOtherCollection->getSelect()->order('id ASC');

        $listingProducts = $listingProductCollection->getData();
        $listingOthers = $listingOtherCollection->getData();

        if (empty($listingProducts) && empty($listingOthers)) {
            $this->deleteAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_PRODUCT_ID);
            $this->deleteAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_OTHER_ID);

            return;
        }

        $skus = [];
        foreach ($listingProducts as $listingProduct) {
            $skus[] = $listingProduct['sku'];
        }

        foreach ($listingOthers as $listingOther) {
            $skus[] = $listingOther['sku'];
        }

        /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General */
        $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
        $repricingSynchronization->setAccount($account);
        $result = $repricingSynchronization->run($skus);
        if ($result) {
            if (!empty($listingProducts)) {
                $this->setAccountData(
                    $account,
                    self::REGISTRY_GENERAL_LAST_LISTING_PRODUCT_ID,
                    $listingProducts[count($listingProducts) - 1]['id']
                );
            }

            if (!empty($listingOthers)) {
                $this->setAccountData(
                    $account,
                    self::REGISTRY_GENERAL_LAST_LISTING_OTHER_ID,
                    $listingOthers[count($listingOthers) - 1]['id']
                );
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     */
    protected function synchronizeActualPrice($account)
    {
        // Listing Products
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        )->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'l.id = main_table.listing_id'
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter('second_table.sku', ['notnull' => true]);
        $listingProductCollection->addFieldToFilter('second_table.online_regular_price', ['notnull' => true]);

        $listingProductCollection->getSelect()->joinLeft(
            [
                'alpr' => $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                ->getResource()->getMainTable()
            ],
            'alpr.listing_product_id = main_table.id'
        );
        $listingProductCollection->addFieldToFilter('alpr.is_online_disabled', 0);

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            [
                'id'   => 'main_table.id',
                'sku'  => 'second_table.sku'
            ]
        );

        $lastListingProductId = $this->getAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_PRODUCT_ID);
        $listingProductCollection->getSelect()->where('main_table.id > ?', $lastListingProductId);
        $listingProductCollection->getSelect()->limit(self::PRODUCTS_COUNT_BY_ACCOUNT_AND_PRODUCT_TYPE);
        $listingProductCollection->getSelect()->order('id ASC');

        // Listing Others
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other\Collection $listingOtherCollection */
        $listingOtherCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Other'
        )->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $account->getId());
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);
        $listingOtherCollection->addFieldToFilter('is_repricing_disabled', 0);

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            [
                'id'   => 'main_table.id',
                'sku'  => 'second_table.sku'
            ]
        );

        $lastListingOtherId = $this->getAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_OTHER_ID);
        $listingOtherCollection->getSelect()->where('main_table.id > ?', $lastListingOtherId);
        $listingOtherCollection->getSelect()->limit(self::PRODUCTS_COUNT_BY_ACCOUNT_AND_PRODUCT_TYPE);
        $listingOtherCollection->getSelect()->order('id ASC');

        $listingProducts = $listingProductCollection->getData();
        $listingOthers = $listingOtherCollection->getData();

        if (empty($listingProducts) && empty($listingOthers)) {
            $this->deleteAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_PRODUCT_ID);
            $this->deleteAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_OTHER_ID);

            return;
        }

        $skus = [];
        foreach ($listingProducts as $listingProduct) {
            $skus[] = $listingProduct['sku'];
        }

        foreach ($listingOthers as $listingOther) {
            $skus[] = $listingOther['sku'];
        }

        /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\ActualPrice */
        $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_ActualPrice');
        $repricingSynchronization->setAccount($account);
        $result = $repricingSynchronization->run($skus);
        if ($result) {
            if (!empty($listingProducts)) {
                $this->setAccountData(
                    $account,
                    self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_PRODUCT_ID,
                    $listingProducts[count($listingProducts) - 1]['id']
                );
            }

            if (!empty($listingOthers)) {
                $this->setAccountData(
                    $account,
                    self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_OTHER_ID,
                    $listingOthers[count($listingOthers) - 1]['id']
                );
            }
        }
    }

    //####################################

    /**
     * @param $account
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isPossibleToSynchronizeGeneral($account)
    {
        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

        $startDate = $this->getAccountData($account, self::REGISTRY_GENERAL_START_DATE);
        $startDate = !empty($startDate) ? strtotime($startDate) : 0;

        $lastListingProductId = $this->getAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_PRODUCT_ID);
        $lastListingOtherId = $this->getAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_OTHER_ID);

        if ($lastListingProductId !== null || $lastListingOtherId !== null) {
            return true;
        }

        if ($currentTimeStamp > $startDate + self::SYNCHRONIZE_GENERAL_INTERVAL) {
            $this->setAccountData(
                $account,
                self::REGISTRY_GENERAL_START_DATE,
                $this->getHelper('Data')->getCurrentGmtDate()
            );

            $this->setAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_PRODUCT_ID, 0);
            $this->setAccountData($account, self::REGISTRY_GENERAL_LAST_LISTING_OTHER_ID, 0);

            return true;
        }

        return false;
    }

    /**
     * @param $account
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isPossibleToSynchronizeActualPrice($account)
    {
        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

        $startDate = $this->getAccountData($account, self::REGISTRY_ACTUAL_PRICE_START_DATE);
        $startDate = !empty($startDate) ? strtotime($startDate) : 0;

        $lastListingProductId = $this->getAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_PRODUCT_ID);
        $lastListingOtherId = $this->getAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_OTHER_ID);

        if ($lastListingProductId !== null || $lastListingOtherId !== null) {
            return true;
        }

        if ($currentTimeStamp > $startDate + self::SYNCHRONIZE_ACTUAL_PRICE_INTERVAL) {
            $this->setAccountData(
                $account,
                self::REGISTRY_ACTUAL_PRICE_START_DATE,
                $this->getHelper('Data')->getCurrentGmtDate()
            );

            $this->setAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_PRODUCT_ID, 0);
            $this->setAccountData($account, self::REGISTRY_ACTUAL_PRICE_LAST_LISTING_OTHER_ID, 0);

            return true;
        }

        return false;
    }

    //#####################################

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

    protected function getAccountData($account, $key)
    {
        return $this->getHelper('Module')->getRegistry()->getValue($key . $account->getId() . '/');
    }

    protected function setAccountData($account, $key, $value)
    {
        $this->getHelper('Module')->getRegistry()->setValue($key . $account->getId() . '/', $value);
    }

    protected function deleteAccountData($account, $key)
    {
        $this->getHelper('Module')->getRegistry()->deleteValue($key . $account->getId() . '/');
    }

    //####################################
}
