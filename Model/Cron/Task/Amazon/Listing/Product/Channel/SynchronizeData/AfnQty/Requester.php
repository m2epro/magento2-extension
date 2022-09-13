<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\AfnQty\ItemsRequester
{
    /**
     * @return string
     */
    protected function getProcessingRunnerModelName(): string
    {
        return 'Cron_Task_Amazon_Listing_Product_Channel_SynchronizeData_AfnQty_ProcessingRunner';
    }
}
