<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Update;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Account\Update\EntityRequester
 */
class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    protected function getRequestData()
    {
        return $this->params;
    }

    protected function getCommand()
    {
        return ['account','update','entity'];
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Connector_Account_Update_ProcessingRunner';
    }

    // ########################################
}
