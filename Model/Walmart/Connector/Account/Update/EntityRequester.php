<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Account\Update;

class EntityRequester extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Requester
{
    // ########################################

    public function getRequestData()
    {
        return $this->params;
    }

    protected function getCommand()
    {
        return array('account', 'update', 'entity');
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart\Connector\Account\Update\ProcessingRunner';
    }

    // ########################################
}