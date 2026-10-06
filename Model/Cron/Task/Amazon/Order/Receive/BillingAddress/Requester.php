<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\BillingAddress;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\BillingAddress\ItemsRequester
{
    protected function getRequestData(): array
    {
        return [
            'from_date' => $this->params['from_date'],
        ];
    }

    protected function getProcessingRunnerModelName(): string
    {
        return 'Cron_Task_Amazon_Order_Receive_BillingAddress_ProcessingRunner';
    }
}
