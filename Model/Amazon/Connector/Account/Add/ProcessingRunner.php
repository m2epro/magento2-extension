<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Add;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    //########################################

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account', $params['account_id']
        );

        $account->addProcessingLock(NULL, $this->getProcessingObject()->getId());
        $account->addProcessingLock('server_synchronize', $this->getProcessingObject()->getId());
        $account->addProcessingLock('adding_to_server', $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account', $params['account_id']
        );

        $account->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('server_synchronize', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('adding_to_server', $this->getProcessingObject()->getId());
    }

    //########################################
}