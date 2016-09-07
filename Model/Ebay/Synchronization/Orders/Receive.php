<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Orders;

final class Receive extends AbstractModel
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
        $fromTime = $this->prepareFromTime($account);
        $toTime   = $this->prepareToTime();

        if (strtotime($fromTime) >= strtotime($toTime)) {
            $fromTime = new \DateTime($toTime);
            $fromTime->modify('- 5 minutes');

            $fromTime = \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($fromTime);
        }

        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('sales', 'get', 'list',
                                                            array('from_time' => $fromTime, 'to_time' => $toTime),
                                                            NULL, NULL, $account);

        $dispatcherObj->process($connectorObj);
        $response = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getId());

        $ebayOrders = array();
        $toTime = $fromTime;

        if (isset($response['orders']) && isset($response['updated_to'])) {
            $ebayOrders = $response['orders'];
            $toTime = $response['updated_to'];
        }

        if (empty($ebayOrders)) {
            $this->saveLastUpdateTime($account, $toTime);
            return array();
        }

        $orders = array();

        foreach ($ebayOrders as $ebayOrderData) {
            /** @var $ebayOrder \Ess\M2ePro\Model\Ebay\Order\Builder */
            $ebayOrder = $this->orderBuilderFactory->create();
            $ebayOrder->initialize($account, $ebayOrderData);

            $orders[] = $ebayOrder->process();
        }

        $this->saveLastUpdateTime($account, $toTime);

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

            $purchaseDate = $order->getChildObject()->getPurchaseCreateDate();

            if (strtotime('2015-08-18 00:00:00') > strtotime($purchaseDate)) {
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

    //########################################

    private function prepareFromTime(\Ess\M2ePro\Model\Account $account)
    {
        $lastSynchronizationDate = $account->getChildObject()->getData('orders_last_synchronization');

        if (is_null($lastSynchronizationDate)) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $sinceTime =\Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($sinceTime);

            $this->saveLastUpdateTime($account, $sinceTime);

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

    private function saveLastUpdateTime(\Ess\M2ePro\Model\Account $account, $lastUpdateTime)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();
        $ebayAccount->setData('orders_last_synchronization', $lastUpdateTime)->save();
    }

    //########################################
}