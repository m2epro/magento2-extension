<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Cancellation\ByBuyer;

use Ess\M2ePro\Model\Ebay\Order as EbayOrder;

class Manager
{
    public const ACTION_APPROVE = 'approve';
    public const ACTION_REJECT = 'reject';

    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $ebayConnectorDispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $ebayConnectorDispatcherFactory
    ) {
        $this->ebayConnectorDispatcherFactory = $ebayConnectorDispatcherFactory;
    }

    public function approve(\Ess\M2ePro\Model\Order $order, int $initiator): bool
    {
        $result = $this->processOnChannel($order, self::ACTION_APPROVE, $initiator);

        if ($result) {
            $order->getChildObject()->setBuyerCancellationStatus(
                EbayOrder::BUYER_CANCELLATION_STATUS_APPROVED
            );
            $order->getChildObject()->save();
        }

        return $result;
    }

    public function reject(\Ess\M2ePro\Model\Order $order, int $initiator): bool
    {
        $result =  $this->processOnChannel($order, self::ACTION_REJECT, $initiator);

        if ($result) {
            $order->getChildObject()->setBuyerCancellationStatus(
                EbayOrder::BUYER_CANCELLATION_STATUS_REJECTED
            );
            $order->getChildObject()->save();
        }

        return $result;
    }

    private function processOnChannel(\Ess\M2ePro\Model\Order $order, string $action, int $initiator): bool
    {
        if ($order->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($order->getComponentMode() !== \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method supports eBay orders only');
        }

        $dispatcher = $this->ebayConnectorDispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation\ByBuyer $connector */
        $connector = $dispatcher->getCustomConnector(
            'Ebay_Connector_Order_Cancellation_ByBuyer',
            [
                'action' => $action,
                'order_id' => $order->getChildObject()->getEbayOrderId(),
            ],
            $order->getMarketplace(),
            $order->getAccount()
        );

        $dispatcher->process($connector);

        $order->getLog()->setInitiator($initiator);
        $messages = $connector->getResponse()->getMessages() ?: [];

        $errorLogWritten = false;
        foreach ($messages->getEntities() as $message) {
            if ($message->isError()) {
                $act = $action === self::ACTION_APPROVE ?
                    'accepted' : 'declined';

                $order->addErrorLog(
                    "Order cancellation request was not $act. Reason: %msg%",
                    ['msg' => $message->getText()]
                );
                $errorLogWritten = true;
            } else {
                $order->addWarningLog($message->getText());
            }
        }

        $responseData = $connector->getResponseData();
        $result = (bool)$responseData['result'];

        if ($result) {
            $template = $action === self::ACTION_APPROVE ?
                'Order cancellation request was accepted.'
                : 'Order cancellation request was declined.';
            $order->addSuccessLog($template);
        } elseif (!$errorLogWritten) {
            $act = $action === self::ACTION_APPROVE ?
                'accepted' : 'declined';
            $order->addErrorLog("Order cancellation request was not $act.");
        }

        return $result;
    }
}
