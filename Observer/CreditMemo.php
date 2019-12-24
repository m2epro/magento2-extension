<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

/**
 * Class \Ess\M2ePro\Observer\CreditMemo
 */
class CreditMemo extends AbstractModel
{
    protected $amazonFactory;
    protected $urlBuilder;
    protected $messageManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\Manager $messageManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        try {

            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $this->getEvent()->getCreditmemo();
            $magentoOrderId = $creditmemo->getOrderId();

            try {
                /** @var $order \Ess\M2ePro\Model\Order */
                $order = $this->activeRecordFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
            } catch (\Exception $e) {
                return;
            }

            if ($order === null) {
                return;
            }

            if ($order->getComponentMode() == \Ess\M2ePro\Helper\Component\Walmart::NICK) {
                $this->modelFactory->getObject('Walmart_Order_CreditMemo_Handler')->handle($order, $creditmemo);
                return;
            }

            if ($order->getComponentMode() != \Ess\M2ePro\Helper\Component\Amazon::NICK) {
                return;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
            $amazonOrder = $order->getChildObject();

            if (!$amazonOrder->canRefund()) {
                return;
            }

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

            $itemsForCancel = [];

            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                /** @var \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem */

                $additionalData = $creditmemoItem->getOrderItem()->getAdditionalData();
                if (!is_string($additionalData)) {
                    continue;
                }

                $additionalData = $this->getHelper('Data')->unserialize($additionalData);
                if (empty($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'])) {
                    continue;
                }

                foreach ($additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'] as $item) {
                    $amazonOrderItemId = $item['order_item_id'];

                    if (in_array($amazonOrderItemId, $itemsForCancel)) {
                        continue;
                    }

                    $amazonOrderItemCollection = $this->amazonFactory
                                                      ->getObject('Order\Item')
                                                      ->getCollection();
                    $amazonOrderItemCollection->addFieldToFilter('amazon_order_item_id', $amazonOrderItemId);

                    /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
                    $orderItem = $amazonOrderItemCollection->getFirstItem();

                    if ($orderItem === null || !$orderItem->getId()) {
                        continue;
                    }

                    /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
                    $amazonOrderItem = $orderItem->getChildObject();

                    $price = $creditmemoItem->getPriceInclTax();
                    if ($price > $amazonOrderItem->getPrice()) {
                        $price = $amazonOrderItem->getPrice();
                    }

                    $tax = $creditmemoItem->getTaxAmount();
                    if ($tax > $amazonOrderItem->getTaxAmount()) {
                        $tax = $amazonOrderItem->getTaxAmount();
                    }

                    $itemsForCancel[] = [
                        'item_id'  => $amazonOrderItemId,
                        'qty'      => $creditmemoItem->getQty(),
                        'prices'   => [
                            'product' => $price,
                        ],
                        'taxes'    => [
                            'product' => $tax,
                        ],
                    ];
                }
            }

            $amazonOrder->refund($itemsForCancel);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }
    }

    //########################################
}
