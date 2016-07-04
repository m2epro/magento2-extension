<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Amazon\Processing\Action;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Request\Pending\Single;
use Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection;

final class AmazonActions extends AbstractTask
{
    const NICK = 'amazon_actions';
    const MAX_MEMORY_LIMIT = 512;

    const ACTION_ITEM_PROCESS_MAX_ATTEMPTS = 5;

    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    protected $amazonFactory;

    private $alreadyProcessedItemIds = array();

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
    )
    {
        parent::__construct($parentFactory, $modelFactory, $activeRecordFactory, $helperFactory, $resource);

        $this->amazonFactory = $amazonFactory;
    }

    //####################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //####################################

    protected function performActions()
    {
        $this->removeMissedProcessingActions();
        $this->completeExpiredActionItems();
        $this->completeSkippedActionItems();

        $this->executeCompletedRequestsPendingSingle();
        $this->executeCompletedActions();

        /** @var AbstractCollection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();

        /** @var Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            $this->executeNotProcessedActions($account);
        }
    }

    //####################################

    private function removeMissedProcessingActions()
    {
        /** @var AbstractCollection $processingActionCollection */
        $processingActionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')
            ->getCollection();
        $processingActionCollection->getSelect()->joinLeft(
            array('p' => $this->resource->getTableName('m2epro_processing')),
            'p.id = main_table.processing_id',
            array()
        );
        $processingActionCollection->addFieldToFilter('p.id', array('null' => true));

        /** @var Action[] $processingActions */
        $processingActions = $processingActionCollection->getItems();

        foreach ($processingActions as $processingAction) {
            $processingAction->delete();
        }
    }

    private function completeExpiredActionItems()
    {
        /** @var AbstractCollection $actionItemCollection */
        $actionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getCollection();
        $actionItemCollection->addFieldToFilter('main_table.is_completed', 0);
        $actionItemCollection->addFieldToFilter('request_pending_single_id', array('notnull' => true));
        $actionItemCollection->getSelect()->joinLeft(
            array('rps' => $this->resource->getTableName('m2epro_request_pending_single')),
            'rps.id = main_table.request_pending_single_id',
            array()
        );
        $actionItemCollection->addFieldToFilter('rps.id', array('null' => true));

        /** @var Action\Item[] $actionItems */
        $actionItems = $actionItemCollection->getItems();

        /** @var Message $message */
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.', Message::TYPE_ERROR
        );

        foreach ($actionItems as $actionItem) {
            $this->completeProcessingActionItem($actionItem, array(), array($message->asArray()));
        }
    }

    private function completeSkippedActionItems()
    {
        /** @var AbstractCollection $actionItemCollection */
        $actionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getCollection();
        $actionItemCollection->addFieldToFilter('is_skipped', 1);
        $actionItemCollection->addFieldToFilter('is_completed', 0);

        /** @var Action\Item[] $actionItems */
        $actionItems = $actionItemCollection->getItems();
        if (empty($actionItems)) {
            return;
        }

        /** @var Message $message */
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'The Action was skipped because the data on Amazon channel were changed earlier.',
            Message::TYPE_ERROR
        );

        foreach ($actionItems as $actionItem) {
            $this->completeProcessingActionItem($actionItem, array('is_skipped' => true), array($message->asArray()));
        }
    }

    private function executeCompletedRequestsPendingSingle()
    {
        $requestIds = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getResource()
            ->getUniqueRequestPendingSingleIds();
        if (empty($requestIds)) {
            return;
        }

        /** @var AbstractCollection $requestPendingSingleCollection */
        $requestPendingSingleCollection = $this->activeRecordFactory->getObject('Request\Pending\Single')
            ->getCollection();
        $requestPendingSingleCollection->addFieldToFilter('id', array('in' => $requestIds));
        $requestPendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var Single[] $requestPendingSingleObjects */
        $requestPendingSingleObjects = $requestPendingSingleCollection->getItems();
        if (empty($requestPendingSingleObjects)) {
            return;
        }

        foreach ($requestPendingSingleObjects as $requestId => $requestPendingSingle) {
            /** @var AbstractCollection $processingActionItemCollection */
            $processingActionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')
                ->getCollection();
            $processingActionItemCollection->setRequestPendingSingleIdFilter($requestId);
            $processingActionItemCollection->setInProgressFilter();

            /** @var Action\Item[] $processingActionItems */
            $processingActionItems = $processingActionItemCollection->getItems();

            $resultData     = $requestPendingSingle->getResultData();
            $resultMessages = $requestPendingSingle->getResultMessages();

            foreach ($processingActionItems as $processingActionItem) {

                $relatedId = $processingActionItem->getRelatedId();

                $this->completeProcessingActionItem(
                    $processingActionItem,
                    $this->getItemResponseData($resultData, $relatedId),
                    $this->getItemResponseMessages($resultData, $resultMessages, $relatedId)
                );
            }

            $requestPendingSingle->delete();
        }
    }

    private function executeCompletedActions()
    {
        $actionIds = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getResource()
            ->getIdsWithFullyCompletedItems();
        if (empty($actionIds)) {
            return;
        }

        $processingActionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')
            ->getCollection();
        $processingActionCollection->addFieldToFilter('id', $actionIds);

        /** @var Action[] $processingActions */
        $processingActions = $processingActionCollection->getItems();

        foreach ($processingActions as $processingAction) {
            $actionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')
                ->getCollection();
            $actionItemCollection->setActionFilter($processingAction);

            $this->completeProcessingAction(
                $processingAction, $this->getItemsResponseData($actionItemCollection->getItems())
            );
        }
    }

    private function executeNotProcessedActions(Account $account)
    {
        foreach ($this->getActionTypes() as $actionType) {
            while ($this->isNeedExecuteAction($actionType, $account)) {
                $this->executeAction($actionType, $account);
            }
        }
    }

    //####################################

    private function isNeedExecuteAction($actionType, Account $account)
    {
        $actionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getCollection();
        $actionItemCollection->setNotProcessedFilter();
        $actionItemCollection->setActionTypeFilter($actionType);
        $actionItemCollection->setAccountFilter($account);

        if (!empty($this->alreadyProcessedItemIds)) {
            $actionItemCollection->addFieldToFilter('main_table.id', array('nin' => $this->alreadyProcessedItemIds));
        }

        if ($actionItemCollection->getSize() > $this->getMaxAllowedWaitingItemsCount($actionType)) {
            return true;
        }

        $actionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getCollection();
        $actionItemCollection->setNotProcessedFilter();
        $actionItemCollection->setActionTypeFilter($actionType);
        $actionItemCollection->setAccountFilter($account);
        $actionItemCollection->setCreatedBeforeFilter($this->getMaxAllowedMinutesDelay($actionType));

        if (!empty($this->alreadyProcessedItemIds)) {
            $actionItemCollection->addFieldToFilter('main_table.id', array('nin' => $this->alreadyProcessedItemIds));
        }

        return (bool)$actionItemCollection->getSize();
    }

    private function executeAction($actionType, Account $account)
    {
        $actionItemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getCollection();
        $actionItemCollection->setNotProcessedFilter();
        $actionItemCollection->addFieldToFilter('is_skipped', 0);
        $actionItemCollection->setActionTypeFilter($actionType);
        $actionItemCollection->setAccountFilter($account);
        $actionItemCollection->setPageSize($this->getMaxItemsCountInRequest($actionType));
        $actionItemCollection->setOrder('create_date', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        if (!empty($this->alreadyProcessedItemIds)) {
            $actionItemCollection->addFieldToFilter('main_table.id', array('nin' => $this->alreadyProcessedItemIds));
        }

        if ($actionItemCollection->getSize() <= 0) {
            return;
        }

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');

        $command = $this->getCommand($actionType);

        /** @var Action\Item[] $actionItems */
        $actionItems = $actionItemCollection->getItems();

        $connectorObj = $dispatcherObject->getVirtualConnector(
            $command[0], $command[1], $command[2],
            $this->getItemsRequestData($actionItems, $actionType), null, $account
        );

        $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getResource()->incrementAttemptsCount(
            $actionItemCollection->getColumnValues('id')
        );

        $this->alreadyProcessedItemIds = array_merge(
            $this->alreadyProcessedItemIds, $actionItemCollection->getColumnValues('id')
        );

        try {
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->processFailedActionItemsRequest($actionItemCollection->getItems(), $exception->getMessage());
            return;
        }

        $responseData = $connectorObj->getResponseData();
        $responseMessages = $connectorObj->getResponseMessages();

        if (empty($responseData['processing_id'])) {
            foreach ($actionItems as $actionItem) {
                $this->completeProcessingActionItem(
                    $actionItem,
                    array(),
                    $this->getItemResponseMessages($responseData, $responseMessages, $actionItem->getRelatedId())
                );
            }

            return;
        }

        $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getResource()->markAsInProgress(
            $actionItemCollection->getColumnValues('id'),
            $this->buildRequestPendingSingle($responseData['processing_id'])
        );
    }

    //####################################

    private function getMaxItemsCountInRequest($actionType)
    {
        if ($this->isProductActionType($actionType)) {
            return 10000;
        }

        return 35;
    }

    private function getMaxAllowedWaitingItemsCount($actionType)
    {
        if ($this->isProductActionType($actionType)) {
            return 1000;
        }

        return 35;
    }

    private function getMaxAllowedMinutesDelay($actionType)
    {
        if ($this->isProductActionType($actionType)) {
            return 1;
        }

        return 5;
    }

    //####################################

    private function getCommand($actionType)
    {
        switch ($actionType) {
            case Action::TYPE_PRODUCT_ADD:
                return array('product', 'add', 'entities');

            case Action::TYPE_PRODUCT_UPDATE:
                return array('product', 'update', 'entities');

            case Action::TYPE_PRODUCT_DELETE:
                return array('product', 'delete', 'entities');

            case Action::TYPE_ORDER_UPDATE:
                return array('orders', 'update', 'entities');

            case Action::TYPE_ORDER_CANCEL:
                return array('orders', 'cancel', 'entities');

            case Action::TYPE_ORDER_REFUND:
                return array('orders', 'refund', 'entities');

            default:
                throw new Logic('Unknown action type.');
        }
    }

    //####################################

    /**
     * @param $actionItems Action\Item[]
     * @param $messageText string
     */
    private function processFailedActionItemsRequest(array $actionItems, $messageText)
    {
        $failedMessage = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $failedMessage->initFromPreparedData(
            $messageText, Message::TYPE_ERROR
        );

        foreach ($actionItems as $actionItem) {
            if ($actionItem->getAttemptsCount() >= self::ACTION_ITEM_PROCESS_MAX_ATTEMPTS) {
                $this->completeProcessingActionItem($actionItem, array(), array($failedMessage->asArray()));
            }
        }
    }

    /**
     * @param $serverHash
     * @return Single
     */
    private function buildRequestPendingSingle($serverHash)
    {
        $requestPendingSingle = $this->activeRecordFactory->getObject('Request\Pending\Single');
        $requestPendingSingle->setData(array(
            'component'       => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'server_hash'     => $serverHash,
            'expiration_date' => $this->helperFactory->getObject('Data')->getDate(
                $this->helperFactory->getObject('Data')->getCurrentGmtDate(true)+self::PENDING_REQUEST_MAX_LIFE_TIME
            )
        ));
        $requestPendingSingle->save();

        return $requestPendingSingle;
    }

    //####################################

    private function getActionTypes()
    {
        return array_merge($this->getProductActionTypes(), $this->getOrderActionTypes());
    }

    // ---------------------------------------

    private function getProductActionTypes()
    {
        return array(
            Action::TYPE_PRODUCT_ADD,
            Action::TYPE_PRODUCT_UPDATE,
            Action::TYPE_PRODUCT_DELETE,
        );
    }

    private function isProductActionType($actionType)
    {
        return in_array($actionType, $this->getProductActionTypes());
    }

    // ---------------------------------------

    private function getOrderActionTypes()
    {
        return array(
            Action::TYPE_ORDER_UPDATE,
            Action::TYPE_ORDER_CANCEL,
            Action::TYPE_ORDER_REFUND,
        );
    }

    //####################################

    private function getItemResponseData(array $responseData, $relatedId)
    {
        $itemData = array();

        if (!empty($responseData['asins'][$relatedId.'-id'])) {
            $itemData['asins'] = $responseData['asins'][$relatedId.'-id'];
        }

        return $itemData;
    }

    private function getItemResponseMessages(array $responseData, array $responseMessages, $relatedId)
    {
        $itemMessages = $responseMessages;

        if (!empty($responseData['messages']['0-id'])) {
            $itemMessages = array_merge($itemMessages, $responseData['messages']['0-id']);
        }

        if (!empty($responseData['messages'][$relatedId.'-id'])) {
            $itemMessages = array_merge($itemMessages, $responseData['messages'][$relatedId.'-id']);
        }

        return $itemMessages;
    }

    // ---------------------------------------

    /**
     * @param Action\Item[] $actionItems
     * @return array
     */
    private function getItemsResponseData(array $actionItems)
    {
        $itemsData = array(
            'messages' => array(),
        );

        foreach ($actionItems as $actionItem) {
            $itemData = $actionItem->getOutputData();

            if (!empty($itemData['asins'])) {
                $itemsData['asins'][$actionItem->getRelatedId()] = $itemData['asins'];
            }

            $itemMessages = $actionItem->getOutputMessages();

            if (!empty($itemMessages)) {
                $itemsData['messages'][$actionItem->getRelatedId()] = $itemMessages;
            }
        }

        return $itemsData;
    }

    // ---------------------------------------

    /**
     * @param Action\Item[] $actionItems
     * @param $actionType
     * @return array
     */
    private function getItemsRequestData(array $actionItems, $actionType)
    {
        $requestData = array();

        foreach ($actionItems as $actionItem) {
            $requestData[$actionItem->getRelatedId()] = $actionItem->getInputData();
        }

        $dataKey = 'items';
        if (in_array($actionType, array(Action::TYPE_ORDER_CANCEL, Action::TYPE_ORDER_REFUND))) {
            $dataKey = 'orders';
        }

        return array($dataKey => $requestData);
    }

    //####################################

    private function completeProcessingAction(Action $processingAction, array $data)
    {
        $processing = $processingAction->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);
        $processing->save();

        $processingAction->delete();
    }

    private function completeProcessingActionItem(Action\Item $processingActionItem, array $data, array $messages)
    {
        $processingActionItem->setSettings('output_data', $data);
        $processingActionItem->setSettings('output_messages', $messages);

        $processingActionItem->setData('is_completed', 1);

        $processingActionItem->save();
    }

    //####################################
}