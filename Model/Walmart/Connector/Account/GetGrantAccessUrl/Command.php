<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Account\GetGrantAccessUrl;

class Command extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    public const PARAM_KEY_BACK_URL = 'back_url';

    protected function getCommand(): array
    {
        return ['account', 'get', 'grantAccessUrl'];
    }

    protected function getRequestData(): array
    {
        return [
            'back_url' => $this->params[self::PARAM_KEY_BACK_URL]
        ];
    }

    public function prepareResponseData(): object
    {
        $response = $this->getResponse()->getResponseData();
        $this->responseData = new Response($response['url']);

        return $this->responseData;
    }
}
