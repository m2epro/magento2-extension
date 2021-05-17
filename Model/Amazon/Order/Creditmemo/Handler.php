<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Creditmemo;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Creditmemo\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\Creditmemo\Handler
{
    const AMAZON_REFUND_REASON_CUSTOMER_RETURN = 'CustomerReturn';
    const AMAZON_REFUND_REASON_NO_INVENTORY    = 'NoInventory';

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getItemsToRefund(
        \Ess\M2ePro\Model\Order $order,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo
    ) {
        $itemsForRefund = [];

        $refundReason = $this->isOrderStatusShipped($order) || $order->isOrderStatusUpdatingToShipped() ?
            self::AMAZON_REFUND_REASON_CUSTOMER_RETURN :
            self::AMAZON_REFUND_REASON_NO_INVENTORY;

        /** @var \Ess\M2ePro\Model\Order\ProxyObject $proxy */
        $proxy = $order->getProxy()->setStore($order->getStore());

        $isTaxAddedToShippingCost = $proxy->isTaxModeNone() && $proxy->getShippingPriceTaxRate() > 0;

        $fullShippingCostRefunded = $creditmemo->getShippingAmount() > 0 ?
            $proxy->getShippingData()['shipping_price'] === $creditmemo->getShippingAmount() :
            false;

        $fullShippingTaxRefunded = $creditmemo->getShippingTaxAmount() > 0 ?
            $order->getChildObject()->getShippingPriceTaxAmount() === $creditmemo->getShippingTaxAmount() :
            false;

        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            /** @var \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem */

            $additionalData = $creditmemoItem->getOrderItem()->getAdditionalData();
            if (!is_string($additionalData)) {
                continue;
            }

            $additionalData = $this->getHelper('Data')->unserialize($additionalData);

            if (!isset($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items']) ||
                !is_array($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'])) {
                continue;
            }

            $qtyAvailable = (int)$creditmemoItem->getQty();

            foreach ($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'] as &$data) {
                if ($qtyAvailable <= 0 || !isset($data['order_item_id'])) {
                    continue;
                }

                $orderItemId = $data['order_item_id'];
                if (in_array($orderItemId, $itemsForRefund)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $item = $order->getItemsCollection()
                    ->addFieldToFilter('amazon_order_item_id', $orderItemId)
                    ->getFirstItem();
                if (!$item->getId()) {
                    continue;
                }

                /*
                 * Extension stores Refunded QTY for each item starting from v1.5.1
                 */
                $itemQtyRef = isset($data['refunded_qty'][$orderItemId]) ? $data['refunded_qty'][$orderItemId] : 0;
                $itemQty = $item->getChildObject()->getQtyPurchased();

                if ($itemQtyRef >= $itemQty) {
                    continue;
                }

                if ($itemQty > $qtyAvailable) {
                    $itemQty = $qtyAvailable;
                }

                $price = $creditmemoItem->getPriceInclTax();
                $tax = $creditmemoItem->getTaxAmount();

                if ($price > $item->getChildObject()->getPrice()) {
                    $price = $item->getChildObject()->getPrice();
                }

                if ($tax > $item->getChildObject()->getTaxAmount()) {
                    $tax = $item->getChildObject()->getTaxAmount();
                }

                $itemForRefund = [
                    'item_id' => $orderItemId,
                    'reason'  => $refundReason,
                    'qty'     => $itemQty,
                    'prices'  => [
                        'product' => $price,
                    ],
                    'taxes'   => [
                        'product' => $tax,
                    ],
                ];

                if ($fullShippingCostRefunded) {
                    $itemForRefund['prices']['shipping'] = $item->getChildObject()->getShippingPrice();
                }

                if ($fullShippingTaxRefunded || ($fullShippingCostRefunded && $isTaxAddedToShippingCost)) {
                    $itemForRefund['taxes']['shipping'] = $item->getChildObject()->getShippingTaxAmount();
                }

                $itemsForRefund[] = $itemForRefund;

                $qtyAvailable -= $itemQty;
                $data['refunded_qty'][$orderItemId] = $itemQty;
            }

            unset($data);

            $creditmemoItem->getOrderItem()->setAdditionalData(
                $this->getHelper('Data')->serialize($additionalData)
            );
            $creditmemoItem->getOrderItem()->save();
        }

        return $itemsForRefund;
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isOrderStatusShipped(\Ess\M2ePro\Model\Order $order)
    {
        return $order->getChildObject()->isShipped() || $order->getChildObject()->isPartiallyShipped();
    }

    //########################################
}
