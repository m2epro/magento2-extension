<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order;

use \Ess\M2ePro\Helper\Component\Ebay;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser
 */
class UploadByUser extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/order/upload_by_user';

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
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
        $accountsCollection = $this->parentFactory->getObject(Ebay::NICK, 'Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Ebay_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->getSynchronizationLog());
        $ordersCreator->setValidateAccountCreateDate(false);

        foreach ($accountsCollection->getItems() as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            /** @var \Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser\Manager $manager */
            $manager = $this->modelFactory->getObject('Cron_Task_Ebay_Order_UploadByUser_Manager');
            $manager->setIdentifierByAccount($account);
            if (!$manager->isEnabled()) {
                continue;
            }

            try {
                $ebayData = $this->receiveEbayOrdersData($manager, $account);
                if (empty($ebayData)) {
                    continue;
                }

                !empty($ebayData['job_token'])
                    ? $manager->setJobToken($ebayData['job_token'])
                    : $manager->setJobToken(null);

                $processedEbayOrders = $ordersCreator->processEbayOrders($account, $ebayData['items']);
                $ordersCreator->processMagentoOrders($processedEbayOrders);

                $manager->setCurrentFromDate($ebayData['to_create_date']);

                if (empty($amazonData['job_token']) &&
                    $manager->getCurrentFromDate()->getTimestamp() >= $manager->getToDate()->getTimestamp()
                ) {
                    $manager->clear();
                }
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module_Translation')->__(
                    'The "Upload Orders By User" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    //########################################

    protected function receiveEbayOrdersData(
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser\Manager $manager,
        \Ess\M2ePro\Model\Account $account
    ) {
        $toTime   = $manager->getToDate();
        $fromTime = $manager->getCurrentFromDate();
        $fromTime === null && $fromTime = $manager->getFromDate();

        $params = [
            'from_create_date' => $this->getHelper('Component\Ebay')->timeToString($fromTime),
            'to_create_date'   => $this->getHelper('Component\Ebay')->timeToString($toTime)
        ];

        $jobToken = $manager->getJobToken();
        if (!empty($jobToken)) {
            $params['job_token'] = $jobToken;
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime $connectorObj */
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getCustomConnector(
            'Ebay_Connector_Order_Receive_Items',
            $params,
            null,
            $account
        );

        $dispatcherObj->process($connectorObj);

        $this->processResponseMessages($connectorObj->getResponseMessages());

        return $connectorObj->getResponseData();
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
