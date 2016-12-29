<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Refund;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Refund\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Order[] $orders */
    private $orders = array();

    protected $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);

        $ordersIds = array();

        foreach ($this->params as $update) {
            if (!isset($update['order_id'])) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Order ID is not defined.');
            }

            $ordersIds[] = (int)$update['order_id'];
        }

        $this->orders = $this->activeRecordFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('id', array('in' => $ordersIds))
            ->getItems();
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        foreach ($this->getOrders() as $order) {
            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            $order->addErrorLog('Amazon Order status was not refunded. Reason: %msg%', array('msg' => $messageText));
        }
    }

    // ########################################

    protected function processResponseData()
    {
        /** @var $orders \Ess\M2ePro\Model\Order[] */
        $orders = $this->getOrders();

        $responseData = $this->getPreparedResponseData();

        // Check separate messages
        //----------------------
        $failedOrdersIds = array();

        foreach ($responseData['messages'] as $changeId => $messages) {
            $changeId = (int)$changeId;

            if ($changeId <= 0) {
                continue;
            }

            $orderId = $this->getOrderIdByChangeId($changeId);

            if (!is_numeric($orderId)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
            $messagesSet = $this->modelFactory->getObject('Connector\Connection\Response\Message\Set');
            $messagesSet->init($messages);

            $failedOrdersIds[] = $orderId;

            foreach ($messagesSet->getEntities() as $message) {
                $orders[$orderId]->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
                $orders[$orderId]->addErrorLog(
                    'Amazon Order was not cancelled. Reason: %msg%', array('msg' => $message->getText())
                );
            }
        }
        //----------------------

        //----------------------
        foreach ($this->params as $changeId => $requestData) {
            $orderId = $this->getOrderIdByChangeId($changeId);

            if (in_array($orderId, $failedOrdersIds)) {
                continue;
            }

            if (!is_numeric($orderId)) {
                continue;
            }

            $orders[$orderId]->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            $orders[$orderId]->addSuccessLog('Amazon Order status was refunded.');
        }
        //----------------------
    }

    // ########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @return \Ess\M2ePro\Model\Order[]
     */
    private function getOrders()
    {
        if (!is_null($this->orders)) {
            return $this->orders;
        }

        $ordersIds = array();

        foreach ($this->params as $update) {
            if (!isset($update['order_id'])) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Order ID is not defined.');
            }

            $ordersIds[] = (int)$update['order_id'];
        }

        $this->orders = $this->activeRecordFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('component_mode',\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->addFieldToFilter('id', array('in' => $ordersIds))
            ->getItems();

        return $this->orders;
    }

    private function getOrderIdByChangeId($changeId)
    {
        foreach ($this->params as $requestChangeId => $requestData) {
            if ($changeId == $requestChangeId && isset($requestData['order_id'])) {
                return $requestData['order_id'];
            }
        }

        return NULL;
    }

    // ########################################
}