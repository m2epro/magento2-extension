<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Marketplace\Get;

class InfoWithDetails extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['marketplace', 'get', 'info'];
    }

    protected function getRequestData(): array
    {
        return [
            'include_details' => true,
            'marketplace' => $this->params['marketplace_id'],
        ];
    }

    protected function validateResponse(): bool
    {
        $response = $this->getResponse()->getResponseData();

        return isset($response['info']['details']['product_type']);
    }
}
