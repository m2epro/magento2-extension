<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails;

/**
 * @method Order[] getPreparedResponseData()
 */
abstract class Responser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['data']['orders']);
    }

    protected function prepareResponseData(): void
    {
        $rawResponseData = $this->getResponse()->getResponseData();

        $orders = [];
        foreach ($rawResponseData['data']['orders'] as $rawOrder) {
            $orderItems = [];
            foreach ($rawOrder['items'] as $rawOrderItem) {
                $orderItems[] = new Item(
                    (string)$rawOrderItem['item_id'],
                    (string)$rawOrderItem['sku'],
                    (string)$rawOrderItem['asin'],
                    (string)$rawOrderItem['rma_id'],
                    (string)$rawOrderItem['resolution'],
                    (int)$rawOrderItem['return_qty'],
                    (string)$rawOrderItem['tracking_id'],
                    (string)$rawOrderItem['return_reason'],
                    \Ess\M2ePro\Helper\Date::createDateGmt($rawOrderItem['return_request_date']),
                    (string)$rawOrderItem['return_request_status']
                );
            }

            $orders[] = new Order(
                (string)$rawOrder['order_id'],
                \Ess\M2ePro\Helper\Date::createDateGmt($rawOrder['purchase_date']),
                $orderItems
            );
        }

        $this->preparedResponseData = $orders;
    }

    protected function getStartProcessDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->params[RequestProcessor::REQUEST_PARAM_KEY_START_PROCESS_DATE]
        );
    }
}
