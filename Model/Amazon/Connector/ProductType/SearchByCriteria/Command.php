<?php

namespace Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria;

class Command extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    public const REQUEST_PARAM_KEY = 'request_param';

    /** @var Response */
    private $preparedResponse;

    protected function getCommand(): array
    {
        return ['category', 'search', 'byCriteria'];
    }

    protected function getRequestData(): array
    {
        /** @var Request $request */
        $request = $this->params[self::REQUEST_PARAM_KEY];

        return [
            'marketplace_id' => $request->getMarketplaceId(),
            'criteria' => $request->getCriteria(),
        ];
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();
        $response = new Response();

        foreach ($responseData['categories'] as $category) {
            $response->addCategory(
                $category['name'],
                $category['is_leaf'],
                $category['product_types']
            );
        }

        $this->preparedResponse = $response;
    }

    public function getPreparedResponse(): Response
    {
        return $this->preparedResponse;
    }
}
