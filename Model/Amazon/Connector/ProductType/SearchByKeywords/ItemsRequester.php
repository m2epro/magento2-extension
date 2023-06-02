<?php

namespace Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByKeywords;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['productType', 'search', 'suggestions'];
    }

    protected function getRequestData(): array
    {
        return [
            'marketplace_id' => $this->params['marketplace_id'],
            'keywords' => $this->params['keywords'],
        ];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['product_types'])
            && is_array($responseData['product_types']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        $productTypes = [];
        foreach ($responseData['product_types'] as $productType) {
            $productTypes[] = $productType;
        }

        $this->responseData = $productTypes;
    }
}
