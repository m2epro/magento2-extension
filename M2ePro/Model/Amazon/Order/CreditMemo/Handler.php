<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\CreditMemo;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\CreditMemo\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\CreditMemo\Handler
{
    //########################################

    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        if (!$order->isComponentModeAmazon()) {
            throw new \InvalidArgumentException('Invalid component mode.');
        }

        if (!$order->getChildObject()->canRefund()) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $items = $this->getItemsToRefund($order, $creditmemo);
        return $order->getChildObject()->refund($items) ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
    }

    //########################################

    protected function getItemsToRefund(
        \Ess\M2ePro\Model\Order $order,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo
    ) {
        $itemsForCancel = [];

        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            /** @var \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem */

            $additionalData = $this->getHelper('Data')->unserialize(
                $creditmemoItem->getOrderItem()->getAdditionalData()
            );

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
                if (in_array($orderItemId, $itemsForCancel)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $item = $order->getItemsCollection()
                    ->addFieldToFilter('amazon_order_item_id', $orderItemId)
                    ->getFirstItem();
                if ($item === null) {
                    continue;
                }

                /*
                 * Extension stores Refunded QTY for each item starting from v6.5.4.0
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
                $tax   = $creditmemoItem->getTaxAmount();

                if ($price > $item->getChildObject()->getPrice()) {
                    $price = $item->getChildObject()->getPrice();
                }

                if ($tax > $item->getChildObject()->getTaxAmount()) {
                    $tax = $item->getChildObject()->getTaxAmount();
                }

                $itemsForCancel[] = [
                    'item_id'  => $orderItemId,
                    'qty'      => $itemQty,
                    'prices'   => [
                        'product' => $price,
                    ],
                    'taxes'    => [
                        'product' => $tax,
                    ],
                ];

                $qtyAvailable -= $itemQty;
                $data['refunded_qty'][$orderItemId] = $itemQty;
            }

            unset($data);

            $creditmemoItem->getOrderItem()->setAdditionalData(
                $this->getHelper('Data')->serialize($additionalData)
            );
            $creditmemoItem->getOrderItem()->save();
        }

        return $itemsForCancel;
    }

    //########################################
}
