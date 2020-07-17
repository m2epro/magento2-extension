<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

use Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Receive
 */
class Receive extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/order/receive';

    //####################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    //########################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    protected function performActions()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(Walmart::NICK, 'Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Walmart_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->getSynchronizationLog());

        foreach ($accountsCollection->getItems() as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            try {
                $responseData = $this->receiveWalmartOrdersData($account);
                if (empty($responseData)) {
                    continue;
                }

                $processedWalmartOrders = $ordersCreator->processWalmartOrders($account, $responseData['items']);
                $ordersCreator->processMagentoOrders($processedWalmartOrders);

                $account->getChildObject()->setData('orders_last_synchronization', $responseData['to_create_date']);
                $account->getChildObject()->save();
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module_Translation')->__(
                    'The "Receive" Action for Walmart Account "%title%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array|null
     * @throws \Exception
     */
    protected function receiveWalmartOrdersData(\Ess\M2ePro\Model\Account $account)
    {
        $fromDate = $this->prepareFromDate($account->getChildObject()->getData('orders_last_synchronization'));
        $toDate = $this->prepareToDate();

        // ----------------------------------------

        if ($fromDate >= $toDate) {
            $fromDate = clone $toDate;
            $fromDate->modify('-5 minutes');
        }

        // ----------------------------------------

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $orders = [[]];
        $breakDate = null;

        // -------------------------------------

        do {
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'orders',
                'get',
                'items',
                [
                    'account'          => $account->getChildObject()->getServerHash(),
                    'from_create_date' => $fromDate->format('Y-m-d H:i:s'),
                    'to_create_date'   => $toDate->format('Y-m-d H:i:s')
                ]
            );
            $dispatcherObject->process($connectorObj);

            // ----------------------------------------

            $this->processResponseMessages($connectorObj->getResponseMessages());

            // ----------------------------------------

            $responseData = $connectorObj->getResponseData();
            if (!isset($responseData['items']) || !isset($responseData['to_create_date'])) {
                $this->getHelper('Module_Logger')->process(
                    [
                        'from_create_date'  => $fromDate->format('Y-m-d H:i:s'),
                        'to_create_date'    => $toDate->format('Y-m-d H:i:s'),
                        'account_id'        => $account->getId(),
                        'response_data'     => $responseData,
                        'response_messages' => $connectorObj->getResponseMessages()
                    ],
                    'Walmart orders receive task - empty response'
                );

                return [];
            }

            // ----------------------------------------

            $fromDate = new \DateTime($responseData['to_create_date'], new \DateTimeZone('UTC'));
            if ($breakDate !== null && $breakDate->getTimestamp() === $fromDate->getTimestamp()) {
                break;
            }

            $orders[] = $responseData['items'];
            $breakDate = $fromDate;

            if ($this->getHelper('Module')->isTestingManualEnvironment()) {
                break;
            }
        } while (!empty($responseData['items']));

        // ----------------------------------------

        return [
            'items'          => call_user_func_array('array_merge', $orders),
            'to_create_date' => $responseData['to_create_date']
        ];
    }

    protected function processResponseMessages(array $messages = [])
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                                           : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module_Translation')->__($message->getText()),
                $logType
            );
        }
    }

    //########################################

    /**
     * @param \DateTime $minPurchaseDateTime
     * @return \DateTime|null
     * @throws \Exception
     */
    protected function getMinPurchaseDateTime(\DateTime $minPurchaseDateTime)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->parentFactory->getObject(Walmart::NICK, 'Order')->getCollection();
        $collection->addFieldToFilter(
            'status',
            [
                'from' => \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED,
                'to'   => \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY
            ]
        );
        $collection->addFieldToFilter(
            'purchase_create_date',
            ['from' => $minPurchaseDateTime->format('Y-m-d H:i:s')]
        );
        $collection->getSelect()->limit(1);

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $collection->getFirstItem();
        if ($order->getId() === null) {
            return null;
        }

        $purchaseDateTime = new \DateTime(
            $order->getChildObject()->getPurchaseCreateDate(),
            new \DateTimeZone('UTC')
        );
        $purchaseDateTime->modify('-1 second');

        return $purchaseDateTime;
    }

    //####################################

    /**
     * @param mixed $lastFromDate
     * @return \DateTime
     * @throws \Exception
     */
    protected function prepareFromDate($lastFromDate)
    {
        $nowDateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        // ----------------------------------------

        if (!empty($lastFromDate)) {
            $lastFromDate = new \DateTime($lastFromDate, new \DateTimeZone('UTC'));
        }

        if (empty($lastFromDate)) {
            $lastFromDate = clone $nowDateTime;
        }

        // ----------------------------------------

        $minDateTime = clone $nowDateTime;
        $minDateTime->modify('-1 day');

        if ($lastFromDate > $minDateTime) {
            $minPurchaseDateTime = $this->getMinPurchaseDateTime($minDateTime);
            if ($minPurchaseDateTime !== null) {
                $lastFromDate = $minPurchaseDateTime;
            }
        }

        // ----------------------------------------

        $minDateTime = clone $nowDateTime;
        $minDateTime->modify('-30 days');

        if ((int)$lastFromDate->format('U') < (int)$minDateTime->format('U')) {
            $lastFromDate = $minDateTime;
        }

        // ---------------------------------------

        return $lastFromDate;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    protected function prepareToDate()
    {
        $operationHistory = $this->getOperationHistory()->getParentObject('cron_runner');
        $toDate = $operationHistory !== null ? $operationHistory->getData('start_date') : 'now';

        return new \DateTime($toDate, new \DateTimeZone('UTC'));
    }

    //########################################
}
