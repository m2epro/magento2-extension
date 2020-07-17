<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

use \Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Order\UploadByUser
 */
class UploadByUser extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/order/upload_by_user';

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
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
        $ordersCreator->setValidateAccountCreateDate(false);

        foreach ($accountsCollection->getItems() as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            /** @var \Ess\M2ePro\Model\Cron\Task\Walmart\Order\UploadByUser\Manager $manager */
            $manager = $this->modelFactory->getObject('Cron_Task_Walmart_Order_UploadByUser_Manager');
            $manager->setIdentifierByAccount($account);
            if (!$manager->isEnabled()) {
                continue;
            }

            try {
                $responseData = $this->receiveWalmartOrdersData($manager, $account);
                if (empty($responseData)) {
                    continue;
                }

                $processedWalmartOrders = $ordersCreator->processWalmartOrders($account, $responseData['items']);
                $ordersCreator->processMagentoOrders($processedWalmartOrders);

                $manager->setCurrentFromDate($responseData['to_create_date']);

                if ($manager->getCurrentFromDate()->getTimestamp() >= $manager->getToDate()->getTimestamp() ||
                    empty($responseData['items'])
                ) {
                    $manager->clear();
                }
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module_Translation')->__(
                    'The "Upload Orders By User" Action for Walmart Account "%title%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    //########################################

    protected function receiveWalmartOrdersData(
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\UploadByUser\Manager $manager,
        \Ess\M2ePro\Model\Account $account
    ) {
        $toTime   = $manager->getToDate();
        $fromTime = $manager->getCurrentFromDate();
        $fromTime === null && $fromTime = $manager->getFromDate();

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $orders = [[]];
        $breakDate = null;

        do {
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'orders',
                'get',
                'items',
                [
                    'account'          => $account->getChildObject()->getServerHash(),
                    'from_create_date' => $fromTime->format('Y-m-d H:i:s'),
                    'to_create_date'   => $toTime->format('Y-m-d H:i:s')
                ]
            );
            $dispatcherObject->process($connectorObj);

            $this->processResponseMessages($connectorObj->getResponseMessages());

            $responseData = $connectorObj->getResponseData();

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
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    //########################################
}
