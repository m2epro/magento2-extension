<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\OtherListings\Update;

class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Inventory\Get\ItemsRequester
{
    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay\Synchronization\OtherListings\Update\ProcessingRunner';
    }

    //########################################
}