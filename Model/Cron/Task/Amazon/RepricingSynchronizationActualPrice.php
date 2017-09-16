<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon;

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
            $repricingSynchronization = $this->modelFactory->getObject('Amazon\Repricing\Synchronization\ActualPrice');
            $repricingSynchronization->setAccount($permittedAccount);
            $repricingSynchronization->run();
        }
    }

    //####################################

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    private function getPermittedAccounts()
    {
        $accountCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account'
        )->getCollection();

        $accountCollection->getSelect()->joinInner(
            array(
                'aar' => $this->activeRecordFactory->getObject('Amazon\Account\Repricing')
                    ->getResource()->getMainTable()
            ),
            'aar.account_id=main_table.id',
            array()
        );

        return $accountCollection->getItems();
    }

    //####################################
}