<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetInfoWithDetails;

class Command extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    public const PARAM_KEY_MARKETPLACE_ID = 'marketplace_id';

    protected function getCommand(): array
    {
        return ['marketplace', 'get', 'info'];
    }

    protected function getRequestData(): array
    {
        return [
            'include_details' => true,
            'marketplace' => $this->params[self::PARAM_KEY_MARKETPLACE_ID],
        ];
    }

    protected function prepareResponseData()
    {
        $productTypes = [];
        $productTypesNicks = [];

        $response = $this->getResponse()->getResponseData();
        foreach ($response['info']['details']['product_types'] as $productType) {
            $productTypes[] = [
                'nick' => $productType['nick'],
                'title' => $productType['title'],
            ];
            $productTypesNicks[] = $productType['nick'];
        }

        $this->responseData = new Response(
            $productTypes,
            $productTypesNicks,
            \Ess\M2ePro\Helper\Date::createDateGmt($response['info']['last_update'])
        );
    }

    protected function validateResponse(): bool
    {
        $response = $this->getResponse()->getResponseData();

        return isset($response['info']['details']['product_types'])
            && isset($response['info']['last_update']);
    }
}
