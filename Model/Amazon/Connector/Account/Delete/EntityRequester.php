<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Delete;

class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    //########################################

    protected function getRequestData()
    {
        return array();
    }

    protected function getCommand()
    {
        return array('account','delete','entity');
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon\Connector\Account\Delete\ProcessingRunner';
    }

    //########################################
}