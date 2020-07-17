<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\UploadByUser
 */
class UploadByUser extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/upload_by_user';

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
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
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Amazon_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->getSynchronizationLog());
        $ordersCreator->setValidateAccountCreateDate(false);

        foreach ($permittedAccounts as $merchantId => $accounts) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\UploadByUser\Manager $manager */
            $manager = $this->modelFactory->getObject('Cron_Task_Amazon_Order_UploadByUser_Manager');
            $manager->setIdentifier($merchantId);
            if (!$manager->isEnabled()) {
                continue;
            }

            try {
                $accountsByServerHash = [];
                foreach ($accounts as $account) {
                    $accountsByServerHash[$account->getChildObject()->getServerHash()] = $account;
                }

                $amazonData = $this->receiveAmazonOrdersData($manager, $merchantId, $accountsByServerHash);
                if (empty($amazonData)) {
                    continue;
                }

                !empty($amazonData['job_token'])
                    ? $manager->setJobToken($amazonData['job_token'])
                    : $manager->setJobToken(null);

                $processedAmazonOrders = [];
                foreach ($amazonData['items'] as $accountAccessToken => $ordersData) {
                    $amazonOrders = $ordersCreator->processAmazonOrders(
                        $accountsByServerHash[$accountAccessToken],
                        $ordersData
                    );

                    if (empty($amazonOrders)) {
                        continue;
                    }

                    $processedAmazonOrders[] = $amazonOrders;
                }

                foreach ($processedAmazonOrders as $amazonOrders) {
                    $ordersCreator->processMagentoOrders($amazonOrders);
                }

                $manager->setCurrentFromDate($amazonData['to_create_date']);

                if (empty($amazonData['job_token']) &&
                    $manager->getCurrentFromDate()->getTimestamp() >= $manager->getToDate()->getTimestamp()
                ) {
                    $manager->clear();
                }
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module_Translation')->__(
                    'The "Upload Orders By User" Action for Amazon Account "%merchant%" was completed with error.',
                    $merchantId
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        $accounts = [];
        foreach ($accountsCollection->getItems() as $accountItem) {
            /** @var $accountItem \Ess\M2ePro\Model\Account */

            $merchantId = $accountItem->getChildObject()->getMerchantId();
            if (!isset($accounts[$merchantId])) {
                $accounts[$merchantId] = [];
            }

            $accounts[$merchantId][] = $accountItem;
        }

        return $accounts;
    }

    //########################################

    protected function receiveAmazonOrdersData(
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\UploadByUser\Manager $manager,
        $merchantId,
        $accounts
    ) {
        $toTime   = $manager->getToDate();
        $fromTime = $manager->getCurrentFromDate();
        $fromTime === null && $fromTime = $manager->getFromDate();

        $params = [
            'accounts'         => $accounts,
            'from_create_date' => $fromTime->format('Y-m-d H:i:s'),
            'to_create_date'   => $toTime->format('Y-m-d H:i:s')
        ];

        $jobToken = $manager->getJobToken();
        if (!empty($jobToken)) {
            $params['job_token'] = $jobToken;
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime $connectorObj */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon_Connector_Orders_Get_Items',
            $params
        );

        $dispatcherObject->process($connectorObj);

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
