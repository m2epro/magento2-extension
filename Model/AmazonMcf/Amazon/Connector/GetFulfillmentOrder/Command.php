<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\GetFulfillmentOrder;

use M2E\AmazonMcf\Model\Amazon\Connector\GetFulfillmentOrder\Response;

class Command extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    public const SELLER_FULFILLMENT_ID_PARAM_KEY = 'seller_fulfillment_id';

    private const ORDER_STATUS_PROCESSING = 'Processing';
    private const ORDER_STATUS_COMPLETE = 'Complete';
    private const ORDER_STATUS_INVALID = 'Invalid';

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

        if (
            !empty($responseData['status'])
            && method_exists($response, 'setOrderStatus')
        ) {
            $orderStatusMap = [
                self::ORDER_STATUS_PROCESSING => Response::ORDER_STATUS_PROCESSING,
                self::ORDER_STATUS_COMPLETE => Response::ORDER_STATUS_COMPLETE,
                self::ORDER_STATUS_INVALID => Response::ORDER_STATUS_INVALID,
            ];
            $response->setOrderStatus($orderStatusMap[$responseData['status']]);
        }

        foreach ($responseData['shipment_items'] ?? [] as $shipmentItem) {
            if (empty($shipmentItem['package_number'])) {
                continue;
            }

            $response->addShipmentItem(
                new Response\ShipmentItem(
                    $shipmentItem['seller_fulfillment_item_id'],
                    $shipmentItem['package_number']
                )
            );
        }

        $this->responseData = $response;
    }
}
