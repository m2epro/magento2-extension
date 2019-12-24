<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive
 */
class Receive extends AbstractModel
{
    protected $orderBuilderFactory;

    //########################################
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\BuilderFactory $orderBuilderFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->orderBuilderFactory = $orderBuilderFactory;
        parent::__construct($amazonFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    protected function getNick()
    {
        return '/receive/';
    }

    protected function getTitle()
    {
        return 'Receive';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $iteration = 0;
        $percentsForOneAccount = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $merchantId => $accounts) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            // ---------------------------------------
            $this->getActualOperationHistory()->addText('Starting Account "'.$merchantId.'"');
            $this->getActualOperationHistory()
                ->addTimePoint(__METHOD__.'process'.$merchantId, 'Get Orders from Amazon');

            $status = 'The "Receive" Action for Amazon Account Merchant "%merchant_id%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $merchantId)
            );
            // ---------------------------------------

            try {
                $this->processAccounts($merchantId, $accounts);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive" Action for Amazon Account Merchant "%merchant_id%" was completed with error.',
                    $merchantId
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__ . 'process'.$merchantId);

            $status = 'The "Receive" Action for Amazon Account Merchant: "%merchant_id%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $merchantId)
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneAccount);
            $this->getActualLockItem()->activate();
            // ---------------------------------------

            $iteration++;
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection();

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

    private function processAccounts($merchantId, array $accounts)
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
            $this->modelFactory->getObject('Config_Manager_Synchronization')->setGroupValue(
                "/amazon/orders/receive/{$merchantId}/",
                "job_token",
                $preparedResponseData['job_token']
            );
        } else {
            $this->modelFactory->getObject('Config_Manager_Synchronization')->deleteGroupValue(
                "/amazon/orders/receive/{$merchantId}/",
                "job_token"
            );
        }

        $this->getActualOperationHistory()->addTimePoint(
            __METHOD__.'create_magento_orders'.$merchantId,
            'Create Magento Orders'
        );

        $processedAmazonOrders = [];
        foreach ($preparedResponseData['items'] as $accountAccessToken => $ordersData) {
            $amazonOrders = $this->processAmazonOrders($ordersData, $accountsByServerHash[$accountAccessToken]);

            if (empty($amazonOrders)) {
                continue;
            }

            $processedAmazonOrders[] = $amazonOrders;

            $this->getActualLockItem()->activate();
        }

        foreach ($processedAmazonOrders as $amazonOrders) {
            try {
                $this->createMagentoOrders($amazonOrders);
            } catch (\Exception $exception) {
                $this->getLog()->addMessage(
                    $this->getHelper('Module\Translation')->__($exception->getMessage()),
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                );

                $this->getHelper('Module\Exception')->process($exception);
            }
        }

        $this->modelFactory->getObject('Config_Manager_Synchronization')->setGroupValue(
            "/amazon/orders/receive/{$merchantId}/",
            "from_update_date",
            $preparedResponseData['to_update_date']
        );
    }

    //########################################

    private function receiveAmazonOrdersData($merchantId, $accounts)
    {
        $updateSinceTime = $this->modelFactory->getObject('Config_Manager_Synchronization')->getGroupValue(
            "/amazon/orders/receive/{$merchantId}/",
            "from_update_date"
        );

        $fromDate = $this->prepareFromDate($updateSinceTime);
        $toDate = $this->prepareToDate();

        if (strtotime($fromDate) >= strtotime($toDate)) {
            $fromDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $fromDate->modify('- 5 minutes');

            $fromDate = $fromDate->format('Y-m-d H:i:s');
        }

        $params = [
            'accounts'         => $accounts,
            'from_update_date' => $fromDate,
            'to_update_date'   => $toDate
        ];

        $jobToken = $this->modelFactory->getObject('Config_Manager_Synchronization')->getGroupValue(
            "/amazon/orders/receive/{$merchantId}/",
            "job_token"
        );

        if (!empty($jobToken)) {
            $params['job_token'] = $jobToken;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\Items $connectorObj */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon_Connector_Orders_Get_Items',
            $params
        );
        $dispatcherObject->process($connectorObj);

        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$merchantId);

        if (!isset($responseData['items']) || !isset($responseData['to_update_date'])) {
            $this->helperFactory->getObject('Module\Logger')->process(
                [
                    'from_update_date'  => $fromDate,
                    'to_update_date'    => $toDate,
                    'jobToken'          => $jobToken,
                    'account_id'        => $merchantId,
                    'response_data'     => $responseData,
                    'response_messages' => $connectorObj->getResponseMessages()
                ],
                'Amazon orders receive task - empty response'
            );

            return [];
        }

        return $responseData;
    }

    private function processResponseMessages(array $messages = [])
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

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    // ---------------------------------------

    private function processAmazonOrders(array $ordersData, \Ess\M2ePro\Model\Account $account)
    {
        $orders = [];

        try {
            $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

            foreach ($ordersData as $orderData) {
                $orderCreateDate = new \DateTime($orderData['purchase_create_date'], new \DateTimeZone('UTC'));
                if ($orderCreateDate < $accountCreateDate) {
                    continue;
                }

                /** @var $orderBuilder \Ess\M2ePro\Model\Amazon\Order\Builder */
                $orderBuilder = $this->orderBuilderFactory->create();
                $orderBuilder->initialize($account, $orderData);

                try {
                    $order = $orderBuilder->process();
                } catch (\Exception $exception) {
                    continue;
                }

                if (!$order) {
                    continue;
                }

                $orders[] = $order;
            }
        } catch (\Exception $e) {
            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($exception);
        }

        return $orders;
    }

    private function createMagentoOrders($amazonOrders)
    {
        $iteration = 0;

        foreach ($amazonOrders as $order) {
            /** @var $order \Ess\M2ePro\Model\Order */

            if ($this->isOrderChangedInParallelProcess($order)) {
                continue;
            }

            if ($iteration % 5 == 0) {
                $this->getActualLockItem()->activate();
            }

            $iteration++;

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

            if ($order->canCreateMagentoOrder()) {
                try {
                    $order->addNoticeLog(
                        'Magento order creation rules are met. M2E Pro will attempt to create Magento order.'
                    );
                    $order->createMagentoOrder();
                } catch (\Exception $exception) {
                    continue;
                }
            }

            if ($order->getReserve()->isNotProcessed() && $order->isReservable()) {
                $order->getReserve()->place();
            }

            if ($order->getChildObject()->canCreateInvoice()) {
                $order->createInvoice();
            }
            if ($order->getChildObject()->canCreateShipments()) {
                $order->createShipments();
            }
            if ($order->getStatusUpdateRequired()) {
                $order->updateMagentoOrderStatus();
            }
        }
    }

    /**
     * This is going to protect from Magento Orders duplicates.
     * (Is assuming that there may be a parallel process that has already created Magento Order)
     *
     * But this protection is not covering a cases when two parallel cron processes are isolated by mysql transactions
     */
    private function isOrderChangedInParallelProcess(\Ess\M2ePro\Model\Order $order)
    {
        /** @var \Ess\M2ePro\Model\Order $dbOrder */
        $dbOrder = $this->activeRecordFactory->getObject('Order')->load($order->getId());

        if ($dbOrder->getMagentoOrderId() != $order->getMagentoOrderId()) {
            return true;
        }

        return false;
    }

    //########################################

    private function prepareFromDate($lastFromDate)
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

    private function prepareToDate()
    {
        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        if ($operationHistory !== null) {
            $toDate = $operationHistory->getData('start_date');
        } else {
            $toDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $toDate = $toDate->format('Y-m-d H:i:s');
        }

        return $toDate;
    }

    //########################################
}
