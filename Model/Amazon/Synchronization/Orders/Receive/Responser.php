<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ItemsResponser
{
    protected $orderBuilderFactory;
    protected $activeRecordFactory;

    protected $synchronizationLog = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\BuilderFactory $orderBuilderFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->orderBuilderFactory = $orderBuilderFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);
    }

    //########################################

    protected function processResponseMessages(array $messages = array())
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        $responseData = $this->getResponse()->getData();
        if ($this->getResponse()->getMessages()->hasErrorEntities() && !isset($responseData['items'])) {
            return false;
        }

        return true;
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module\Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    //########################################

    protected function processResponseData()
    {
        $accounts = $this->getAccountsByAccessTokens();
        $preparedResponseData = $this->getPreparedResponseData();

        $processedAmazonOrders = array();

        foreach ($preparedResponseData['orders'] as $accountAccessToken => $ordersData) {

            $amazonOrders = $this->processAmazonOrders($ordersData, $accounts[$accountAccessToken]);

            if (empty($amazonOrders)) {
                continue;
            }

            $processedAmazonOrders[] = $amazonOrders;
        }

        $merchantId = current($accounts)->getChildObject()->getMerchantId();

        if (!empty($preparedResponseData['job_token'])) {
            $this->modelFactory->getObject('Config\Manager\Synchronization')->setGroupValue(
                "/amazon/orders/receive/{$merchantId}/", "job_token", $preparedResponseData['job_token']
            );
        } else {
            $this->modelFactory->getObject('Config\Manager\Synchronization')->deleteGroupValue(
                "/amazon/orders/receive/{$merchantId}/", "job_token"
            );
        }

        $this->modelFactory->getObject('Config\Manager\Synchronization')->setGroupValue(
            "/amazon/orders/receive/{$merchantId}/", "from_update_date", $preparedResponseData['to_update_date']
        );

        foreach ($processedAmazonOrders as $amazonOrders) {
            try {

                $this->createMagentoOrders($amazonOrders);

            } catch (\Exception $exception) {

                $this->getSynchronizationLog()->addMessage(
                    $this->getHelper('Module\Translation')->__($exception->getMessage()),
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                );

                $this->getHelper('Module\Exception')->process($exception);
            }
        }
    }

    // ---------------------------------------

    private function processAmazonOrders(array $ordersData, \Ess\M2ePro\Model\Account $account)
    {
        $accountCreateDate = new \DateTime($account->getData('create_date'), new \DateTimeZone('UTC'));

        $orders = array();

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

        return $orders;
    }

    private function createMagentoOrders($amazonOrders)
    {
        foreach ($amazonOrders as $order) {
            /** @var $order \Ess\M2ePro\Model\Order */

            if ($this->isOrderChangedInParallelProcess($order)) {
                continue;
            }

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

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

            if ($order->getChildObject()->canCreateInvoice()) {
                $order->createInvoice();
            }
            if ($order->getChildObject()->canCreateShipment()) {
                $order->createShipment();
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

    private function getSynchronizationLog()
    {
        if (!is_null($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $this->synchronizationLog;
    }

    //########################################
}