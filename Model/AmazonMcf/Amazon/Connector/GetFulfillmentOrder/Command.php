<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\GetFulfillmentOrder;

use M2E\AmazonMcf\Model\Amazon\Connector\GetFulfillmentOrder\Response\ShipmentItem;

class Command extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    public const SELLER_FULFILLMENT_ID_PARAM_KEY = 'seller_fulfillment_id';

    protected function getCommand(): array
    {
        return ['fulfillmentOutbound', 'order', 'get'];
    }

    protected function getRequestData(): array
    {
        /** @var string $sellerFulfillmentId */
        $sellerFulfillmentId = $this->params[self::SELLER_FULFILLMENT_ID_PARAM_KEY];

        return ['seller_fulfillment_id' => $sellerFulfillmentId];
    }

    protected function prepareResponseData(): void
    {
        $response = new \M2E\AmazonMcf\Model\Amazon\Connector\GetFulfillmentOrder\Response();

        $responseData = $this->getResponse()->getResponseData();

        if (empty($responseData['shipment_items'])) {
            $this->responseData = $response;

            return;
        }

        foreach ($responseData['shipment_items'] as $shipmentItem) {
            if (empty($shipmentItem['package_number'])) {
                continue;
            }

            $response->addShipmentItem(
                new ShipmentItem(
                    $shipmentItem['seller_fulfillment_item_id'],
                    $shipmentItem['package_number']
                )
            );
        }

        $this->responseData = $response;
    }
}
