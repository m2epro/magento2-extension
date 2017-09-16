<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Orders;

class Receive extends AbstractModel
{
    protected $orderBuilderFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Order\BuilderFactory $orderBuilderFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
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
        $percentsForOneAcc = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            $this->getActualOperationHistory()->addTimePoint(__METHOD__.'get'.$account->getId(),'Get Orders from eBay');

            // M2ePro\TRANSLATIONS
            // The "Receive" Action for eBay Account "%account_title%" is in data receiving state...
            $status = 'The "Receive" Action for eBay Account "%account_title%" is in data receiving state...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            try {

                $ebayOrders = $this->processEbayOrders($account);

                $this->getActualLockItem()->setPercents(
                    $this->getPercentsStart() + $iteration * $percentsForOneAcc * 0.3
                );

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
                // ---------------------------------------

                if (count($ebayOrders) > 0) {
                    $percentsForOneOrder = (int)(($this->getPercentsStart() + $iteration * $percentsForOneAcc * 0.7)
                        / count($ebayOrders));

                    $this->createMagentoOrders($ebayOrders, $percentsForOneOrder);
                }

            } catch (\Exception $exception) {

                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'create_magento_orders'.$account->getId());

            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneAcc);
            $this->getActualLockItem()->activate();
            // ---------------------------------------

            $iteration++;
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function processEbayOrders($account)
    {
        /** @var \Ess\M2ePro\Model\Account $account */

        $fromTime = $this->prepareFromTime($account);
        $toTime   = $this->prepareToTime();

        if (strtotime($fromTime) >= strtotime($toTime)) {
            $fromTime = new \DateTime($toTime);
            $fromTime->modify('- 5 minutes');

            $fromTime = \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($fromTime);
        }

        $params = array(
            'from_update_date' => $fromTime,
            'to_update_date'=> $toTime
        );

        $jobToken = $account->getChildObject()->getData('job_token');
        if (!empty($jobToken)) {
            $params['job_token'] = $jobToken;
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime $connectorObj */
        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getCustomConnector(
            'Ebay\Connector\Order\Receive\Items', $params, NULL, $account
        );

        $dispatcherObj->process($connectorObj);
        $response = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getId());

        if (!isset($response['items']) || !isset($response['to_update_date'])) {
            return array();
        }

        $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

        $orders = array();

        foreach ($response['items'] as $ebayOrderData) {

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

        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();

        if (!empty($response['job_token'])) {
            $ebayAccount->setData('job_token', $response['job_token']);
        } else {
            $ebayAccount->setData('job_token', NULL);
        }

        $ebayAccount->setData('orders_last_synchronization', $response['to_update_date']);
        $ebayAccount->save();

        return array_filter($orders);
    }

    private function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector\Connection\Response\Message\Set');
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
        $iteration = 1;
        $currentPercents = $this->getActualLockItem()->getPercents();

        foreach ($ebayOrders as $order) {
            /** @var $order \Ess\M2ePro\Model\Order */

            if ($this->isOrderChangedInParallelProcess($order)) {
                continue;
            }

            if ($order->canCreateMagentoOrder()) {
                try {
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
            if ($order->getChildObject()->canCreateShipment()) {
                $order->createShipment();
            }
            if ($order->getChildObject()->canCreateTracks()) {
                $order->getChildObject()->createTracks();
            }
            if ($order->getStatusUpdateRequired()) {
                $order->updateMagentoOrderStatus();
            }

            $currentPercents = $currentPercents + $percentsForOneOrder * $iteration;
            $this->getActualLockItem()->setPercents($currentPercents);

            if ($iteration % 5 == 0) {
                $this->getActualLockItem()->activate();
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

    private function prepareFromTime(\Ess\M2ePro\Model\Account $account)
    {
        $lastSynchronizationDate = $account->getChildObject()->getData('orders_last_synchronization');

        if (is_null($lastSynchronizationDate)) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $sinceTime =\Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($sinceTime);

            /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
            $ebayAccount = $account->getChildObject();
            $ebayAccount->setData('orders_last_synchronization', $sinceTime)->save();

            return $sinceTime;
        }

        $sinceTime = new \DateTime($lastSynchronizationDate, new \DateTimeZone('UTC'));

        // Get min date for synch
        // ---------------------------------------
        $minDate = new \DateTime('now',new \DateTimeZone('UTC'));
        $minDate->modify('-90 days');
        // ---------------------------------------

        // Prepare last date
        // ---------------------------------------
        if ((int)$sinceTime->format('U') < (int)$minDate->format('U')) {
            $sinceTime = $minDate;
        }
        // ---------------------------------------

        return \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($sinceTime);
    }

    private function prepareToTime()
    {
        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        if (!is_null($operationHistory)) {
            $toTime = $operationHistory->getData('start_date');
        } else {
            $toTime = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($toTime);
    }

    //########################################
}