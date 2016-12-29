<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive\Details;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\Details\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    protected $synchronizationLog = NULL;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    )
    {
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

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
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
        $responseData = $this->getPreparedResponseData();

        $amazonOrdersIds = array();
        foreach ($responseData['data'] as $details) {
            $amazonOrdersIds[] = $details['amazon_order_id'];
        }

        $amazonOrdersIds = array_unique($amazonOrdersIds);
        if (empty($amazonOrdersIds)) {
            return;
        }

        $ordersCollection = $this->amazonFactory->getObject('Order')->getCollection();
        $ordersCollection->addFieldToFilter('amazon_order_id', array('in' => $amazonOrdersIds));

        foreach ($responseData['data'] as $details) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $ordersCollection->getItemByColumnValue('amazon_order_id', $details['amazon_order_id']);
            if (is_null($order)) {
                continue;
            }

            unset($details['amazon_order_id']);

            $additionalData = $order->getAdditionalData();
            $additionalData['fulfillment_details'] = $details;
            $order->setSettings('additional_data', $additionalData)->save();
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account','account_id');
    }

    protected function getSynchronizationLog()
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