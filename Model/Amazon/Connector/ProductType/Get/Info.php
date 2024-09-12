<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\ProductType\Get;

class Info extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['productType', 'get', 'info'];
    }

    protected function getRequestData(): array
    {
        return [
            'product_type_nick' => $this->params['product_type_nick'],
            'marketplace' => $this->params['marketplace_id'],
        ];
    }

    protected function validateResponse(): bool
    {
        $response = $this->getResponse()->getResponseData();

        return isset(
            $response['nick'],
            $response['title'],
            $response['attributes'],
            $response['attributes_groups'],
            $response['variation_themes'],
            $response['last_update']
        );
    }
}
