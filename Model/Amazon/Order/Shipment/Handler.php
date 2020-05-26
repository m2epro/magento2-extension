<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment;

use \Ess\M2ePro\Helper\Data as DataHelper;
use Magento\Sales\Model\Order\Shipment\Item;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Shipment\Handler
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

        if (isset($additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()])) {
            return $additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()];
        }

        if (!isset($additionalData[DataHelper::CUSTOM_IDENTIFIER]['items']) ||
            !is_array($additionalData[DataHelper::CUSTOM_IDENTIFIER]['items'])) {
            return [];
        }

        $shipmentItems = [];
        $qtyAvailable = (int)$shipmentItem->getQty();

        foreach ($additionalData[DataHelper::CUSTOM_IDENTIFIER]['items'] as &$data) {
            if ($qtyAvailable <= 0 || !isset($data['order_item_id'])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Order\Item $item */
            $orderItemId = $data['order_item_id'];
            $item = $order->getItemsCollection()
                ->addFieldToFilter('amazon_order_item_id', $orderItemId)
                ->getFirstItem();
            if ($item === null) {
                continue;
            }

            /*
             * Extension stores Shipped QTY for each item starting from v1.5.0
            */
            $itemQtyShipped = isset($data['shipped_qty'][$orderItemId]) ? $data['shipped_qty'][$orderItemId] : 0;
            $itemQty = $item->getChildObject()->getQty();

            if ($itemQtyShipped >= $itemQty) {
                continue;
            }

            if ($itemQty > $qtyAvailable) {
                $itemQty = $qtyAvailable;
            }

            $items[] = [
                'amazon_order_item_id' => $orderItemId,
                'qty'                  => $itemQty
            ];

            $qtyAvailable -= $itemQty;
            $data['shipped_qty'][$orderItemId] = $itemQty;
        }

        unset($data);

        $additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()] = $shipmentItems;

        $shipmentItem->getOrderItem()->setAdditionalData(
            $this->getHelper('Data')->serialize($additionalData)
        );
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
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################
}
