<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Shipment;

use \Ess\M2ePro\Helper\Data as Helper;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Shipment\Handler
 */
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
        if (!$order->isComponentModeWalmart()) {
            throw new \InvalidArgumentException('Invalid component mode.');
        }

        $trackingDetails = $this->getTrackingDetails($order, $shipment);

        if (!$order->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $items = $this->getItemsToShip($order, $shipment);
        $trackingDetails['fulfillment_date'] = $shipment->getCreatedAt();

        return $order->getChildObject()->updateShippingStatus($trackingDetails, $items)
            ? self::HANDLE_RESULT_SUCCEEDED
            : self::HANDLE_RESULT_FAILED;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @throws \LogicException
     *
     * @return array
     */
    private function getItemsToShip(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $itemsToShip = [];

        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */

            if ($shipmentItem->getId() === null) {
                continue;
            }

            $additionalData = $this->getHelper('Data')->unserialize(
                $shipmentItem->getOrderItem()->getAdditionalData()
            );

            //--
            if (isset($additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()])) {
                $itemsToShip = array_merge(
                    $itemsToShip,
                    $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()]
                );
                continue;
            }
            //--

            if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items']) ||
                !is_array($additionalData[Helper::CUSTOM_IDENTIFIER]['items'])) {
                continue;
            }

            $shipmentItems = [];
            $qtyAvailable = (int)$shipmentItem->getQty();

            $dataSize = count($additionalData[Helper::CUSTOM_IDENTIFIER]['items']);
            for ($i = 0; $i < $dataSize; $i++) {
                $data = $additionalData[Helper::CUSTOM_IDENTIFIER]['items'][$i];
                if ($qtyAvailable <= 0 || !isset($data['order_item_id'])) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $orderItemId = $data['order_item_id'];

                /** @var \Ess\M2ePro\Model\Walmart\Order\Item $item */
                $item = $this->activeRecordFactory->getObjectLoaded(
                    'Walmart_Order_Item',
                    $orderItemId,
                    'order_item_id'
                );
                if ($item === null) {
                    continue;
                }

                /**
                 * Walmart returns the same Order Item more than one time with single QTY. That data was merged
                 */
                $mergedOrderItems = $item->getMergedWalmartOrderItemIds();
                $orderItemId = $item->getWalmartOrderItemId();
                while ($mergedOrderItemId = array_shift($mergedOrderItems)) {
                    if (!isset($data['shipped_qty'][$mergedOrderItemId])) {
                        $orderItemId = $mergedOrderItemId;
                        break;
                    }
                }

                /**
                 * - Extension stores Refunded QTY for each item starting from v6.5.4.0
                 * - Walmart Order Item QTY is always equals 1
                 */
                $itemQtyShipped = isset($data['shipped_qty'][$orderItemId]) ? $data['shipped_qty'][$orderItemId] : 0;
                $itemQty = 1;

                if ($itemQtyShipped >= $itemQty) {
                    continue;
                }

                if ($itemQty > $qtyAvailable) {
                    $itemQty = $qtyAvailable;
                }

                $shipmentItems[] = [
                    'walmart_order_item_id' => $orderItemId,
                    'qty'                   => $itemQty
                ];

                $qtyAvailable -= $itemQty;
                $data['shipped_qty'][$orderItemId] = $itemQty;

                $additionalData[Helper::CUSTOM_IDENTIFIER]['items'][$i] = $data;
                $mergedOrderItemId && $i--;
            }

            $itemsToShip = array_merge($itemsToShip, $shipmentItems);
            $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()] = $shipmentItems;

            $shipmentItem->getOrderItem()->setAdditionalData($this->getHelper('Data')->serialize($additionalData));
            $shipmentItem->getOrderItem()->save();
        }

        return $itemsToShip;
    }

    //########################################
}
