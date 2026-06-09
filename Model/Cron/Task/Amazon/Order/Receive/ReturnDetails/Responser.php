<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\ReturnDetails;

use Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Requester as Requester;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Responser
{
    private \Ess\M2ePro\Model\Amazon\Order\Repository $amazonOrderRepository;
    private \Ess\M2ePro\Model\Order\Note\Repository $orderNoteRepository;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\Repository $amazonOrderRepository,
        \Ess\M2ePro\Model\Order\Note\Repository $orderNoteRepository,
        \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );
        $this->amazonOrderRepository = $amazonOrderRepository;
        $this->orderNoteRepository = $orderNoteRepository;
        $this->amazonAccountRepository = $amazonAccountRepository;
    }

    protected function processResponseData(): void
    {
        $returnOrders = $this->getPreparedResponseData();
        foreach ($returnOrders as $returnOrder) {
            $this->processReturnOrder($returnOrder);
        }

        $this->updateOrderReturnDataLastSynchronization();
    }

    private function processReturnOrder(
        \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Order $returnOrder
    ): void {
        $order = $this->amazonOrderRepository->findByAmazonOrderId($returnOrder->orderId);
        if ($order === null) {
            return;
        }

        $noteMessages = [];
        foreach ($returnOrder->items as $returnOrderItem) {
            $orderItem = $this->findOrderItem($order, $returnOrderItem);
            if ($orderItem === null) {
                continue;
            }

            $this->updateOrderItemReturnDetails($orderItem, $returnOrderItem);

            if ($this->isChangedReturnStatusToApproved($orderItem)) {
                $noteMessages[] = (string)__(
                    'Return was requested: SKU %sku, Reason %reason, Tracking ID %tracking_id',
                    [
                        'sku' => $returnOrderItem->sku,
                        'reason' => $returnOrderItem->returnReason,
                        'tracking_id' => $returnOrderItem->trackingId,
                    ]
                );
            }
        }

        if (empty($noteMessages)) {
            return;
        }

        $this->writeOrderNotes($order, $noteMessages);
    }

    private function findOrderItem(
        \Ess\M2ePro\Model\Order $order,
        \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Item $returnOrderItem
    ): ?\Ess\M2ePro\Model\Order\Item {
        foreach ($order->getItems() as $orderItem) {
            /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
            $amazonOrderItem = $orderItem->getChildObject();
            if ($amazonOrderItem->getAmazonOrderItemId() === $returnOrderItem->itemId) {
                return $orderItem;
            }
        }

        return null;
    }

    private function updateOrderItemReturnDetails(
        \Ess\M2ePro\Model\Order\Item $orderItem,
        \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Item $returnOrderItem
    ): void {
        /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
        $amazonOrderItem = $orderItem->getChildObject();
        $amazonOrderItem->setReturnDetails(
            $returnOrderItem->returnRequestDate,
            $returnOrderItem->returnRequestStatus,
            $returnOrderItem->trackingId,
            $returnOrderItem->returnQty,
            $returnOrderItem->resolution
        );
        $orderItem->save();
    }

    private function writeOrderNotes(\Ess\M2ePro\Model\Order $order, array $noteMessages): void
    {
        foreach ($noteMessages as $noteItem) {
            $this->orderNoteRepository->create((int)$order->getId(), $noteItem);
        }
    }

    private function isChangedReturnStatusToApproved(\Ess\M2ePro\Model\Order\Item $orderItem): bool
    {
        /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
        $amazonOrderItem = $orderItem->getChildObject();

        $oldData = $amazonOrderItem->getOriginReturnRequestStatus();
        $newData = $amazonOrderItem->getReturnRequestStatus();

        return $newData !== $oldData
            && $newData === \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\Item::STATUS_APPROVED;
    }

    public function updateOrderReturnDataLastSynchronization(): void
    {
        $amazonAccount = $this->amazonAccountRepository->get((int)$this->params['account_id']);

        $amazonAccount->setOrderReturnDataLastSynchronization($this->getStartProcessDate());
        $amazonAccount->save();
    }
}
