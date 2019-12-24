<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize\ProductsResponser
 */
class ProductsResponser extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Responser
{
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\State[] $pickupStoreStateItems */
    private $pickupStoreStateItems = [];

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log $log */
    private $log = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($ebayFactory, $response, $helperFactory, $modelFactory, $params);

        $collection = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_State')->getCollection();
        $collection->addFieldToFilter('id', array_keys($this->params['pickup_store_state_items']));

        $this->pickupStoreStateItems = $collection->getItems();
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $messageText,
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        foreach ($this->pickupStoreStateItems as $stateItem) {
            if ($stateItem->getIsDeleted()) {
                $stateItem->delete();
                continue;
            }

            $this->logMessage($stateItem, $message);
        }
    }

    //########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        return isset($responseData['messages']);
    }

    protected function processResponseData()
    {
        $responseData     = $this->getPreparedResponseData();
        $responseMessages = $responseData['messages'];

        foreach ($this->pickupStoreStateItems as $stateItem) {
            $isSuccess = true;

            if (!empty($responseMessages[$stateItem->getSku()])) {
                $messages = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
                $messages->init($responseMessages[$stateItem->getSku()]);

                $isSuccess = $this->processMessages($stateItem, $messages);
            }

            if (!$isSuccess) {
                if ($stateItem->getIsDeleted()) {
                    $stateItem->delete();
                }

                continue;
            }

            $this->processSuccess($stateItem);
        }
    }

    //########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->pickupStoreStateItems as $stateItem) {
            $this->processMessages($stateItem, $this->getResponse()->getMessages());
        }
    }

    //########################################

    private function processMessages(
        \Ess\M2ePro\Model\Ebay\Account\PickupStore\State $stateItem,
        \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messages
    ) {
        foreach ($messages->getEntities() as $message) {
            $this->logMessage($stateItem, $message);
        }

        return !$messages->hasErrorEntities();
    }

    private function processSuccess(\Ess\M2ePro\Model\Ebay\Account\PickupStore\State $stateItem)
    {
        $stateItemData = $this->params['pickup_store_state_items'][$stateItem->getId()];

        $this->logMessage($stateItem, $this->getSuccessMessage($stateItemData));

        if (!$stateItem->getIsDeleted()) {
            $stateItem->addData([
                'online_qty' => $stateItemData['target_qty'],
                'is_added'   => 0,
                'is_deleted' => 0,
            ]);
            $stateItem->save();
        } else {
            $stateItem->delete();
        }
    }

    /**
     * @param array $stateItemData
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getSuccessMessage(array $stateItemData)
    {
        $encodedDescription = null;

        switch ($this->getLogsAction($stateItemData)) {
            case \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_ADD_PRODUCT:
                $encodedDescription = $this->getHelper('Module\Log')->encodeDescription(
                    'The Product with %qty% quantity was successfully added to the Store.',
                    ['!qty' => $stateItemData['target_qty']]
                );
                break;

            case \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_DELETE_PRODUCT:
                $encodedDescription = $this->getHelper('Module\Log')->encodeDescription(
                    'The Product was successfully deleted from the Store.'
                );
                break;

            case \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_UPDATE_QTY:
                $stockFrom = '';
                $stockTo   = '';
                if ((int)$stateItemData['target_qty'] == 0) {
                    $stockFrom = 'IN STOCK ';
                    $stockTo   = 'OUT OF STOCK ';
                } elseif ((int)$stateItemData['online_qty'] == 0) {
                    $stockFrom = 'OUT OF STOCK ';
                    $stockTo   = 'IN STOCK ';
                }

                $encodedDescription = $this->getHelper('Module\Log')->encodeDescription(
                    'The Product quantity was successfully changed from %stock_from%[%qty_from%]
                    to %stock_to%[%qty_to%] for the Store.',
                    [
                        '!qty_from'   => $stateItemData['online_qty'],
                        '!qty_to'     => $stateItemData['target_qty'],
                        '!stock_from' => $stockFrom,
                        '!stock_to'   => $stockTo,
                    ]
                );
                break;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown logs action type');
        }

        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $encodedDescription,
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        return $message;
    }

    //########################################

    private function logMessage(
        \Ess\M2ePro\Model\Ebay\Account\PickupStore\State $stateItem,
        \Ess\M2ePro\Model\Connector\Connection\Response\Message $message
    ) {
        $this->getLog()->addMessage(
            $stateItem->getId(),
            $this->params['logs_action_id'],
            $this->getLogsAction($stateItem),
            $message->getText(),
            $this->getLogsMessageType($message),
            $this->getLogsPriority($message)
        );
    }

    // ---------------------------------------

    private function getLogsAction($stateItemData)
    {
        if ($stateItemData['is_added']) {
            return \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_ADD_PRODUCT;
        }

        if ($stateItemData['is_deleted']) {
            return \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_DELETE_PRODUCT;
        }

        return \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_UPDATE_QTY;
    }

    private function getLogsMessageType(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
    {
        if ($message->isError()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR;
        }

        if ($message->isWarning()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;
        }

        if ($message->isSuccess()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS;
        }

        if ($message->isNotice()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE;
        }

        return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR;
    }

    private function getLogsPriority(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
    {
        if ($message->isError()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH;
        }

        if ($message->isNotice()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW;
        }

        return \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM;
    }

    //########################################

    private function getLog()
    {
        if ($this->log !== null) {
            return $this->log;
        }

        return $this->log = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_Log');
    }

    //########################################
}
