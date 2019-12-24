<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update;

use \Ess\M2ePro\Model\Order as Order;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Status
 */
class Status extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    // M2ePro_TRANSLATIONS
    // Shipping status was not updated (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%
    // Shipping status was not updated (Item: %item_id%, Transaction: %trn_id%). Reason: eBay Failure.
    // Tracking number "%num%" for "%code%" has been sent to eBay (Item: %item_id%, Transaction: %trn_id%).
    // Order Item has been marked as Shipped (Item: %item_id%, Transaction: %trn_id%).

    /** @var $orderItem Order\Item */
    private $orderItem;
    private $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Order\Item $orderItem,
        array $params
    ) {
        parent::__construct(
            $marketplace,
            $account,
            $helperFactory,
            $modelFactory,
            $params
        );
        $this->activeRecordFactory = $activeRecordFactory;
        $this->orderItem           = ($orderItem->getId() !== null) ? $orderItem : null;
    }

    // ########################################

    public function setOrderItem(Order\Item $orderItem)
    {
        $this->orderItem = $orderItem;
        $this->account   = $orderItem->getOrder()->getAccount();

        return $this;
    }

    /**
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrderChangeId()
    {
        if (isset($this->params['change_id'])) {
            return (int)$this->params['change_id'];
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Order change id has not been set.');
    }

    // ########################################

    protected function getCommand()
    {
        return ['orders', 'update', 'status'];
    }

    protected function isNeedSendRequest()
    {
        return true;
    }

    protected function getRequestData()
    {
        $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP;

        if (!empty($this->params['tracking_number']) && !empty($this->params['carrier_code'])) {
            $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK;
        }

        $trackingNumber = !empty($this->params['tracking_number']) ? $this->params['tracking_number'] : null;
        $carrierCode    = !empty($this->params['carrier_code'])    ? $this->params['carrier_code']    : null;

        return [
            'account'         => $this->orderItem->getOrder()->getAccount()->getChildObject()->getServerHash(),
            'action'          => $action,
            'item_id'         => $this->orderItem->getChildObject()->getItemId(),
            'transaction_id'  => $this->orderItem->getChildObject()->getTransactionId(),
            'tracking_number' => $trackingNumber,
            'carrier_code'    => $carrierCode
        ];
    }

    // ########################################

    public function process()
    {
        if (!$this->isNeedSendRequest()) {
            return false;
        }

        parent::process();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            $messageText = 'Shipping status was not updated (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%';
            $this->orderItem->getOrder()->addErrorLog($messageText, [
                '!item_id' => $this->orderItem->getChildObject()->getItemId(),
                '!trn_id'  => $this->orderItem->getChildObject()->getTransactionId(),
                'msg'      => $message->getText(),
            ]);
        }
    }

    // ########################################

    protected function validateResponseData()
    {
        return true;
    }

    protected function prepareResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getResponse()->getResponseData();

        if (!isset($responseData['result']) || !$responseData['result']) {
            $message = 'Shipping status was not updated (Item: %item_id%, Transaction: %trn_id%). '.
                'Reason: eBay Failure.';
            $this->orderItem->getOrder()->addErrorLog($message, [
                '!item_id' => $this->orderItem->getChildObject()->getItemId(),
                '!trn_id'  => $this->orderItem->getChildObject()->getTransactionId(),
            ]);

            return;
        }

        if (!empty($this->params['tracking_number']) && !empty($this->params['carrier_code'])) {
            $message = 'Tracking number "%num%" for "%code%" has been sent to eBay '.
                '(Item: %item_id%, Transaction: %trn_id%).';
            $this->orderItem->getOrder()->addSuccessLog($message, [
                '!num' => $this->params['tracking_number'],
                'code' => $this->params['carrier_code'],
                '!item_id' => $this->orderItem->getChildObject()->getItemId(),
                '!trn_id'  => $this->orderItem->getChildObject()->getTransactionId(),
            ]);
        } else {
            $message = 'Order Item has been marked as Shipped (Item: %item_id%, Transaction: %trn_id%).';
            $this->orderItem->getOrder()->addSuccessLog($message, [
                '!item_id' => $this->orderItem->getChildObject()->getItemId(),
                '!trn_id'  => $this->orderItem->getChildObject()->getTransactionId(),
            ]);
        }

        $this->activeRecordFactory
             ->getObject('Order\Change')
             ->getResource()
             ->deleteByIds([$this->getOrderChangeId()]);
    }

    // ########################################
}
