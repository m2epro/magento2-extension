<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Shipment\ItemToShipLoader;

use Ess\M2ePro\Helper\Data as Helper;
use Ess\M2ePro\Model\Order\Shipment\ItemToShipLoaderInterface;

/**
 * Class Ess\M2ePro\Model\Walmart\Order\Shipment\ItemToShipLoader\DefaultObject
 */
class PretendedToBeSimple extends DefaultObject implements ItemToShipLoaderInterface
{
    //########################################

    /**
     * @return array
     * @throws \Exception
     */
    public function loadItem()
    {
        $additionalData = $this->getHelper('Data')->unserialize(
            $this->shipmentItem->getOrderItem()->getAdditionalData()
        );

        if ($cache = $this->getAlreadyProcessed($additionalData)) {
            return $cache;
        }

        if (!$this->validate($additionalData)) {
            return [];
        }

        $orderItem = $this->getOrderItem($additionalData);
        $qtyAvailable = (int)$this->shipmentItem->getQty();

        $shippingInfo = [];
        $orderItemAdditionalData = $orderItem->getAdditionalData();
        if (isset($orderItemAdditionalData['shipping_info'])) {
            $shippingInfo = $orderItemAdditionalData['shipping_info'];
        }

        $shipmentItemId = $this->shipmentItem->getId();
        $productId = $this->shipmentItem->getProductId();
        if (!isset($shippingInfo['items'][$productId]['shipped'][$shipmentItemId])) {
            $shippingInfo['items'][$productId]['shipped'][$shipmentItemId] = $qtyAvailable;
            $orderItemAdditionalData['shipping_info'] = $shippingInfo;
            $orderItem->setSettings('additional_data', $orderItemAdditionalData);
            $orderItem->save();
        }

        foreach ($shippingInfo['items'] as $productId => $data) {
            $totalQtyShipped = 0;
            foreach ($data['shipped'] as $shipmentItemId => $itemQtyShipped) {
                $totalQtyShipped += $itemQtyShipped;
            }

            if ($totalQtyShipped < $data['total']) {
                $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()] = [];
                $this->saveAdditionalDataInShipmentItem($additionalData);

                return [];
            }
        }

        /**
         * - Walmart returns the same Order Item more than one time with single QTY. That data was merged.
         * - Walmart Order Item QTY is always equals 1.
         */
        $items = [];
        $orderItemIds = array_merge(
            [$orderItem->getChildObject()->getWalmartOrderItemId()],
            $orderItem->getChildObject()->getMergedWalmartOrderItemIds()
        );

        foreach ($orderItemIds as $orderItemId) {
            $items[] = [
                'walmart_order_item_id' => $orderItemId,
                'qty'                   => 1
            ];
        }

        $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()] = $items;
        $this->saveAdditionalDataInShipmentItem($additionalData);

        return $items;
    }

    //########################################
}
