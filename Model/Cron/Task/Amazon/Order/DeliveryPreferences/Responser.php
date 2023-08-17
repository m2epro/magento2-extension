<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\DeliveryPreferences;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\DeliveryPreferences\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
    protected $synchronizationLog = null;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    ) {
        $this->translationHelper = $translationHelper;

        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory
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
                $this->translationHelper->__($message->getText()),
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
            $this->translationHelper->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    protected function processResponseData(): void
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData)) {
            return;
        }

        $amazonOrdersIds = array_keys($responseData);

        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $ordersCollection */
        $ordersCollection = $this->amazonFactory->getObject('Order')->getCollection();
        $ordersCollection->addFieldToFilter('amazon_order_id', ['in' => $amazonOrdersIds]);
        $ordersCollection->addFieldToFilter('is_get_delivery_preferences', 0);

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $orders = $ordersCollection->getItems();

        foreach ($orders as $orderEntity) {
            /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
            $amazonOrder = $orderEntity->getChildObject();
            $amazonOrderId = $amazonOrder->getAmazonOrderId();

            $uniqueDeliveryInstructions = [];

            foreach ($responseData[$amazonOrderId] as $item) {
                $instruction = $item['delivery_instructions'];

                if ($instruction !== null && !in_array($instruction, $uniqueDeliveryInstructions)) {
                    $uniqueDeliveryInstructions[] = $instruction;
                }
            }

            foreach ($uniqueDeliveryInstructions as $instruction) {
                /** @var \Ess\M2ePro\Model\Order\Note $noteModel */
                $noteModel = $this->activeRecordFactory->getObject('Order_Note');
                $noteModel->setData('note', $instruction);
                $noteModel->setData('order_id', $orderEntity->getId());
                $noteModel->save();
            }

            $amazonOrder->setIsGetDeliveryPreferences();
            $amazonOrder->save();
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
