<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Shipment;

use Magento\Sales\Model\Order\Shipment\Item;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\Shipment\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($activeRecordFactory, $carrierFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param array $trackingDetails
     * @param array $itemsToShip
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processStatusUpdates(\Ess\M2ePro\Model\Order $order, array $trackingDetails, array $itemsToShip)
    {
        if (empty($trackingDetails)) {
            return $order->getChildObject()->updateShippingStatus();
        }

        if (empty($itemsToShip) || count($itemsToShip) == $order->getItemsCollection()->getSize()) {
            return $order->getChildObject()->updateShippingStatus($trackingDetails);
        }

        $succeeded = true;
        $initianor = $order->getLog()->getInitiator();
        foreach ($itemsToShip as $item) {
            /**@var \Ess\M2ePro\Model\Order\Item $item */
            $item->getChildObject()->getEbayOrder()->getParentObject()->getLog()->setInitiator($initianor);
            if ($item->getChildObject()->updateShippingStatus($trackingDetails)) {
                continue;
            }

            $succeeded = false;
        }

        return $succeeded;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getItemsToShip(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $magentoProductHelper = $this->getHelper('Magento\Product');

        $itemsToShip = [];
        $allowedItems = [];
        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var $shipmentItem Item */

            $orderItem = $shipmentItem->getOrderItem();
            $parentOrderItemId = $orderItem->getParentItemId();

            if ($parentOrderItemId !== null) {
                !in_array($parentOrderItemId, $allowedItems) && ($allowedItems[] = $parentOrderItemId);
                continue;
            }

            if (!$magentoProductHelper->isBundleType($orderItem->getProductType()) &&
                !$magentoProductHelper->isGroupedType($orderItem->getProductType())) {
                $allowedItems[] = $orderItem->getId();
            }
            $orderItems = $this->getItemsToShipForShipmentItem($order, $shipmentItem);
            if ($orderItems === null) {
                return [];
            }

            $itemsToShip += $orderItems;
        }

        $resultItems = [];
        foreach ($itemsToShip as $orderItemId => $item) {
            if (!in_array($orderItemId, $allowedItems)) {
                continue;
            }

            $resultItems[] = $item;
        }

        return $resultItems;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param Item $shipmentItem
     * @return array|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getItemsToShipForShipmentItem(\Ess\M2ePro\Model\Order $order, Item $shipmentItem)
    {
        $orderItem = $shipmentItem->getOrderItem();
        $additionalData = $shipmentItem->getOrderItem()->getAdditionalData();
        if (!is_string($additionalData)) {
            return [];
        }

        $additionalData = $this->getHelper('Data')->unserialize($additionalData);

        $itemId = $transactionId = null;
        $orderItemDataIdentifier = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER;

        if (isset($additionalData[$orderItemDataIdentifier]['items'])) {
            if (!is_array($additionalData[$orderItemDataIdentifier]['items'])
                || count($additionalData[$orderItemDataIdentifier]['items']) != 1
            ) {
                return null;
            }

            if (isset($additionalData[$orderItemDataIdentifier]['items'][0]['item_id'])) {
                $itemId = $additionalData[$orderItemDataIdentifier]['items'][0]['item_id'];
            }

            if (isset($additionalData[$orderItemDataIdentifier]['items'][0]['transaction_id'])) {
                $transactionId = $additionalData[$orderItemDataIdentifier]['items'][0]['transaction_id'];
            }
        }

        if ($itemId === null || $transactionId === null) {
            return [];
        }

        $item = $this->ebayFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('item_id', $itemId)
            ->addFieldToFilter('transaction_id', $transactionId)
            ->getFirstItem();

        if (!$item->getId()) {
            return [];
        }

        return [$orderItem->getId() => $item];
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################
}
