<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment\ItemToShipLoader;

use Ess\M2ePro\Helper\Data as Helper;
use Ess\M2ePro\Model\Order\Shipment\ItemToShipLoaderInterface;

/**
 * Class Ess\M2ePro\Model\Amazon\Order\Shipment\ItemToShipLoader\DefaultObject
 */
class PretendedToBeSimple extends DefaultObject implements ItemToShipLoaderInterface
{
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

        $items = [
            [
                'amazon_order_item_id' => $orderItem->getChildObject()->getAmazonOrderItemId(),
                'qty' => $shippingInfo['send'],
            ],
        ];

        $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()] = $items;
        $this->saveAdditionalDataInShipmentItem($additionalData);

        return $items;
    }

    //########################################

    protected function validate(array $additionalData)
    {
        if (
            !isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items']) ||
            !is_array($additionalData[Helper::CUSTOM_IDENTIFIER]['items'])
        ) {
            return false;
        }

        if ($this->shipmentItem->getQty() <= 0) {
            return false;
        }

        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['order_item_id'])) {
            return false;
        }

        $orderItem = $this->getOrderItem($additionalData);
        if (!$orderItem->getId()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $additionalData
     *
     * @return \Ess\M2ePro\Model\Order\Item
     */
    protected function getOrderItem(array $additionalData)
    {
        if ($this->orderItem !== null) {
            return $this->orderItem;
        }

        $this->orderItem = $this->amazonFactory
            ->getObject('Order_Item')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId())
            ->addFieldToFilter(
                'amazon_order_item_id',
                $additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['order_item_id']
            )
            ->getFirstItem();

        return $this->orderItem;
    }
}
