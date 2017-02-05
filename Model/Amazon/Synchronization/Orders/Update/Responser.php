<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Update;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Update\ItemsResponser
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

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        foreach ($this->orders as $order) {
            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            $order->addErrorLog('Amazon Order status was not updated. Reason: %msg%', array('msg' => $messageText));
        }
    }

    // ########################################

    protected function processResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();

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

            $isFailed = false;

            foreach ($messagesSet->getEntities() as $message) {
                $this->orders[$orderId]->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

                if ($message->isError()) {
                    $isFailed = true;
                    $this->orders[$orderId]->addErrorLog(
                        'Amazon Order status was not updated. Reason: %msg%',
                        array('msg' => $message->getText())
                    );
                } else {
                    $this->orders[$orderId]->addWarningLog($message->getText());
                }
            }

            if ($isFailed) {
                $failedOrdersIds[] = $orderId;
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

            $this->orders[$orderId]->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            $this->orders[$orderId]->addSuccessLog('Amazon Order status was updated to Shipped.');

            if (empty($requestData['tracking_number']) || empty($requestData['carrier_name'])) {
                continue;
            }

            $this->orders[$orderId]->addSuccessLog(
                'Tracking number "%num%" for "%code%" has been sent to Amazon.',
                [
                    '!num' => $requestData['tracking_number'],
                    'code' => $requestData['carrier_name']
                ]
            );
        }
        //----------------------
    }

    // ########################################

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