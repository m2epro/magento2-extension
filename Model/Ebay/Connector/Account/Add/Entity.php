<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Account\Add;

class Entity extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['account', 'add', 'entity'];
    }

    protected function getRequestData(): array
    {
        return [
            'mode' => $this->params['mode'],
            'auth_code' => $this->params['auth_code']
        ];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        if (!$responseData['user_id'] || !$responseData['token_expired_date']) {
            return false;
        }

        return true;
    }
}
