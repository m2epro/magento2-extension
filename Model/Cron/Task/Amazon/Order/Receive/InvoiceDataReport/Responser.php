<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceDataReport;

/**
 * Class Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceDataReport\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceDataReport\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
    protected $synchronizationLog = null;

    //########################################

    /**
     * @param array $messages
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseMessages(array $messages = [])
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module_Translation')->__($message->getText()),
                $logType
            );
        }
    }

    /**
     * @return bool
     */
    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    //########################################

    /**
     * @param string $messageText
     */
    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module_Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
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

            $order->getChildObject()->setSettings('invoice_data_report', $orderData);
            $order->getChildObject()->save();

            $order->getChildObject()->sendInvoiceFromReport();
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization_Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $this->synchronizationLog;
    }

    //########################################
}
