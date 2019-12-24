<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\OtherListings\Update\Blocked;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\OtherListings\Update\Blocked\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked\ItemsRequester
{
    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Synchronization_OtherListings_Update_Blocked_ProcessingRunner';
    }

    //########################################
}
