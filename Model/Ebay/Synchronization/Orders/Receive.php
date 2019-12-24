<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Orders;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Orders\Receive
 */
class Receive extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Ebay\Order\BuilderFactory  */
    protected $orderBuilderFactory;

    /** @var int|float */
    protected $percentForOneAccount;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Order\BuilderFactory $orderBuilderFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->orderBuilderFactory = $orderBuilderFactory;
        parent::__construct($ebayFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/receive/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Orders Receive';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
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

        $iteration = 1;
        $this->countPercentsForOneAccount($permittedAccounts);

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            try {
                $this->processAccount($account, $iteration);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->activateAfterAccountProcessed($account, $iteration);
            $iteration++;
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    private function countPercentsForOneAccount(array $permittedAccounts)
    {
        $this->percentForOneAccount = $this->getPercentsInterval() / count($permittedAccounts);
    }

    //########################################

    private function processAccount(\Ess\M2ePro\Model\Account $account, $iteration)
    {
        $this->setDataReceivingState($account);

        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();
        $ebayData = $this->receiveEbayOrdersData($account);
        $this->updateJobToken($ebayAccount, $ebayData);

        if (empty($ebayData['items'])) {
            return;
        }

        $this->getActualLockItem()->activate();
        $processedEbayOrders = $this->processEbayOrders($account, $ebayData['items']);

        $this->setOrderCreationState($account, $iteration);

        if (!empty($processedEbayOrders)) {
            $percentsForOneOrder = (int)(($this->getPercentsStart() + $iteration * $this->percentForOneAccount * 0.7)
                / count($processedEbayOrders));

            $this->createMagentoOrders($processedEbayOrders, $percentsForOneOrder);
        }

        $ebayAccount->setData('orders_last_synchronization', $ebayData['to_update_date']);
        $ebayAccount->save();
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array
     */
    private function receiveEbayOrdersData(\Ess\M2ePro\Model\Account $account)
    {
        $params = $this->prepareConnectorParams($account);

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObj */
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Receive\Items $connectorObj */
        $connectorObj = $dispatcherObj->getCustomConnector(
            'Ebay_Connector_Order_Receive_Items',
            $params,
            null,
            $account
        );

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getId());

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

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array
     */
    private function prepareConnectorParams(\Ess\M2ePro\Model\Account $account)
    {
        $toTime   = $this->prepareToTime();
        $fromTime = $this->prepareFromTime($account, $toTime);

        $params = [
            'from_update_date' => $this->getHelper('Component\Ebay')->timeToString($fromTime),
            'to_update_date'   => $this->getHelper('Component\Ebay')->timeToString($toTime)
        ];

        $jobToken = $account->getChildObject()->getData('job_token');
        if (!empty($jobToken)) {
            $params['job_token'] = $jobToken;
        }

        return $params;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Account $ebayAccount
     * @param array $ebayData
     */
    private function updateJobToken(\Ess\M2ePro\Model\Ebay\Account $ebayAccount, array $ebayData)
    {
        if (!empty($ebayData['job_token'])) {
            $ebayAccount->setData('job_token', $ebayData['job_token']);
        } else {
            $ebayAccount->setData('job_token', null);
        }

        $ebayAccount->save();
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param array $ordersData
     * @return \Ess\M2ePro\Model\Order[]
     */
    private function processEbayOrders(\Ess\M2ePro\Model\Account $account, array $ordersData)
    {
        $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

        $orders = [];

        foreach ($ordersData as $ebayOrderData) {
            $orderCreateDate = new \DateTime($ebayOrderData['purchase_create_date'], new \DateTimeZone('UTC'));
            if ($orderCreateDate < $accountCreateDate) {
                continue;
            }

            /** @var $ebayOrder \Ess\M2ePro\Model\Ebay\Order\Builder */
            $ebayOrder = $this->orderBuilderFactory->create();
            $ebayOrder->initialize($account, $ebayOrderData);

            try {
                $orders[] = $ebayOrder->process();
            } catch (\Exception $exception) {
                continue;
            }
        }

        return array_filter($orders);
    }

    private function processResponseMessages(array $messages)
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

    private function createMagentoOrders($ebayOrders, $percentsForOneOrder)
    {
        $iteration = 0;
        $currentPercents = $this->getActualLockItem()->getPercents();

        foreach ($ebayOrders as $order) {
            /** @var $order \Ess\M2ePro\Model\Order */

            if ($this->isOrderChangedInParallelProcess($order)) {
                continue;
            }

            if ($iteration % 5 == 0) {
                $this->getActualLockItem()->activate();
            }

            $iteration++;

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

            if ($order->getChildObject()->canCreatePaymentTransaction()) {
                $order->getChildObject()->createPaymentTransactions();
            }
            if ($order->getChildObject()->canCreateInvoice()) {
                $order->createInvoice();
            }
            if ($order->getChildObject()->canCreateShipments()) {
                $order->createShipments();
            }
            if ($order->getChildObject()->canCreateTracks()) {
                $order->getChildObject()->createTracks();
            }
            if ($order->getStatusUpdateRequired()) {
                $order->updateMagentoOrderStatus();
            }

            $currentPercents = $currentPercents + $percentsForOneOrder * $iteration;
            $this->getActualLockItem()->setPercents($currentPercents);
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

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param \DateTime $toTime
     * @return \DateTime
     */
    private function prepareFromTime(\Ess\M2ePro\Model\Account $account, \DateTime $toTime)
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
        }
        // ---------------------------------------

        if ($sinceTime->getTimestamp() >= $toTime->getTimestamp()) {
            $sinceTime = clone $toTime;
            $sinceTime->modify('- 5 minutes');
        }

        return $sinceTime;
    }

    /**
     * @return \DateTime
     */
    private function prepareToTime()
    {
        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');

        if ($operationHistory !== null) {
            $synchStartDate = $operationHistory->getData('start_date');
            $toTime         = new \DateTime($synchStartDate, new \DateTimeZone('UTC'));
        } else {
            $toTime         = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return $toTime;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param int $iteration
     */
    private function setOrderCreationState(\Ess\M2ePro\Model\Account $account, $iteration)
    {
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getId());
        $this->getActualOperationHistory()->addTimePoint(
            __METHOD__.'create_magento_orders'.$account->getId(),
            'Create Magento Orders'
        );

        // M2ePro_TRANSLATIONS
        // The "Receive" Action for eBay Account "%account_title%" is in Order Creation state...
        $status = 'The "Receive" Action for eBay Account "%account_title%" is in Order Creation state...';
        $this->getActualLockItem()->setStatus(
            $this->getHelper('Module\Translation')->__($status, $account->getTitle())
        );

        $this->getActualLockItem()->setPercents(
            $this->getPercentsStart() + $iteration * $this->percentForOneAccount * 0.3
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     */
    private function setDataReceivingState(\Ess\M2ePro\Model\Account $account)
    {
        $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
        $this->getActualOperationHistory()->addTimePoint(__METHOD__.'get'.$account->getId(), 'Get Orders from eBay');

        // M2ePro\TRANSLATIONS
        // The "Receive" Action for eBay Account "%account_title%" is in data receiving state...
        $status = 'The "Receive" Action for eBay Account "%account_title%" is in data receiving state...';
        $this->getActualLockItem()->setStatus(
            $this->getHelper('Module\Translation')->__($status, $account->getTitle())
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param int $iteration
     */
    private function activateAfterAccountProcessed(\Ess\M2ePro\Model\Account $account, $iteration)
    {
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'create_magento_orders'.$account->getId());

        $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $this->percentForOneAccount);
        $this->getActualLockItem()->activate();
    }

    //########################################
}
