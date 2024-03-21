<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Account\Get;

class GrantAccessUrl extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['account', 'get', 'grantAccessUrl'];
    }

    protected function getRequestData(): array
    {
        return [
            'mode' => $this->params['mode'],
            'back_url' => $this->params['back_url']
        ];
    }
}
