<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData\Requester
 */
class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Inventory\Get\ItemsRequester
{
    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Cron_Task_Ebay_Listing_Other_Channel_SynchronizeData_ProcessingRunner';
    }

    //########################################
}
