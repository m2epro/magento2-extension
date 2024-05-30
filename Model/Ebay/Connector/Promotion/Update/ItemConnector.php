<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Promotion\Update;

class ItemConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    private const ERROR_CODE = 38227;

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'marketplace' => $this->params['marketplace'],
            'promotion_id' => $this->params['promotion_id'],
            'items_ids' => $this->params['items_ids'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotion', 'update', 'item'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['errors']);
    }

    protected function prepareResponseData(): void
    {
        $preparedData = [];

        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['errors'] as $item) {
            if ($item['code'] !== self::ERROR_CODE) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown error code.');
            }

            $preparedData[$item['item_id']] = $item['message'];
        }

        $this->responseData = $preparedData;
    }
}
