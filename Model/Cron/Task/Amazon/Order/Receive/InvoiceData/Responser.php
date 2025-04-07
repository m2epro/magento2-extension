<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceData;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceData\ItemsResponser
{
    private \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog;

    private \Ess\M2ePro\Model\Synchronization\LogFactory $synchronizationLogFactory;
    private \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;
    private \Ess\M2ePro\Model\Amazon\Magento\Order\UpdateAddressVatIdService $updateAddressVatIdService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Magento\Order\UpdateAddressVatIdService $updateAddressVatIdService,
        \Ess\M2ePro\Model\Synchronization\LogFactory $synchronizationLogFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = []
    ) {
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
        $this->synchronizationLogFactory = $synchronizationLogFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->updateAddressVatIdService = $updateAddressVatIdService;
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
        $responseOrders = $responseData['data']['orders'] ?? [];

        $amazonOrdersIds = array_column($responseOrders, 'order_id');
        if (empty($amazonOrdersIds)) {
            return;
        }

        $ordersGroupedByAmazonOrderId = $this->loadOrdersGroupedByAmazonOrderId($amazonOrdersIds);

        foreach ($responseOrders as $orderData) {
            $amazonOrderId = $orderData['order_id'];
            if (!isset($ordersGroupedByAmazonOrderId[$amazonOrderId])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $ordersGroupedByAmazonOrderId[$amazonOrderId];
            $buyerVatNumber = $orderData['buyer_vat_number'] ?? null;
            if (
                empty($buyerVatNumber)
                || !empty($order->getChildObject()->getTaxRegistrationId())
            ) {
                continue;
            }

            $order->getChildObject()->setTaxRegistrationId($buyerVatNumber);
            $order->getChildObject()->save();

            $this->updateAddressVatIdService->execute($order, $buyerVatNumber);
        }
    }

    private function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->synchronizationLog)) {
            $this->synchronizationLog = $this->synchronizationLogFactory->create();
            $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
            $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);
        }

        return $this->synchronizationLog;
    }

    private function loadOrdersGroupedByAmazonOrderId(array $amazonOrdersIds): array
    {
        $ordersCollection = $this->orderCollectionFactory->createWithAmazonChildMode();
        $ordersCollection->addFieldToFilter('amazon_order_id', ['in' => $amazonOrdersIds]);

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $orders = $ordersCollection->getItems();

        $result = [];
        foreach ($orders as $order) {
            $result[$order->getChildObject()->getAmazonOrderId()] = $order;
        }

        return $result;
    }
}
