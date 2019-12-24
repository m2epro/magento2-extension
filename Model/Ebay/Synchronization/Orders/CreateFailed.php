<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Orders;

use Ess\M2ePro\Model\Order;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Orders\CreateFailed
 */
class CreateFailed extends AbstractModel
{
    const MAX_TRIES_TO_CREATE_ORDER = 3;

    protected $orderBuilderFactory;

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
        return '/create_failed/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Create Failed Orders';
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

            try {
                $this->getActualOperationHistory()->addText('Starting account "'.$account->getTitle().'"');

                // M2ePro_TRANSLATIONS
                // The "Create Failed Orders" Action for eBay Account "%title%" is in Order Creation state...
                $this->getActualLockItem()->setStatus($this->getHelper('Module\Translation')->__(
                    'The "Create Failed Orders" Action for eBay Account "%title%" is in Order Creation state...',
                    $account->getTitle()
                ));

                $ebayOrders = $this->getEbayOrders($account);

                if (!empty($ebayOrders)) {
                    $percentsForOneOrder = (int)(($this->getPercentsStart() + $iteration * $percentsForOneAcc)
                        / count($ebayOrders));

                    $this->createMagentoOrders($ebayOrders, $percentsForOneOrder);
                }
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Create Failed Orders" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
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

    //---------------------------------------

    private function getEbayOrders(\Ess\M2ePro\Model\Account $account)
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('-15 minutes');

        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('account_id', $account->getId());
        $collection->addFieldToFilter('magento_order_id', ['null' => true]);
        $collection->addFieldToFilter('magento_order_creation_failure', Order::MAGENTO_ORDER_CREATION_FAILED_YES);
        $collection->addFieldToFilter('magento_order_creation_fails_count', ['lt' => self::MAX_TRIES_TO_CREATE_ORDER]);
        $collection->addFieldToFilter(
            'magento_order_creation_latest_attempt_date',
            ['lt' => $backToDate->format('Y-m-d H:i:s')]
        );
        $collection->getSelect()->order('magento_order_creation_latest_attempt_date ASC');
        $collection->setPageSize(25);

        return $collection->getItems();
    }

    //---------------------------------------

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
                    $order->addNoticeLog(
                        'Magento order creation rules are met. M2E Pro will attempt to create Magento order.'
                    );
                    $order->createMagentoOrder();
                } catch (\Exception $exception) {
                    continue;
                }
            } else {
                $order->addData([
                    'magento_order_creation_failure'             => Order::MAGENTO_ORDER_CREATION_FAILED_NO,
                    'magento_order_creation_fails_count'         => 0,
                    'magento_order_creation_latest_attempt_date' => null
                ]);
                $order->save();

                continue;
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

            if ($iteration % 5 == 0) {
                $this->getActualLockItem()->activate();
            }

            $iteration++;
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
}
