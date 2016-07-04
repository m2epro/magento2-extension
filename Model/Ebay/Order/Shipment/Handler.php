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

    private $activeRecordFactory;

    //########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
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
            return $this->processOrder($order) ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
        }

        $itemsToShip = $this->getItemsToShip($order, $shipment);

        if (empty($itemsToShip) || count($itemsToShip) == $order->getItemsCollection()->getSize()) {
            return $this->processOrder($order, $trackingDetails)
                ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
        }

        $succeeded = true;
        foreach ($itemsToShip as $item) {
            if ($this->processOrderItem($item, $trackingDetails)) {
                continue;
            }

            $succeeded = false;
        }

        return $succeeded ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
    }

    //########################################

    private function processOrder(\Ess\M2ePro\Model\Order $order, array $trackingDetails = array())
    {
        $changeParams = array(
            'tracking_details' => $trackingDetails,
        );
        $this->createChange($order, $changeParams);

        return $order->getChildObject()->updateShippingStatus($trackingDetails);
    }

    private function processOrderItem(\Ess\M2ePro\Model\Order\Item $item, array $trackingDetails)
    {
        $changeParams = array(
            'tracking_details' => $trackingDetails,
            'item_id' => $item->getId(),
        );
        $this->createChange($item->getOrder(), $changeParams);

        return $item->getChildObject()->updateShippingStatus($trackingDetails);
    }

    // ---------------------------------------

    private function createChange(\Ess\M2ePro\Model\Order $order, array $params)
    {
        $orderId   = $order->getId();
        $action    =\Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING;
        $creator   =\Ess\M2ePro\Model\Order\Change::CREATOR_TYPE_OBSERVER;
        $component =\Ess\M2ePro\Helper\Component\Ebay::NICK;

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $orderId, $action, $creator, $component, $params
        );
    }

    private function getItemsToShip(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $productTypesNotAllowedByDefault = array(
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
            \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
        );

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

            if (!in_array($orderItem->getProductType(), $productTypesNotAllowedByDefault)) {
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