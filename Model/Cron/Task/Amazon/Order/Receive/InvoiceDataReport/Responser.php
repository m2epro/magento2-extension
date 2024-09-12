<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceDataReport;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceDataReport\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
    protected $synchronizationLog = null;
    private \Ess\M2ePro\Model\Magento\Order\Updater $orderUpdater;

    public function __construct(
        \Ess\M2ePro\Model\Magento\Order\Updater $orderUpdater,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = []
    ) {
        $this->orderUpdater = $orderUpdater;

        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );
    }

    protected function processResponseMessages(array $messages = []): void
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                (string)__($message->getText()),
                $logType
            );
        }
    }

    protected function isNeedProcessResponse(): bool
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    public function failDetected($messageText): void
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            (string)__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    protected function processResponseData(): void
    {
        $responseData = $this->getPreparedResponseData();
        $responseData = $responseData['data'];

        $amazonOrdersIds = array_keys($responseData);
        if (empty($amazonOrdersIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $ordersCollection */
        $ordersCollection = $this->amazonFactory->getObject('Order')->getCollection();
        $ordersCollection->addFieldToFilter('amazon_order_id', ['in' => $amazonOrdersIds]);

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $orders = $ordersCollection->getItems();

        foreach ($responseData as $amazonOrderId => $orderData) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = null;

            foreach ($orders as $orderEntity) {
                if ($orderEntity->getChildObject()->getAmazonOrderId() == $amazonOrderId) {
                    $order = $orderEntity;
                    break;
                }
            }

            if ($order === null) {
                continue;
            }

            $buyerVatNumber = $orderData['buyer-vat-number'] ?? null;

            if (!empty($buyerVatNumber)) {
                if (empty($order->getChildObject()->getTaxRegistrationId())) {
                    $order->getChildObject()->setTaxRegistrationId($buyerVatNumber);

                    $magentoOrder = $order->getMagentoOrder();
                    if ($magentoOrder !== null) {
                        $this->orderUpdater->setMagentoOrder($magentoOrder);
                        $this->orderUpdater->updateShippingAddress(['vat_id' => $buyerVatNumber]);
                        $this->orderUpdater->updateBillingAddress(['vat_id' => $buyerVatNumber]);
                        $this->orderUpdater->finishUpdate();
                    }
                }
            }

            $order->getChildObject()->setSettings('invoice_data_report', $orderData);
            $order->getChildObject()->save();

            $order->getChildObject()->sendInvoiceFromReport();
        }
    }

    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization_Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $this->synchronizationLog;
    }
}
