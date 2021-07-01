<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Creator
 */
class Creator extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    protected $_synchronizationLog;

    /** @var bool */
    protected $_validateAccountCreateDate = true;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setSynchronizationLog(\Ess\M2ePro\Model\Synchronization\Log $log)
    {
        $this->_synchronizationLog = $log;
    }

    public function setValidateAccountCreateDate($mode)
    {
        $this->_validateAccountCreateDate = $mode;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param array $ordersData
     * @return \Ess\M2ePro\Model\Order[]
     */
    public function processEbayOrders(\Ess\M2ePro\Model\Account $account, array $ordersData)
    {
        $orders = [];
        $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

        foreach ($ordersData as $ebayOrderData) {
            try {
                $orderCreateDate = new \DateTime($ebayOrderData['purchase_create_date'], new \DateTimeZone('UTC'));
                if ($this->_validateAccountCreateDate && $orderCreateDate < $accountCreateDate) {
                    continue;
                }

                /** @var $orderBuilder \Ess\M2ePro\Model\Ebay\Order\Builder */
                $orderBuilder = $this->modelFactory->getObject('Ebay_Order_Builder');
                $orderBuilder->initialize($account, $ebayOrderData);

                $order = $orderBuilder->process();
                $order && $orders[] = $order;
            } catch (\Exception $e) {
                $this->_synchronizationLog->addMessageFromException($e);
                $this->getHelper('Module_Exception')->process($e);
                continue;
            }
        }

        return array_filter($orders);
    }

    /**
     * @param \Ess\M2ePro\Model\Order[] $orders
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function processMagentoOrders($orders)
    {
        foreach ($orders as $order) {
            if ($this->isOrderChangedInParallelProcess($order)) {
                continue;
            }

            try {
                $this->createMagentoOrder($order);
            } catch (\Exception $e) {
                $this->_synchronizationLog->addMessageFromException($e);
                $this->getHelper('Module_Exception')->process($e);
                continue;
            }
        }
    }

    //########################################

    public function createMagentoOrder(\Ess\M2ePro\Model\Order $order)
    {
        if ($order->canCreateMagentoOrder()) {
            try {
                $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
                $order->addNoticeLog(
                    'Magento order creation rules are met. M2E Pro will attempt to create Magento order.'
                );
                $order->createMagentoOrder();
            } catch (\Exception $exception) {
                return;
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

        $order->createShipments();

        if ($order->getChildObject()->canCreateTracks()) {
            $order->getChildObject()->createTracks();
        }

        if ($order->getStatusUpdateRequired()) {
            $order->updateMagentoOrderStatus();
        }
    }

    /**
     * This is going to protect from Magento Orders duplicates.
     * (Is assuming that there may be a parallel process that has already created Magento Order)
     *
     * But this protection is not covering cases when two parallel cron processes are isolated by mysql transactions
     */
    public function isOrderChangedInParallelProcess(\Ess\M2ePro\Model\Order $order)
    {
        /** @var \Ess\M2ePro\Model\Order $dbOrder */
        $dbOrder = $this->activeRecordFactory->getObjectLoaded('Order', $order->getId());

        if ($dbOrder->getMagentoOrderId() != $order->getMagentoOrderId()) {
            return true;
        }

        return false;
    }

    //########################################
}