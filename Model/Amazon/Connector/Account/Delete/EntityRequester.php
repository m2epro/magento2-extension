<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Delete;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Account\Delete\EntityRequester
 */
class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    //########################################

    protected function getRequestData()
    {
        return [];
    }

    protected function getCommand()
    {
        return ['account','delete','entity'];
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Connector_Account_Delete_ProcessingRunner';
    }

    //########################################
}
