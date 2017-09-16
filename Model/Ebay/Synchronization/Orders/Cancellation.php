<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Orders;

class Cancellation extends AbstractModel
{
    private $orderHelper;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Order\Helper $orderHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->orderHelper = $orderHelper;
        parent::__construct($ebayFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/cancellation/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Cancellation';
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

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function intervalIsEnabled()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function intervalIsLocked()
    {
        if ($this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_USER ||
            $this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return false;
        }

        return parent::intervalIsLocked();
    }

    //########################################

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $iteration = 1;
        $percentsForOneStep = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

            // M2ePro\TRANSLATIONS
            // The "Cancellation" Action for eBay Account: "%account_title%" is started. Please wait...
            $status = 'The "Cancellation" Action for eBay Account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            try {

                $this->processAccount($account);

            } catch (\Exception $exception) {

                $message = $this->getHelper('Module\Translation')->__(
                    'The "Cancellation" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // M2ePro\TRANSLATIONS
            // The "Cancellation" Action for eBay Account: "%account_title%" is finished. Please wait...
            $status = 'The "Cancellation" Action for eBay Account: "%account_title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
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

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        if (!$account->getChildObject()->shouldCreateMagentoOrderImmediately()
            || $account->getChildObject()->getMagentoOrdersReservationDays() <= 0
        ) {
            return;
        }

        $data = $this->getUnpaidOrdersUpdates($account);
        if (empty($data)) {
            return;
        }

        /** @var $cancellationCandidates \Ess\M2ePro\Model\Order[] */
        $cancellationCandidates = array();
        foreach ($data as $orderData) {
            $cancellationCandidates[] = $this->associateAndUpdateOrder($account, $orderData);
        }

        $cancellationCandidates = array_filter($cancellationCandidates);
        if (empty($cancellationCandidates)) {
            return;
        }

        foreach ($cancellationCandidates as $order) {
            $this->processOrder($order);
        }
    }

    //########################################

    private function getUnpaidOrdersUpdates(\Ess\M2ePro\Model\Account $account)
    {
        $reservationDays = $account->getChildObject()->getMagentoOrdersReservationDays();
        list($startDate, $endDate) = $this->getDateRangeForUnpaidOrders($reservationDays);

        $ordersIds = $this->activeRecordFactory->getObject('Ebay\Order')->getResource()
            ->getCancellationCandidatesChannelIds($account->getId(), $startDate, $endDate);

        if (empty($ordersIds)) {
            return array();
        }

        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('orders', 'get', 'orders',
                                                            array('orders_ids' => $ordersIds),
                                                            NULL, NULL, $account);

        $dispatcherObj->process($connectorObj);
        $response = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        return isset($response['orders']) ? $response['orders'] : array();
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

    private function getDateRangeForUnpaidOrders($reservationDays)
    {
        $reservationDays = (int)$reservationDays;

        if ($reservationDays < 1) {
            throw new \InvalidArgumentException('Reservation period cannot be less than 1 day.');
        }

        $endDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $endDate->modify("-{$reservationDays} days");

        $startDate = clone $endDate;
        $startDate->modify('-3 days');

        return array($startDate, $endDate);
    }

    private function associateAndUpdateOrder(\Ess\M2ePro\Model\Account $account, array $orderData)
    {
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->ebayFactory->getObject('Order')->getCollection()
            ->addFieldToFilter('account_id', $account->getId())
            ->addFieldToFilter('ebay_order_id', $orderData['identifiers']['ebay_order_id'])
            ->getFirstItem();

        if (!$order->getId()) {
            return null;
        }

        $order->setAccount($account);

        $checkoutStatus = $this->getCheckoutStatus($orderData);
        $paymentStatus = $this->getPaymentStatus($orderData);
        $shippingStatus = $this->getShippingStatus($orderData);

        if ($paymentStatus ==\Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED) {
            $paymentDetails = $orderData['payment'];
            unset($paymentDetails['external_transactions']);

            $paymentDetails['method'] = $this->orderHelper->getPaymentMethodNameByCode(
                $paymentDetails['method'], $order->getMarketplaceId()
            );

            $order->setData('payment_details', $this->getHelper('Data')->jsonEncode($paymentDetails));
            $order->setData('payment_status', $paymentStatus);
        }

        if (
            !$order->getChildObject()->isCheckoutCompleted() &&
            $checkoutStatus == \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED
        ) {
            $shippingDetails = $orderData['shipping'];

            $shippingDetails['service'] = $this->orderHelper->getShippingServiceNameByCode(
                $shippingDetails['service'], $order->getMarketplaceId()
            );

            $order->setData('shipping_details', $this->getHelper('Data')->jsonEncode($shippingDetails));
            $order->setData('shipping_status', $shippingStatus);
            $order->setData('tax_details', $this->getHelper('Data')->jsonEncode($orderData['selling']['tax_details']));
        }

        $order->save();

        return $order;
    }

    //########################################

    private function processOrder(\Ess\M2ePro\Model\Order $order)
    {
        if ($order->getChildObject()->isPaymentCompleted()) {
            // unpaid order became paid
            // immediately created magento order should be canceled
            // and new magento order should be created instead

            if ($order->canCancelMagentoOrder()) {
                $message = 'Payment Status was updated to Paid on eBay. '.
                           'As Magento Order #%order_id% can have wrong data, it have to be cancelled.';
                $order->addWarningLog($message, array('!order_id' => $order->getMagentoOrder()->getRealOrderId()));

                try {
                    $order->cancelMagentoOrder();
                } catch (\Exception $e) {
                    // magento order was not cancelled
                    // do not create new magento order to prevent oversell
                    return;
                }
            }

            $this->clearOrder($order);
            $this->createMagentoOrder($order);
        } else {
            // unpaid order did not become paid
            // immediately created magento order should be canceled
            // and unpaid item process should be opened for each order item

            if ($order->canCancelMagentoOrder()) {
                $message = 'Payment Status was not updated to Paid. Magento Order #%order_id% '.
                           'have to be cancelled according to Account\'s Automatic Cancellation Setting.';
                $order->addWarningLog($message, array('!order_id' => $order->getMagentoOrder()->getRealOrderId()));

                try {
                    $order->cancelMagentoOrder();
                } catch (\Exception $e) {}
            }

            $this->openUnpaidItemProcess($order);
        }
    }

    private function createMagentoOrder(\Ess\M2ePro\Model\Order $order)
    {
        if ($order->canCreateMagentoOrder()) {
            try {
                $order->createMagentoOrder();
            } catch (\Exception $exception) {
                return;
            }
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
        $order->updateMagentoOrderStatus();
    }

    private function clearOrder(\Ess\M2ePro\Model\Order $order)
    {
        $order->setMagentoOrder(null);
        $order->setData('magento_order_id', null);
        $order->save();

        $order->getItemsCollection()->walk('setProduct', array(null));
    }

    //########################################

    private function openUnpaidItemProcess(\Ess\M2ePro\Model\Order $order)
    {
        $items = $this->getOrderItemsForUnpaidItemProcess($order);
        if (empty($items)) {
            return;
        }

        $action = \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher::ACTION_ADD_DISPUTE;
        $params = array(
            'explanation' => \Ess\M2ePro\Model\Ebay\Order\Item::DISPUTE_EXPLANATION_BUYER_HAS_NOT_PAID,
            'reason'      => \Ess\M2ePro\Model\Ebay\Order\Item::DISPUTE_REASON_BUYER_HAS_NOT_PAID
        );

        /** @var $dispatcher \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher */
        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\OrderItem\Dispatcher');
        $dispatcher->process($action, $items, $params);
    }

    private function getOrderItemsForUnpaidItemProcess(\Ess\M2ePro\Model\Order $order)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection $collection */
        $collection = $this->ebayFactory->getObject('Order\Item')->getCollection();
        $collection->addFieldToFilter('order_id', $order->getId());
        $collection->addFieldToFilter(
            'unpaid_item_process_state',\Ess\M2ePro\Model\Ebay\Order\Item::UNPAID_ITEM_PROCESS_NOT_OPENED
        );

        return $collection->getItems();
    }

    //########################################

    private function getCheckoutStatus($orderData)
    {
        return $this->orderHelper->getCheckoutStatus($orderData['statuses']['checkout']);
    }

    private function getPaymentStatus($orderData)
    {
        return $this->orderHelper->getPaymentStatus(
            $orderData['payment']['method'], $orderData['payment']['date'], $orderData['payment']['status']
        );
    }

    private function getShippingStatus($orderData)
    {
        return $this->orderHelper->getShippingStatus(
            $orderData['shipping']['date'], !empty($orderData['shipping']['service'])
        );
    }

    //########################################
}