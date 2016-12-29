<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment;

class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return int
     */
    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        if (!$order->isComponentModeAmazon()) {
            throw new \InvalidArgumentException('Invalid component mode.');
        }

        $trackingDetails = $this->getTrackingDetails($shipment);

        if (!$order->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $items = $this->getItemsToShip($order, $shipment);

        $trackingDetails['fulfillment_date'] = $shipment->getCreatedAt();

        $order->getChildObject()->updateShippingStatus($trackingDetails, $items);

        return self::HANDLE_RESULT_SUCCEEDED;
    }

    /**
     * @param \Ess\M2ePro\Model\Order          $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @throws \LogicException
     *
     * @return array
     */
    private function getItemsToShip(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $shipmentItems = $shipment->getAllItems();
        $orderItemDataIdentifier = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER;

        $items = array();

        foreach ($shipmentItems as $shipmentItem) {
            $additionalData = $shipmentItem->getOrderItem()->getAdditionalData();
            $additionalData = is_string($additionalData) ? @unserialize($additionalData) : array();

            if (!isset($additionalData[$orderItemDataIdentifier]['items'])) {
                continue;
            }

            if (!is_array($additionalData[$orderItemDataIdentifier]['items'])) {
                continue;
            }

            $qtyAvailable = (int)$shipmentItem->getQty();

            foreach ($additionalData[$orderItemDataIdentifier]['items'] as $data) {
                if ($qtyAvailable <= 0) {
                    continue;
                }

                if (!isset($data['order_item_id'])) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Order\Item $item */
                $item = $this->activeRecordFactory->getObjectLoaded(
                    'Amazon\Order\Item', $data['order_item_id'], 'amazon_order_item_id'
                );

                if (is_null($item)) {
                    continue;
                }

                $qty = $item->getQtyPurchased();

                if ($qty > $qtyAvailable) {
                    $qty = $qtyAvailable;
                }

                $items[] = array(
                    'qty' => $qty,
                    'amazon_order_item_id' => $data['order_item_id']
                );

                $qtyAvailable -= $qty;
            }
        }

        return $items;
    }

    //########################################
}
