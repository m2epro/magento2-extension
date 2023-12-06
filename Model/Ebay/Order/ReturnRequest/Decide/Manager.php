<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Order\ReturnRequest\Decide;

use Ess\M2ePro\Model\Ebay\Order as EbayOrder;

class Manager
{
    public const DECIDE_APPROVE = 'approve';
    public const DECIDE_DECLINE = 'decline';

    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $ebayConnectorDispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $ebayConnectorDispatcherFactory
    ) {
        $this->ebayConnectorDispatcherFactory = $ebayConnectorDispatcherFactory;
    }

    public function approve(\Ess\M2ePro\Model\Order $order, int $initiator): bool
    {
        $isSuccess = $this->processOnChannel($order, self::DECIDE_APPROVE, $initiator);

        if ($isSuccess) {
            if ($order->getChildObject()->isBuyerReturnRequested()) {
                $order->getChildObject()->setReturnRequestedStatus(
                    EbayOrder::BUYER_RETURN_REQUESTED_STATUS_APPROVED
                );
            }
            $order->getChildObject()->save();
        }

        return $isSuccess;
    }

    public function decline(\Ess\M2ePro\Model\Order $order, int $initiator): bool
    {
        $isSuccess =  $this->processOnChannel($order, self::DECIDE_DECLINE, $initiator);

        if ($isSuccess) {
            if ($order->getChildObject()->isBuyerReturnRequested()) {
                $order->getChildObject()->setReturnRequestedStatus(
                    EbayOrder::BUYER_RETURN_REQUESTED_STATUS_DECLINED
                );
            }
            $order->getChildObject()->save();
        }

        return $isSuccess;
    }

    private function processOnChannel(\Ess\M2ePro\Model\Order $order, string $decide, int $initiator): bool
    {
        if ($order->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($order->getComponentMode() !== \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method supports eBay orders only');
        }

        $dispatcher = $this->ebayConnectorDispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\ReturnRequest\Decide $connector */
        $connector = $dispatcher->getCustomConnector(
            'Ebay_Connector_Order_ReturnRequest_Decide',
            [
                'decision' => $decide,
                'order_id' => $order->getChildObject()->getEbayOrderId(),
            ],
            $order->getMarketplace(),
            $order->getAccount()
        );

        $dispatcher->process($connector);

        $order->getLog()->setInitiator($initiator);

        $responseData = $connector->getResponseData();
        $isSuccess = (bool)$responseData['result'];

        $messages = $connector->getResponse()->getMessages() ?: [];
        $this->processLog($order, $decide, $messages, $isSuccess);

        return $isSuccess;
    }

    private function processLog(
        \Ess\M2ePro\Model\Order $order,
        string $decide,
        \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messages,
        bool $isSuccess
    ): void {
        foreach ($messages->getEntities() as $message) {
            if ($message->isError()) {
                $act = $decide === self::DECIDE_APPROVE ?
                    'accepted' : 'declined';

                $order->addErrorLog(
                    (string)__(
                        "Order return request was not $act. Reason: %msg",
                        ['msg' => $message->getText()]
                    )
                );
            } else {
                $order->addWarningLog($message->getText());
            }
        }

        if ($isSuccess) {
            $template = $decide === self::DECIDE_APPROVE ?
                (string)__('Order return request was accepted.')
                : (string)__('Order return request was declined.');
            $order->addSuccessLog($template);

            return;
        }

        $act = $decide === self::DECIDE_APPROVE ? 'accepted' : 'declined';
        $order->addErrorLog((string)__("Order Return request was not $act."));
    }
}
