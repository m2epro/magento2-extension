<?php

namespace Ess\M2ePro\Model\Amazon\Order\Creditmemo;

class Handler extends \Ess\M2ePro\Model\Order\Creditmemo\Handler
{
    public const AMAZON_REFUND_REASON_CUSTOMER_RETURN = 'CustomerReturn';
    public const AMAZON_REFUND_REASON_NO_INVENTORY = 'NoInventory';
    public const AMAZON_REFUND_REASON_BUYER_CANCELED = 'BuyerCanceled';

    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->dataHelper = $dataHelper;
    }

    protected function getItemsToRefund(
        \Ess\M2ePro\Model\Order $order,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo
    ): array {
        $refundReason = $this->getRefundReason($order);
        $ordersItemsByAmazonId = $this->getAmazonOrderItemsByAmazonId($order);

        $itemsForRefund = [];
        foreach ($creditmemo->getItems() as $creditmemoItem) {
            $cancelledQty = (int)$creditmemoItem->getQty();

            $items = $this->getOrderItemsFromCreditMemoItem($creditmemoItem);

            foreach ($items as $data) {
                $orderItemId = $data['order_item_id'] ?? null;

                if (!$orderItemId || !isset($ordersItemsByAmazonId[$orderItemId])) {
                    continue;
                }

                $item = $ordersItemsByAmazonId[$orderItemId];

                $price = $creditmemoItem->getPrice() ?? 0.0;
                $tax = $creditmemoItem->getTaxAmount() ?? 0.0;

                $cancelledItemQty = $cancelledQty;
                if ($cancelledItemQty > $item->getChildObject()->getQtyPurchased()) {
                    $cancelledItemQty = $item->getChildObject()->getQtyPurchased();

                    $cancelledQty -= $cancelledItemQty;
                    if ($cancelledQty < 0) {
                        $cancelledQty = 0;
                    }
                }

                $itemForRefund = [
                    'item_id' => $orderItemId,
                    'reason' => $refundReason,
                    'purchased_qty' => $item->getChildObject()->getQtyPurchased(),
                    'cancelled_qty' => $cancelledItemQty,
                    'prices' => [
                        'product' => $price,
                        'shipping' => $item->getChildObject()->getShippingPrice(),
                    ],
                    'taxes' => [
                        'product' => $tax,
                        'shipping' => $item->getChildObject()->getShippingTaxAmount(),
                    ],
                ];

                $itemsForRefund[] = $itemForRefund;
            }
        }

        return $itemsForRefund;
    }

    protected function getComponentMode(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    protected function isOrderStatusShipped(\Ess\M2ePro\Model\Order $order): bool
    {
        return $order->getChildObject()->isShipped() || $order->getChildObject()->isPartiallyShipped();
    }

    private function getRefundReason(\Ess\M2ePro\Model\Order $order): string
    {
        if ($order->getChildObject()->isShipped()) {
            return self::AMAZON_REFUND_REASON_CUSTOMER_RETURN;
        }

        if ($order->getChildObject()->isPartiallyShipped()) {
            return self::AMAZON_REFUND_REASON_CUSTOMER_RETURN;
        }

        if ($order->isOrderStatusUpdatingToShipped()) {
            return self::AMAZON_REFUND_REASON_CUSTOMER_RETURN;
        }

        return self::AMAZON_REFUND_REASON_NO_INVENTORY;
    }

    private function getAmazonOrderItemsByAmazonId(\Ess\M2ePro\Model\Order $order): array
    {
        $orderItemsCollection = $order->getItemsCollection();
        $ordersItemsByAmazonId = [];
        /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
        foreach ($orderItemsCollection as $orderItem) {
            $ordersItemsByAmazonId[$orderItem->getChildObject()->getAmazonOrderItemId()] = $orderItem;
        }

        return $ordersItemsByAmazonId;
    }

    private function getOrderItemsFromCreditMemoItem(\Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem): array
    {
        $additionalData = $creditmemoItem->getOrderItem()->getAdditionalData();
        if (!is_string($additionalData)) {
            return [];
        }

        $additionalData = $this->dataHelper->unserialize($additionalData);

        if (
            !isset($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items']) ||
            !is_array($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'])
        ) {
            return [];
        }

        return $additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'];
    }
}
