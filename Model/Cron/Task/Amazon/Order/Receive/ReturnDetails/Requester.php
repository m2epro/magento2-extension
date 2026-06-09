<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\ReturnDetails;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Requester
{
    protected function getProcessingRunnerModelName(): string
    {
        return 'Cron_Task_Amazon_Order_Receive_ReturnDetails_ProcessingRunner';
    }
}
