<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Orders\Get;

class ItemsCancellationRequested extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    /**
     * @return array
     */
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'from_create_date' => $this->params['from_create_date'],
        ];
    }

    /**
     * @return array
     */
    protected function getCommand(): array
    {
        return ['orders', 'get', 'itemsCancellationRequested'];
    }

    /**
     * @return bool
     */
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['items']);
    }

    /**
     * @return void
     */
    protected function prepareResponseData()
    {
        $result = [];
        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['items'] as $item) {
            $result[] = [
                'sku' => $item['sku'],
                'walmart_order_id' => $item['walmart_order_id'],
            ];
        }

        $this->responseData = $result;
    }
}
