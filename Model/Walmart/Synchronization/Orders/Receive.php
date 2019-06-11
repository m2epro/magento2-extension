<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Orders;

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
            $this->getActualOperationHistory()
                 ->addTimePoint(__METHOD__.'process'.$account->getTitle(),'Get Orders from Walmart');

            $status = 'The "Receive" Action for Walmart Account "%title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            try {

                $preparedResponseData = $this->receiveWalmartOrdersData($account);

                if (empty($preparedResponseData)) {
                    continue;
                }

                $this->getActualOperationHistory()->addTimePoint(
                    __METHOD__.'create_magento_orders'.$account->getTitle(), 'Create Magento Orders'
                );

                $processedWalmartOrders = array();

                try {

                    $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

                    foreach ($preparedResponseData['items'] as $orderData) {

                        $orderCreateDate = new \DateTime($orderData['purchase_date'], new \DateTimeZone('UTC'));
                        if ($orderCreateDate < $accountCreateDate) {
                            continue;
                        }

                        /** @var $orderBuilder \Ess\M2ePro\Model\Walmart\Order\Builder */
                        $orderBuilder = $this->modelFactory->getObject('Walmart\Order\Builder');
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

    private function receiveWalmartOrdersData(\Ess\M2ePro\Model\Account $account)
    {
        $createSinceTime = $account->getChildObject()->getData('orders_last_synchronization');

        $fromDate = $this->prepareFromDate($createSinceTime);
        $toDate   = $this->prepareToDate();

        if (strtotime($fromDate) >= strtotime($toDate)) {
            $fromDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $fromDate->modify('- 5 minutes');

            $fromDate = $fromDate->format('Y-m-d H:i:s');
        }

        $requestData = array(
            'account'          => $account->getChildObject()->getServerHash(),
            'from_create_date' => $fromDate,
            'to_create_date'   => $toDate
        );

        $dispatcherObject = $this->modelFactory->getObject('Walmart\Connector\Dispatcher');
        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime $connectorObj */
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'orders', 'get', 'items', $requestData
        );

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getTitle());

        if (!isset($responseData['items']) || !isset($responseData['to_create_date'])) {
            $logData = array(
                'from_create_date'  => $fromDate,
                'to_create_date'    => $toDate,
                'account_id'        => $account->getId(),
                'response_data'     => $responseData,
                'response_messages' => $connectorObj->getResponseMessages()
            );
            $this->getHelper('Module\Logger')->process($logData, 'Walmart orders receive task - empty response');

            return array();
        } else {
            $account->getChildObject()->setData('orders_last_synchronization', $responseData['to_create_date'])->save();
        }

        return $responseData;
    }

    protected function processResponseMessages(array $messages = array())
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
        $minDate = new \DateTime('now',new \DateTimeZone('UTC'));
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
        if (!is_null($operationHistory)) {
            $toDate = $operationHistory->getData('start_date');
        } else {
            $toDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $toDate = $toDate->format('Y-m-d H:i:s');
        }

        return $toDate;
    }

    //########################################
}