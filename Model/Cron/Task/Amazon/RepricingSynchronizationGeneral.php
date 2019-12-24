<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationGeneral
 */
class RepricingSynchronizationGeneral extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/repricing_synchronization_general';
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
            /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
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

    //####################################
}
