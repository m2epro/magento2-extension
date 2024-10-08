<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\ProductType\GetInfo;

class Command extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    public const PARAM_KEY_MARKETPLACE_ID = 'marketplace_id';
    public const PARAM_KEY_PRODUCT_TYPE_NICK = 'product_type_nick';

    protected function getCommand(): array
    {
        return ['productType', 'get', 'info'] ;
    }

    protected function getRequestData(): array
    {
        return [
            'marketplace' => $this->params[self::PARAM_KEY_MARKETPLACE_ID],
            'product_type_nick' => $this->params[self::PARAM_KEY_PRODUCT_TYPE_NICK],
        ];
    }

    protected function prepareResponseData()
    {
        $response = $this->getResponse()->getResponseData();

        $this->responseData = new Response(
            $response['title'],
            $response['nick'],
            $response['variation_attributes'],
            $response['attributes']
        );
    }

    protected function validateResponse(): bool
    {
        $response = $this->getResponse()->getResponseData();

        return isset($response['title'])
            && isset($response['nick'])
            && isset($response['variation_attributes'])
            && isset($response['attributes']);
    }
}
