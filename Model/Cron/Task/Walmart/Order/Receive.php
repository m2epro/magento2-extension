<?php

/**
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
    public const NICK = 'walmart/order/receive';

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
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(Walmart::NICK, 'Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Walmart_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->getSynchronizationLog());

        foreach ($accountsCollection->getItems() as $account) {
            /** @var \Ess\M2ePro\Model\Account $account * */

            try {
                $responseData = $this->receiveWalmartOrdersData($account);
                if (empty($responseData)) {
                    continue;
                }

                $processedWalmartOrders = $ordersCreator->processWalmartOrders($account, $responseData['items']);
                $ordersCreator->processMagentoOrders($processedWalmartOrders);

                $account->getChildObject()->setData('orders_last_synchronization', $responseData['to_update_date']);
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
     *
     * @return array
     * @throws \Exception
     */
    protected function receiveWalmartOrdersData(\Ess\M2ePro\Model\Account $account): array
    {
        $fromDate = $this->prepareFromDate($account->getChildObject()->getData('orders_last_synchronization'));
        $toDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        // ----------------------------------------

        if ($fromDate >= $toDate) {
            $fromDate = clone $toDate;
            $fromDate->modify('-5 minutes');
        }

        // ----------------------------------------

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        // -------------------------------------

        $connectorObj = $dispatcherObject->getVirtualConnector(
            'orders',
            'get',
            'items',
            [
                'account' => $account->getChildObject()->getServerHash(),
                'from_update_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_update_date' => $toDate->format('Y-m-d H:i:s'),
            ]
        );
        $dispatcherObject->process($connectorObj);

        // ----------------------------------------

        $this->processResponseMessages($connectorObj->getResponseMessages());

        // ----------------------------------------

        $responseData = $connectorObj->getResponseData();
        if (!isset($responseData['items'])) {
            /** @var \Ess\M2ePro\Helper\Module\Logger $moduleLogger */
            $moduleLogger = $this->getHelper('Module_Logger');
            $moduleLogger->process(
                [
                    'from_update_date' => $fromDate->format('Y-m-d H:i:s'),
                    'to_update_date' => $toDate->format('Y-m-d H:i:s'),
                    'account_id' => $account->getId(),
                    'response_data' => $responseData,
                    'response_messages' => $connectorObj->getResponseMessages(),
                ],
                'Walmart orders receive task - empty response'
            );

            return [];
        }

        return [
            'items' => $responseData['items'],
            'to_create_date' => $responseData['to_create_date'] ?? $toDate->format('Y-m-d H:i:s'),
            'to_update_date' => count($responseData['items']) > 0
                ? $responseData['to_create_date'] ?? $toDate->format('Y-m-d H:i:s')
                : $toDate->format('Y-m-d H:i:s'),
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

    /**
     * @param mixed $lastFromDate
     *
     * @return \DateTime
     * @throws \Exception
     */
    protected function prepareFromDate($lastFromDate): \DateTime
    {
        $nowDateTime = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        if (!empty($lastFromDate)) {
            $lastFromDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastFromDate);
        } else {
            $lastFromDate = clone $nowDateTime;
            $lastFromDate = $lastFromDate->modify('-1 day');
        }

        return $lastFromDate;
    }

    //########################################
}
