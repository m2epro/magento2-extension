<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Update;

abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    // M2ePro\TRANSLATIONS
    // eBay Order Status was not updated. Reason: %msg%
    // Status of India Site Orders cannot be updated if the Buyer uses PaisaPay payment method.

    /**
     * @var $order \Ess\M2ePro\Model\Order
     */
    protected $order = NULL;
    protected $action = NULL;

    private $status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;

    protected $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($marketplace, $account, $helperFactory, $modelFactory, $params);
    }

    // ########################################

    public function setOrder(\Ess\M2ePro\Model\Order $order)
    {
        $this->order = $order;
        $this->account = $order->getAccount();

        return $this;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    //----------------------------------------

    public function getStatus()
    {
        return $this->status;
    }

    // ########################################

    protected function getCommand()
    {
        return array('orders', 'update', 'status');
    }

    // ########################################

    protected function validateResponse()
    {
        return true;
    }

    public function process()
    {
        if (!$this->isNeedSendRequest()) {
            $this->status = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
            return;
        }

        parent::process();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            $this->status = \Ess\M2ePro\Helper\Data::STATUS_ERROR;

            $this->order->addErrorLog(
                'eBay Order status was not updated. Reason: %msg%', array('msg' => $message->getText())
            );
        }
    }

    //----------------------------------------

    protected function isNeedSendRequest()
    {
        if ($this->order->getMarketplace()->getCode() == 'India'
            && stripos($this->order->getChildObject()->getPaymentMethod(), 'paisa') !== false
        ) {
            $this->order->addErrorLog('eBay Order Status was not updated. Reason: %msg%', array(
                'msg' => 'Status of India Site Orders cannot be updated if the Buyer uses PaisaPay payment method.'
            ));

            return false;
        }

        if (!in_array($this->action,array(
           \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_PAY,
           \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP,
           \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK
        ))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Action.');
        }

        return true;
    }

    protected function getRequestData()
    {
        $requestData = array('action' => $this->action);

        $ebayOrderId = $this->order->getChildObject()->getData('ebay_order_id');

        if (strpos($ebayOrderId, '-') === false) {
            $requestData['order_id'] = $ebayOrderId;
        } else {
            $orderIdParts = explode('-', $ebayOrderId);

            $requestData['item_id'] = $orderIdParts[0];
            $requestData['transaction_id'] = $orderIdParts[1];
        }

        return $requestData;
    }

    // ########################################
}