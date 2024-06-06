<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\CreateFulfillmentOrder;

class Command extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    public const REQUEST_PARAM_KEY = 'request_param';

    protected function getCommand(): array
    {
        return ['fulfillmentOutbound', 'order', 'create'];
    }

    protected function getRequestData(): array
    {
        /** @var \M2E\AmazonMcf\Model\Amazon\Connector\CreateFulfillmentOrder\Request $request */
        $request = $this->params[self::REQUEST_PARAM_KEY];

        $destinationAddress = $request->getDestinationAddress();
        $items = [];
        foreach ($request->getItems() as $item) {
            $items[] = [
                'seller_sku' => $item->getSellerSku(),
                'seller_fulfillment_item_id' => $item->getSellerFulfillmentOrderItemId(),
                'qty' => $item->getQty(),
            ];
        }

        return [
            'order' => [
                'seller_fulfillment_id' => $request->getSellerFulfillmentId(),
                'displayable_order_id' => $request->getDisplayableOrderId(),
                'displayable_order_comment' => $request->getDisplayableOrderComment(),
                'shipping_speed_category' => $request->getShippingSpeedCategory(),
                'displayable_order_date' => $request->getDisplayableOrderDate()->format('Y-m-d H:i:s'),
                'destination_address' => [
                    'name' => $destinationAddress->getName(),
                    'address_line' => $destinationAddress->getAddressLine(),
                    'city' => $destinationAddress->getCity(),
                    'phone' => $destinationAddress->getPhone(),
                    'state_or_region' => $destinationAddress->getStateOrRegion(),
                    'postal_code' => $destinationAddress->getPostalCode(),
                    'country_code' => $destinationAddress->getCountryCode(),
                ],
                'items' => $items,
            ],
        ];
    }

    protected function prepareResponseData()
    {
        $this->responseData = new \M2E\AmazonMcf\Model\Amazon\Connector\CreateFulfillmentOrder\Response();
    }
}
