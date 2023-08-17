<?php

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\DeliveryPreferences;

abstract class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['data']);
    }

    protected function prepareResponseData(): void
    {
        $preparedData = [];

        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['data']['orders'] as $order) {
            $orderId = $order['order_id'];
            $items = $order['items'];

            $preparedData[$orderId] = $items;
        }

        $this->preparedResponseData = $preparedData;
    }
}
