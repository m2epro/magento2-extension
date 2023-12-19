<?php

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update;

class Status extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    public const REQUEST_PARAM_KEY = 'request';

    /** @var \Ess\M2ePro\Model\Order */
    private $order;
    /** @var bool */
    private $successfulUpdateStatus = false;

    protected function getCommand()
    {
        return ['orders', 'update', 'status'];
    }

    public function setOrderItem(\Ess\M2ePro\Model\Order\Item $orderItem): self
    {
        $this->order = $orderItem->getOrder();
        $this->account = $orderItem->getOrder()->getAccount();

        return $this;
    }

    public function getRequestData()
    {
        $request = $this->getParamsRequest();
        if (!empty($request->getItems())) {
            $items = [];
            foreach ($request->getItems() as $item) {
                $items[] = [
                    'item_id' => $item->getItemId(),
                    'transaction_id' => $item->getTransactionId(),
                    'tracking_number' => $item->getTrackingNumber(),
                    'carrier_code' => $item->getCarrierCode(),
                    'qty' => $item->getShippedQty(),
                ];
            }

            return [
                'action' => \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK,
                'order_id' => $request->getOrderId(),
                'items' => $items,
            ];
        }

        return [
            'action' => \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP,
            'item_id' => $request->getItemId(),
            'transaction_id' => $request->getTransactionId(),
        ];
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process()
    {
        parent::process();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if ($message->isError()) {
                $this->logErrorMessage($message->getText());
            }
        }
    }

    private function logErrorMessage(string $messageText): void
    {
        $message = 'Shipping status was not updated (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%';
        $request = $this->getParamsRequest();
        if (!empty($request->getItems())) {
            foreach ($request->getItems() as $requestItem) {
                $this->order->addErrorLog($message, [
                    '!item_id' => $requestItem->getItemId(),
                    '!trn_id' => $requestItem->getTransactionId(),
                    'msg' => $messageText,
                ]);
            }

            return;
        }

        $this->order->addErrorLog($message, [
            '!item_id' => $request->getItemId(),
            '!trn_id' => $request->getTransactionId(),
            'msg' => $messageText,
        ]);
    }

    protected function prepareResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->logStatusWasNotUpdated();

            return;
        }

        $this->logSuccessfulUpdateStatus();
        $this->successfulUpdateStatus = true;
    }

    private function logStatusWasNotUpdated(): void
    {
        $message = 'Shipping status was not updated (Item: %item_id%, Transaction: %trn_id%). Reason: eBay Failure.';

        $request = $this->getParamsRequest();
        if (!empty($request->getItems())) {
            foreach ($request->getItems() as $requestItem) {
                $this->order->addErrorLog($message, [
                    '!item_id' => $requestItem->getItemId(),
                    '!trn_id' => $requestItem->getTransactionId(),
                ]);
            }

            return;
        }

        $this->order->addErrorLog($message, [
            '!item_id' => $request->getItemId(),
            '!trn_id' => $request->getTransactionId(),
        ]);
    }

    private function logSuccessfulUpdateStatus(): void
    {
        $request = $this->getParamsRequest();
        if (!empty($request->getItems())) {
            $message = 'Tracking number "%num%" for "%code%" has been sent to eBay ' .
                '(Item: %item_id%, Transaction: %trn_id%).';

            foreach ($request->getItems() as $item) {
                $this->order->addSuccessLog($message, [
                    '!num' => $item->getTrackingNumber(),
                    'code' => $item->getCarrierCode(),
                    '!item_id' => $item->getItemId(),
                    '!trn_id' => $item->getTransactionId(),
                ]);
            }

            return;
        }

        $message = 'Order Item has been marked as Shipped (Item: %item_id%, Transaction: %trn_id%).';
        $this->order->addSuccessLog($message, [
            '!item_id' => $request->getItemId(),
            '!trn_id' => $request->getTransactionId(),
        ]);
    }

    private function getParamsRequest(): \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Request
    {
        return $this->params[self::REQUEST_PARAM_KEY];
    }

    public function isSuccessfulUpdateStatus(): bool
    {
        return $this->successfulUpdateStatus;
    }
}
