<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceData;

abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    public function getCommand(): array
    {
        return ['orders', 'get', 'invoiceData'];
    }

    protected function getProcessingRunnerModelName(): string
    {
        return 'Connector_Command_Pending_Processing_Partial_Runner';
    }

    protected function getResponserParams(): array
    {
        return [
            'account_id' => (int)$this->account->getId(),
            'marketplace_id' => $this->account->getChildObject()->getMarketplaceId(),
        ];
    }

    public function getRequestData(): array
    {
        return [
            'from_date' => $this->params['from_date'],
            'order_ids' => $this->params['order_ids'],
        ];
    }
}
