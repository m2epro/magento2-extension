<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Other;

class Delete extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'items' => $this->params['items'],
        ];
    }

    protected function getCommand(): array
    {
        return ['item', 'end', 'batch'];
    }
}
