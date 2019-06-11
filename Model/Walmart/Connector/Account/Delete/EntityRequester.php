<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Account\Delete;

class EntityRequester extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Requester
{
    //########################################

    public function getRequestData()
    {
        return array();
    }

    protected function getCommand()
    {
        return array('account', 'delete', 'entity');
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart\Connector\Account\Delete\ProcessingRunner';
    }

    //########################################
}