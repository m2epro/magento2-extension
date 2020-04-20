<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment;

use \Ess\M2ePro\Helper\Data as DataHelper;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Shipment\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        if (!$order->isComponentModeAmazon()) {
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
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \LogicException
     */
    private function getItemsToShip(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $itemsToShip = [];

        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */

            $additionalData = $this->getHelper('Data')->unserialize(
                $shipmentItem->getOrderItem()->getAdditionalData()
            );

            //--
            if (isset($additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()])) {
                $itemsToShip = array_merge(
                    $itemsToShip,
                    $additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()]
                );
                continue;
            }

            //--

            if (!isset($additionalData[DataHelper::CUSTOM_IDENTIFIER]['items']) ||
                !is_array($additionalData[DataHelper::CUSTOM_IDENTIFIER]['items'])) {
                continue;
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
                 * Extension stores Shipped QTY for each item starting from v6.5.4.0
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

            $itemsToShip = array_merge($itemsToShip, $shipmentItems);
            $additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$shipmentItem->getId()] = $shipmentItems;

            $shipmentItem->getOrderItem()->setAdditionalData(
                $this->getHelper('Data')->serialize($additionalData)
            );
            $shipmentItem->getOrderItem()->save();
        }

        return $itemsToShip;
    }

    //########################################
}
