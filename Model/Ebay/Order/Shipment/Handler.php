<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Shipment;

class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    private $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        if (!$order->isComponentModeEbay()) {
            throw new \InvalidArgumentException('Invalid component mode.');
        }

        $trackingDetails = $this->getTrackingDetails($shipment);

        if (!$order->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        if (empty($trackingDetails)) {
            return $order->getChildObject()->updateShippingStatus()
                ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
        }

        $itemsToShip = $this->getItemsToShip($order, $shipment);

        if (empty($itemsToShip) || count($itemsToShip) == $order->getItemsCollection()->getSize()) {
            return $order->getChildObject()->updateShippingStatus($trackingDetails)
                ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
        }

        $succeeded = true;
        foreach ($itemsToShip as $item) {
            if ($item->getChildObject()->updateShippingStatus($trackingDetails)) {
                continue;
            }

            $succeeded = false;
        }

        return $succeeded ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
    }

    //########################################

    private function getItemsToShip(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $magentoProductHelper = $this->getHelper('Magento\Product');

        $items = array();
        $allowedItems = array();
        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var $shipmentItem \Magento\Sales\Model\Order\Shipment\Item */

            $orderItem = $shipmentItem->getOrderItem();
            $parentOrderItemId = $orderItem->getParentItemId();

            if (!is_null($parentOrderItemId)) {
                !in_array($parentOrderItemId, $allowedItems) && ($allowedItems[] = $parentOrderItemId);
                continue;
            }

            if (!$magentoProductHelper->isBundleType($orderItem->getProductType()) &&
                !$magentoProductHelper->isGroupedType($orderItem->getProductType())) {

                $allowedItems[] = $orderItem->getId();
            }

            $additionalData = $orderItem->getAdditionalData();
            $additionalData = is_string($additionalData) ? @unserialize($additionalData) : array();

            $itemId = $transactionId = null;
            $orderItemDataIdentifier = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER;

            if (isset($additionalData['ebay_item_id']) && isset($additionalData['ebay_transaction_id'])) {
                // backward compatibility with versions 5.0.4 or less
                $itemId = $additionalData['ebay_item_id'];
                $transactionId = $additionalData['ebay_transaction_id'];
            } elseif (isset($additionalData[$orderItemDataIdentifier]['items'])) {
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

            if (is_null($itemId) || is_null($transactionId)) {
                continue;
            }

            $item = $this->ebayFactory->getObject('Order\Item')->getCollection()
                ->addFieldToFilter('order_id', $order->getId())
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('transaction_id', $transactionId)
                ->getFirstItem();

            if (!$item->getId()) {
                continue;
            }

            $items[$orderItem->getId()] = $item;
        }

        $resultItems = array();
        foreach ($items as $orderItemId => $item) {
            if (!in_array($orderItemId, $allowedItems)) {
                continue;
            }

            $resultItems[] = $item;
        }

        return $resultItems;
    }

    //########################################
}