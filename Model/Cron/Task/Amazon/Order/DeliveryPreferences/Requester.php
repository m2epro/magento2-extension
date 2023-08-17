<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\DeliveryPreferences;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\DeliveryPreferences\ItemsRequester
{
    protected function getProcessingRunnerModelName(): string
    {
        return 'Cron_Task_Amazon_Order_DeliveryPreferences_ProcessingRunner';
    }
}
