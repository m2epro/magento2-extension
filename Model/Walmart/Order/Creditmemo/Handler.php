<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Creditmemo;

class Handler extends \Ess\M2ePro\Model\Order\Creditmemo\Handler
{
    protected $activeRecordFactory = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->walmartFactory = $walmartFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

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

            $dataSize = count($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items']);
            for ($i = 0; $i < $dataSize; $i++) {
                $data = $additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'][$i];
                if ($qtyAvailable <= 0 || !isset($data['order_item_id'])) {
                    continue;
                }

                $orderItemId = $data['order_item_id'];
                if (in_array($orderItemId, $itemsForCancel)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Order\Item $parentObject */
                $parentObject = $this->walmartFactory
                    ->getObject('Order_Item')
                    ->getCollection()
                    ->addFieldToFilter('order_id', $order->getId())
                    ->addFieldToFilter('walmart_order_item_id', $orderItemId)
                    ->getFirstItem();

                if (!$parentObject->getId()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Walmart\Order\Item $item */
                $item = $parentObject->getChildObject();

                /**
                 * Walmart returns the same Order Item more than one time with single QTY. That data was merged
                 */
                $mergedOrderItems = $item->getMergedWalmartOrderItemIds();
                $orderItemId = $item->getWalmartOrderItemId();
                while ($mergedOrderItemId = array_shift($mergedOrderItems)) {
                    if (!isset($data['refunded_qty'][$mergedOrderItemId])) {
                        $orderItemId = $mergedOrderItemId;
                        break;
                    }
                }

                /**
                 * - Extension stores Refunded QTY for each item starting from v1.4.0
                 * - Walmart Order Item QTY is always equals 1
                 */
                $itemQtyRef = isset($data['refunded_qty'][$orderItemId]) ? $data['refunded_qty'][$orderItemId] : 0;
                $itemQty = 1;

                if ($itemQtyRef >= $itemQty) {
                    continue;
                }

                if ($itemQty > $qtyAvailable) {
                    $itemQty = $qtyAvailable;
                }

                $price = $creditmemoItem->getPriceInclTax();
                $tax = $creditmemoItem->getTaxAmount();

                $entry = [
                    'item_id' => $orderItemId,
                    'qty'     => $itemQty,
                    'prices'  => [
                        'product' => $price,
                    ],
                    'taxes'   => [
                        'product' => $tax,
                    ],
                ];

                if ($item->isBuyerCancellationRequested()
                    && $item->isBuyerCancellationPossible()
                ) {
                    $entry['is_buyer_cancellation'] = true;
                }

                $itemsForCancel[] = $entry;

                $qtyAvailable -= $itemQty;
                $data['refunded_qty'][$orderItemId] = $itemQty;

                $additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'][$i] = $data;
                $mergedOrderItemId && $i--;
            }

            $creditmemoItem->getOrderItem()->setAdditionalData(
                $this->getHelper('Data')->serialize($additionalData)
            );
            $creditmemoItem->getOrderItem()->save();
        }

        return $itemsForCancel;
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
