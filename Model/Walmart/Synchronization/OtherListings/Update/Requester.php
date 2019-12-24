<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\OtherListings\Update;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\OtherListings\Update\Requester
 */
class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Inventory\Get\ItemsRequester
{
    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart_Synchronization_OtherListings_Update_ProcessingRunner';
    }

    // ########################################
}
