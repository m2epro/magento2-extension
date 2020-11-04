<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\ItemsRequester
{
    //########################################

    /**
     * @return string
     */
    protected function getProcessingRunnerModelName()
    {
        return 'Cron_Task_Amazon_Listing_SynchronizeInventory_ProcessingRunner';
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getResponserParams()
    {
        return array_merge(
            parent::getResponserParams(),
            [
                'request_date' => $this->getHelper('Data')->getCurrentGmtDate(),
            ]
        );
    }

    //########################################
}
