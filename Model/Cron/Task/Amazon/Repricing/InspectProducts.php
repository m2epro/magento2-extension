<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Repricing;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\InspectProducts
 */
class InspectProducts extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/repricing/inspect_products';

    //####################################

    public function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();

        foreach ($permittedAccounts as $permittedAccount) {
            $operationDate = $this->getHelper('Data')->getCurrentGmtDate();
            $skus = $this->getNewNoneSyncSkus($permittedAccount);

            if (empty($skus)) {
                continue;
            }

            /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General   */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
            $repricingSynchronization->setAccount($permittedAccount);
            $repricingSynchronization->run($skus);

            $this->setLastUpdateDate($permittedAccount, $operationDate);
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
                'aar' => $this->activeRecordFactory->getObject('Amazon_Account_Repricing')->getResource()
                    ->getMainTable()
            ],
            'aar.account_id=main_table.id',
            []
        );

        return $accountCollection->getItems();
    }

    /**
     * @param $account \Ess\M2ePro\Model\Account
     * @return array
     */
    protected function getNewNoneSyncSkus(\Ess\M2ePro\Model\Account $account)
    {
        $accountId = $account->getId();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        )->getCollection();
        $listingProductCollection->getSelect()->join(
            ['l' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
            'l.id=main_table.listing_id',
            []
        );
        $listingProductCollection->addFieldToFilter('l.account_id', $accountId);
        $listingProductCollection->addFieldToFilter(
            'main_table.status',
            [
                'in' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN
                ]
            ]
        );
        $listingProductCollection->addFieldToFilter(
            'main_table.update_date',
            ['gt' => $this->getLastUpdateDate($account)]
        );

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns('second_table.sku');

        return $listingProductCollection->getColumnValues('sku');
    }

    /**
     * @param $account \Ess\M2ePro\Model\Account
     * @return string
     */
    protected function getLastUpdateDate(\Ess\M2ePro\Model\Account $account)
    {
        $accountId = $account->getId();

        $lastCheckedUpdateTime = $this->activeRecordFactory->getObjectLoaded('Amazon_Account_Repricing', $accountId)
            ->getLastCheckedListingProductDate();

        if ($lastCheckedUpdateTime === null) {
            $lastCheckedUpdateTime = new \DateTime(
                $this->getHelper('Data')->getCurrentGmtDate(),
                new \DateTimeZone('UTC')
            );
            $lastCheckedUpdateTime->modify('-1 hour');
            $lastCheckedUpdateTime = $lastCheckedUpdateTime->format('Y-m-d H:i:s');
        }

         return $lastCheckedUpdateTime;
    }

    /**
     * @param $account \Ess\M2ePro\Model\Account
     * @param $syncDate \Datetime|String
     */
    protected function setLastUpdateDate(\Ess\M2ePro\Model\Account $account, $syncDate)
    {
        $accountId = $account->getId();

        /** @var $accountRepricingModel \Ess\M2ePro\Model\Amazon\Account\Repricing */
        $accountRepricingModel = $this->activeRecordFactory->getObjectLoaded('Amazon_Account_Repricing', $accountId);
        $accountRepricingModel->setData(
            'last_checked_listing_product_update_date',
            $syncDate
        );

        $accountRepricingModel->save();
    }

    //####################################
}
