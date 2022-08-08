<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

class Receive extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/receive';

    /** @var bool */
    private $isErrorMessageReceived = false;

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

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $merchantId => $accounts) {
            /** @var \Ess\M2ePro\Model\Account $account **/

            try {
                $this->processAccounts($merchantId, $accounts);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive" Action for Amazon Account Merchant "%merchant%" was completed with error.',
                    $merchantId
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    protected function getPermittedAccounts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        $accounts = [];
        foreach ($accountsCollection->getItems() as $accountItem) {
            /** @var \Ess\M2ePro\Model\Account $accountItem */

            $merchantId = $accountItem->getChildObject()->getMerchantId();
            if (!isset($accounts[$merchantId])) {
                $accounts[$merchantId] = [];
            }

            $accounts[$merchantId][] = $accountItem;
        }

        return $accounts;
    }

    protected function processAccounts($merchantId, array $accounts)
    {
        $accountsByServerHash = [];
        foreach ($accounts as $account) {
            $accountsByServerHash[$account->getChildObject()->getServerHash()] = $account;
        }

        $preparedResponseData = $this->receiveAmazonOrdersData($merchantId, $accountsByServerHash);
        if (empty($preparedResponseData)) {
            return null;
        }

        if (!empty($preparedResponseData['job_token'])) {
            $this->getHelper('Module')->getRegistry()->setValue(
                "/amazon/orders/receive/{$merchantId}/job_token/",
                $preparedResponseData['job_token']
            );
        } else {
            $this->getHelper('Module')->getRegistry()->deleteValue("/amazon/orders/receive/{$merchantId}/job_token/");
        }

        /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Amazon_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->getSynchronizationLog());

        $processedAmazonOrders = [];
        foreach ($preparedResponseData['items'] as $accountAccessToken => $ordersData) {
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

        $this->getHelper('Module')->getRegistry()->setValue(
            "/amazon/orders/receive/{$merchantId}/from_update_date/",
            $preparedResponseData['to_update_date']
        );
    }

    protected function receiveAmazonOrdersData($merchantId, $accounts)
    {
        $updateSinceTime = $this->getHelper('Module')->getRegistry()->getValue(
            "/amazon/orders/receive/{$merchantId}/from_update_date/"
        );

        $fromDate = $this->prepareFromDate($updateSinceTime);
        $toDate   = $this->prepareToDate();

        $fromDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($fromDate)
            ->format('U');
        $toDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($toDate)
            ->format('U');
        if ($fromDateTimestamp >= $toDateTimestamp) {
            $fromDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $fromDate->modify('- 5 minutes');

            $fromDate = $fromDate->format('Y-m-d H:i:s');
        }

        $params = [
            'accounts'         => $accounts,
            'from_update_date' => $fromDate,
            'to_update_date'   => $toDate
        ];

        $jobToken = $this->getHelper('Module')->getRegistry()->getValue(
            "/amazon/orders/receive/{$merchantId}/job_token/"
        );

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
        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        if ($this->isErrorMessageReceived) {
            return [];
        }

        return $responseData;
    }

    protected function processResponseMessages(array $messages = [])
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        $this->isErrorMessageReceived = false;
        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            if ($message->isError()) {
                $logType = \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR;
                $this->isErrorMessageReceived = true;
            } else {
                $logType = \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;
            }

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    protected function prepareFromDate($lastFromDate)
    {
        // Get last from date
        // ---------------------------------------
        if (empty($lastFromDate)) {
            $lastFromDate = new \DateTime('now', new \DateTimeZone('UTC'));
        } else {
            $lastFromDate = new \DateTime($lastFromDate, new \DateTimeZone('UTC'));
        }

        // ---------------------------------------

        // Get min date for synch
        // ---------------------------------------
        $minDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDate->modify('-30 days');
        // ---------------------------------------

        // Prepare last date
        // ---------------------------------------
        if ((int)$lastFromDate->format('U') < (int)$minDate->format('U')) {
            $lastFromDate = $minDate;
        }

        // ---------------------------------------

        return $lastFromDate->format('Y-m-d H:i:s');
    }

    protected function prepareToDate()
    {
        $operationHistory = $this->getOperationHistory()->getParentObject('cron_runner');
        if ($operationHistory !== null) {
            $toDate = $operationHistory->getData('start_date');
        } else {
            $toDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $toDate = $toDate->format('Y-m-d H:i:s');
        }

        return $toDate;
    }
}
