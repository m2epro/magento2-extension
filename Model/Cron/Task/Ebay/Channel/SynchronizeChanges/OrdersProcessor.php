<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges\OrdersProcessor
 */
class OrdersProcessor extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    protected $synchronizationLog = null;

    protected $receiveOrdersToDate = null;

    protected $ebayFactory;
    protected $activeRecordFactory;

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setSynchronizationLog(\Ess\M2ePro\Model\Synchronization\Log $log)
    {
        $this->synchronizationLog = $log;
        return $this;
    }

    public function setReceiveOrdersToDate($toDate)
    {
        $this->receiveOrdersToDate = $toDate;
        return $this;
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

    public function process()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection();

        foreach ($accountsCollection->getItems() as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            try {
                $this->processAccount($account);
            } catch (\Exception $e) {
                $this->getHelper('Module\Exception')->process($e);
                $this->synchronizationLog->addMessageFromException($e);
            }
        }
    }

    //########################################

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Ebay_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->synchronizationLog);

        $ebayData = $this->receiveEbayOrdersData($account);
        if (empty($ebayData)) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();

        if (!empty($ebayData['job_token'])) {
            $ebayAccount->setData('job_token', $ebayData['job_token']);
        } else {
            $ebayAccount->setData('job_token', null);
        }

        $processedEbayOrders = $ordersCreator->processEbayOrders($account, $ebayData['items']);
        $ordersCreator->processMagentoOrders($processedEbayOrders);

        $ebayAccount->setData('orders_last_synchronization', $ebayData['to_update_date']);
        $ebayAccount->save();
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array
     */
    protected function receiveEbayOrdersData(\Ess\M2ePro\Model\Account $account)
    {
        $toTime   = $this->prepareToTime();
        $fromTime = $this->prepareFromTime($account, $toTime);

        $params = [
            'from_update_date' => $this->getHelper('Component\Ebay')->timeToString($fromTime),
            'to_update_date'=> $this->getHelper('Component\Ebay')->timeToString($toTime)
        ];

        $jobToken = $account->getChildObject()->getData('job_token');
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
        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        if (!isset($responseData['items']) || !isset($responseData['to_update_date'])) {
            $logData = [
                'params'            => $params,
                'account_id'        => $account->getId(),
                'response_data'     => $responseData,
                'response_messages' => $connectorObj->getResponseMessages()
            ];
            $this->getHelper('Module\Logger')->process($logData, 'eBay orders receive task - empty response');

            return [];
        }

        return $responseData;
    }

    // ---------------------------------------

    protected function processResponseMessages(array $messages)
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

            $this->synchronizationLog->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param \DateTime $toTime
     * @return \DateTime
     */
    protected function prepareFromTime(\Ess\M2ePro\Model\Account $account, \DateTime $toTime)
    {
        $lastSynchronizationDate = $account->getChildObject()->getData('orders_last_synchronization');

        if ($lastSynchronizationDate === null) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        } else {
            $sinceTime = new \DateTime($lastSynchronizationDate, new \DateTimeZone('UTC'));

            // Get min date for synch
            // ---------------------------------------
            $minDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $minDate->modify('-90 days');
            // ---------------------------------------

            // Prepare last date
            // ---------------------------------------
            if ((int)$sinceTime->format('U') < (int)$minDate->format('U')) {
                $sinceTime = $minDate;
            }

            // ---------------------------------------
        }

        if ($sinceTime->getTimestamp() >= $toTime->getTimeStamp()) {
            $sinceTime = clone $toTime;
            $sinceTime->modify('- 5 minutes');
        }

        return $sinceTime;
    }

    /**
     * @return \DateTime
     */
    protected function prepareToTime()
    {
        if ($this->receiveOrdersToDate !== null) {
            $toTime = new \DateTime($this->receiveOrdersToDate, new \DateTimeZone('UTC'));
        } else {
            $toTime = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return $toTime;
    }

    //########################################
}
