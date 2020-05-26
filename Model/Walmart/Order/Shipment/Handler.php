<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Shipment;

use \Ess\M2ePro\Helper\Data as Helper;
use Magento\Sales\Model\Order\Shipment\Item;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Shipment\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param Item $shipmentItem
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getItemsToShipForShipmentItem(\Ess\M2ePro\Model\Order $order, Item $shipmentItem)
    {
        $additionalData = $shipmentItem->getOrderItem()->getAdditionalData();
        if (!is_string($additionalData)) {
            return [];
        }

        $additionalData = $this->getHelper('Data')->unserialize($additionalData);

        if (isset($additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()])) {
            return $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()];
        }

        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items']) ||
            !is_array($additionalData[Helper::CUSTOM_IDENTIFIER]['items'])) {
            return [];
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
             * - Extension stores Refunded QTY for each item starting from v1.4.0
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

        $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()] = $shipmentItems;

        $shipmentItem->getOrderItem()->setAdditionalData($this->getHelper('Data')->serialize($additionalData));
        $shipmentItem->getOrderItem()->save();

        return $shipmentItems;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    protected function getTrackingDetails(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        return array_merge(
            parent::getTrackingDetails($order, $shipment),
            ['fulfillment_date' => $shipment->getCreatedAt()]
        );
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
