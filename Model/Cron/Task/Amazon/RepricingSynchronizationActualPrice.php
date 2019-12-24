<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationActualPrice
 */
class RepricingSynchronizationActualPrice extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/repricing_synchronization_actual_price';
    const MAX_MEMORY_LIMIT = 512;

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
            /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\ActualPrice */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_ActualPrice');
            $repricingSynchronization->setAccount($permittedAccount);
            $repricingSynchronization->run();
            $this->getLockItem()->activate();
        }
    }

    //####################################

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    private function getPermittedAccounts()
    {
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

    //####################################
}
