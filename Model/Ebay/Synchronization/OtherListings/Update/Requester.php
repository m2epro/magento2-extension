<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\OtherListings\Update;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\OtherListings\Update\Requester
 */
class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Inventory\Get\ItemsRequester
{
    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay_Synchronization_OtherListings_Update_ProcessingRunner';
    }

    //########################################
}
