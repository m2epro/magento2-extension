<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

final class RepricingSynchronization extends AbstractModel
{
    const NICK = 'repricing_synchronization';
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
            /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon\Repricing\Synchronization');
            $repricingSynchronization->setAccount($permittedAccount);
            $repricingSynchronization->runFull();
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
            'aar.account_id=main_table.id',
            array()
        );

        return $accountCollection->getItems();
    }

    //####################################
}