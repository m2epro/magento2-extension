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
    public const NICK = 'amazon/repricing/inspect_products';

    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
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
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    //####################################

    public function performActions()
    {
        $permittedAccounts = $this->accountCollectionFactory
            ->create()
            ->getAccountsWithValidRepricingAccount();

        foreach ($permittedAccounts as $permittedAccount) {
            $operationDate = $this->getHelper('Data')->getCurrentGmtDate();
            $skus = $this->getNewNoneSyncSkus($permittedAccount);

            if (empty($skus)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General $repricingSynchronization */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
            $repricingSynchronization->setAccount($permittedAccount);
            $repricingSynchronization->run($skus);

            $this->setLastUpdateDate($permittedAccount, $operationDate);
        }
    }

    //####################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
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
                    \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN,
                ],
            ]
        );
        $listingProductCollection->addFieldToFilter(
            'main_table.update_date',
            ['gt' => $this->getLastUpdateDate($account)]
        );

        $listingProductCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $listingProductCollection->getSelect()->columns('second_table.sku');

        return $listingProductCollection->getColumnValues('sku');
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
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
     * @param \Ess\M2ePro\Model\Account $account
     * @param \Datetime|String $syncDate
     */
    protected function setLastUpdateDate(\Ess\M2ePro\Model\Account $account, $syncDate)
    {
        $accountId = $account->getId();

        /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing $accountRepricingModel */
        $accountRepricingModel = $this->activeRecordFactory->getObjectLoaded('Amazon_Account_Repricing', $accountId);
        $accountRepricingModel->setData(
            'last_checked_listing_product_update_date',
            $syncDate
        );

        $accountRepricingModel->save();
    }

    //####################################
}
