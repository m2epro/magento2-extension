<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Update;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Update\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    /**
     * @var $order \Ess\M2ePro\Model\Order
     */
    protected $order = null;
    protected $action = null;

    private $status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace = null,
        \Ess\M2ePro\Model\Account $account = null,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $marketplace, $account, $params);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @return $this
     */
    public function setOrder(\Ess\M2ePro\Model\Order $order)
    {
        $this->order = $order;
        $this->account = $order->getAccount();
        $this->marketplace = $order->getMarketplace();

        return $this;
    }

    /**
     * @param $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    //----------------------------------------

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    //----------------------------------------

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

    //########################################

    /**
     * @return array
     */
    protected function getCommand()
    {
        return ['orders', 'update', 'status'];
    }

    //########################################

    /**
     * @return bool
     */
    protected function validateResponse()
    {
        return true;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process()
    {
        if (!$this->isNeedSendRequest()) {
            $this->status = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
            return;
        }

        /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
        $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->getOrderChangeId());
        $this->order->getLog()->setInitiator($orderChange->getCreatorType());

        parent::process();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            $this->status = \Ess\M2ePro\Helper\Data::STATUS_ERROR;

            $this->order->addErrorLog(
                'eBay Order status was not updated. Reason: %msg%',
                ['msg' => $message->getText()]
            );
        }
    }

    //----------------------------------------

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isNeedSendRequest()
    {
        if ($this->order->getMarketplace()->getCode() == 'India'
            && stripos($this->order->getChildObject()->getPaymentMethod(), 'paisa') !== false
        ) {
            /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
            $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->getOrderChangeId());
            $this->order->getLog()->setInitiator($orderChange->getCreatorType());
            $this->order->addErrorLog('eBay Order Status was not updated. Reason: %msg%', [
                'msg' => 'Status of India Site Orders cannot be updated if the Buyer uses PaisaPay payment method.'
            ]);

            $orderChange->delete();
            return false;
        }

        if (!in_array(
            $this->action,
            [
                \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_PAY,
                \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP,
                \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK
            ]
        )) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Action.');
        }

        return true;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRequestData()
    {
        return [
            'action' => $this->action,
            'order_id' => $this->order->getChildObject()->getEbayOrderId()
        ];
    }

    //########################################
}
