<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Orders;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Orders\Receive
 */
class Receive extends AbstractModel
{
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

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            // ---------------------------------------
            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getTitle(),
                'Get Orders from Walmart'
            );

            $status = 'The "Receive" Action for Walmart Account "%title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            try {
                $responseData = $this->receiveWalmartOrdersData($account);
                if (empty($responseData)) {
                    continue;
                }

                $this->getActualOperationHistory()->addTimePoint(
                    __METHOD__.'create_magento_orders'.$account->getTitle(),
                    'Create Magento Orders'
                );

                $processedWalmartOrders = [];

                try {
                    $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

                    foreach ($responseData['items'] as $orderData) {
                        $orderCreateDate = new \DateTime($orderData['purchase_date'], new \DateTimeZone('UTC'));
                        if ($orderCreateDate < $accountCreateDate) {
                            continue;
                        }

                        /** @var $orderBuilder \Ess\M2ePro\Model\Walmart\Order\Builder */
                        $orderBuilder = $this->modelFactory->getObject('Walmart_Order_Builder');
                        $orderBuilder->initialize($account, $orderData);

                        $order = $orderBuilder->process();

                        if (!$order) {
                            continue;
                        }

                        $processedWalmartOrders[] = $order;
                    }
                } catch (\Exception $exception) {
                    $this->getLog()->addMessage(
                        $this->getHelper('Module\Translation')->__($exception->getMessage()),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                    );

                    $this->getHelper('Module\Exception')->process($exception);
                }

                foreach ($processedWalmartOrders as $walmartOrder) {
                    try {
                        $iteration = 0;

                        /** @var $walmartOrder \Ess\M2ePro\Model\Order */

                        if ($this->isOrderChangedInParallelProcess($walmartOrder)) {
                            continue;
                        }

                        $iteration++;

                        if ($iteration % 5 == 0) {
                            $this->getActualLockItem()->activate();
                        }

                        $walmartOrder->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

                        if ($walmartOrder->canCreateMagentoOrder()) {
                            try {
                                $message = 'Magento order creation rules are met.';
                                $message .= ' M2E Pro will attempt to create Magento order.';

                                $walmartOrder->addNoticeLog($message);
                                $walmartOrder->createMagentoOrder();
                            } catch (\Exception $exception) {
                                continue;
                            }
                        }

                        if ($walmartOrder->getReserve()->isNotProcessed() && $walmartOrder->isReservable()) {
                            $walmartOrder->getReserve()->place();
                        }

                        if ($walmartOrder->getChildObject()->canCreateInvoice()) {
                            $walmartOrder->createInvoice();
                        }
                        if ($walmartOrder->getChildObject()->canCreateShipments()) {
                            $walmartOrder->createShipments();
                        }
                        if ($walmartOrder->getStatusUpdateRequired()) {
                            $walmartOrder->updateMagentoOrderStatus();
                        }
                    } catch (\Exception $exception) {
                        $this->getLog()->addMessage(
                            $this->getHelper('Module\Translation')->__($exception->getMessage()),
                            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                        );

                        $this->getHelper('Module\Exception')->process($exception);
                    }
                }

                // ---------------------------------------

                $account->getChildObject()->setData('orders_last_synchronization', $responseData['to_create_date']);
                $account->getChildObject()->save();
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive" Action for Walmart Account "%title%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__ . 'process'.$account->getTitle());

            $status = 'The "Receive" Action for Walmart Account: "%title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
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
        $accountsCollection = $this->walmartFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array|null
     * @throws \Exception
     */
    private function receiveWalmartOrdersData(\Ess\M2ePro\Model\Account $account)
    {
        $fromDate = $this->prepareFromDate($account->getChildObject()->getData('orders_last_synchronization'));
        $toDate = $this->prepareToDate();

        // ----------------------------------------

        if ($fromDate >= $toDate) {
            $fromDate = clone $toDate;
            $fromDate->modify('-5 minutes');
        }

        // ----------------------------------------

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $orders = [[]];
        $breakDate = null;

        // -------------------------------------

        do {
            $connectorObj = $dispatcherObject->getVirtualConnector('orders', 'get', 'items', [
                'account'          => $account->getChildObject()->getData('server_hash'),
                'from_create_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_create_date'   => $toDate->format('Y-m-d H:i:s')
            ]);
            $dispatcherObject->process($connectorObj);

            // ----------------------------------------

            $this->processResponseMessages($connectorObj->getResponseMessages());
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__ . 'get' . $account->getTitle());

            // ----------------------------------------

            $responseData = $connectorObj->getResponseData();
            if (!isset($responseData['items']) || !isset($responseData['to_create_date'])) {
                $this->getHelper('Module\Logger')->process([
                    'from_create_date'  => $fromDate->format('Y-m-d H:i:s'),
                    'to_create_date'    => $toDate->format('Y-m-d H:i:s'),
                    'account_id'        => $account->getId(),
                    'response_data'     => $responseData,
                    'response_messages' => $connectorObj->getResponseMessages()
                ], 'Walmart orders receive task - empty response');

                return [];
            }

            // ----------------------------------------

            $fromDate = new \DateTime($responseData['to_create_date'], new \DateTimeZone('UTC'));
            if ($breakDate !== null && $breakDate->getTimestamp() === $fromDate->getTimestamp()) {
                break;
            }

            $orders[] = $responseData['items'];
            $breakDate = $fromDate;

            if ($this->helperFactory->getObject('Module')->isTestingEnvironment()) {
                break;
            }
        } while (!empty($responseData['items']));

        // ----------------------------------------

        return [
            'items'          => call_user_func_array('array_merge', $orders),
            'to_create_date' => $responseData['to_create_date']
        ];
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

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    // ---------------------------------------

    /**
     * This is going to protect from Magento Orders duplicates.
     * (Is assuming that there may be a parallel process that has already created Magento Order)
     *
     * But this protection is not covering a cases when two parallel cron processes are isolated by mysql transactions
     */
    private function isOrderChangedInParallelProcess(\Ess\M2ePro\Model\Order $order)
    {
        /** @var \Ess\M2ePro\Model\Order $dbOrder */
        $dbOrder = $this->activeRecordFactory->getObjectLoaded('Order', $order->getId());

        if ($dbOrder->getMagentoOrderId() != $order->getMagentoOrderId()) {
            return true;
        }

        return false;
    }

    //########################################

    /**
     * @param \DateTime $minPurchaseDateTime
     * @return \DateTime|null
     * @throws \Exception
     */
    private function getMinPurchaseDateTime(\DateTime $minPurchaseDateTime)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->walmartFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('status', [
            'from' => \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED,
            'to'   => \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY
        ]);
        $collection->addFieldToFilter('purchase_create_date', [
            'from' => $minPurchaseDateTime->format('Y-m-d H:i:s')
        ]);
        $collection->getSelect()->limit(1);

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $collection->getFirstItem();
        if ($order->getId() === null) {
            return null;
        }

        $purchaseDateTime = new \DateTime(
            $order->getChildObject()->getPurchaseCreateDate(),
            new \DateTimeZone('UTC')
        );
        $purchaseDateTime->modify('-1 second');

        return $purchaseDateTime;
    }

    //####################################

    /**
     * @param mixed $lastFromDate
     * @return \DateTime
     * @throws \Exception
     */
    private function prepareFromDate($lastFromDate)
    {
        $nowDateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        // ----------------------------------------

        if (!empty($lastFromDate)) {
            $lastFromDate = new \DateTime($lastFromDate, new \DateTimeZone('UTC'));
        }

        if (empty($lastFromDate)) {
            $lastFromDate = clone $nowDateTime;
        }

        // ----------------------------------------

        $minDateTime = clone $nowDateTime;
        $minDateTime->modify('-1 day');

        if ($lastFromDate > $minDateTime) {
            $minPurchaseDateTime = $this->getMinPurchaseDateTime($minDateTime);
            if ($minPurchaseDateTime !== null) {
                $lastFromDate = $minPurchaseDateTime;
            }
        }

        // ----------------------------------------

        $minDateTime = clone $nowDateTime;
        $minDateTime->modify('-30 days');

        if ((int)$lastFromDate->format('U') < (int)$minDateTime->format('U')) {
            $lastFromDate = $minDateTime;
        }

        // ---------------------------------------

        return $lastFromDate;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    private function prepareToDate()
    {
        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        $toDate = $operationHistory !== null ? $operationHistory->getData('start_date') : 'now';

        return new \DateTime($toDate, new \DateTimeZone('UTC'));
    }

    //########################################
}
