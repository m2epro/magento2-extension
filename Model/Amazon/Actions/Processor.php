<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Actions;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Amazon\Processing\Action;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Request\Pending\Single;
use Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel as AbstractCollection;

class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    protected $activeRecordFactory;

    protected $amazonFactory;

    protected $lockItem;

    protected $resource;

    protected $alreadyProcessedItemIds = array();

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        $data = []
    )
    {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        $this->resource = $resource;
    }

    //####################################

    public function getLockItem()
    {
        return $this->lockItem;
    }

    public function setLockItem(\Ess\M2ePro\Model\Lock\Item\Manager $lockItem)
    {
        $this->lockItem = $lockItem;
        return $this;
    }

    //####################################

    public function process()
    {
        $this->removeMissedProcessingActions();
        $this->completeExpiredActions();
        $this->completeNeedSynchRulesCheckActions();

        $this->executeCompletedRequestsPendingSingle();

        /** @var AbstractCollection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();

        /** @var Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            $this->executeNotProcessedSingleAccountActions($account);
        }

        $groupedAccounts = array();

        foreach ($accounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account */

            $merchantId = $account->getChildObject()->getMerchantId();
            if (!isset($groupedAccounts[$merchantId])) {
                $groupedAccounts[$merchantId] = array();
            }

            $groupedAccounts[$merchantId][] = $account;
        }

        foreach ($groupedAccounts as $accountsGroup) {
            $this->executeNotProcessedMultipleAccountsActions($accountsGroup);
        }
    }

    //####################################

    private function removeMissedProcessingActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            array('p' => $this->resource->getTableName('m2epro_processing')),
            'p.id = main_table.processing_id',
            array()
        );
        $actionCollection->addFieldToFilter('p.id', array('null' => true));

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();

        foreach ($actions as $action) {
            $action->delete();
        }
    }

    private function completeExpiredActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
        $actionCollection->addFieldToFilter('request_pending_single_id', array('notnull' => true));
        $actionCollection->getSelect()->joinLeft(
            array('rps' => $this->resource->getTableName('m2epro_request_pending_single')),
            'rps.id = main_table.request_pending_single_id',
            array()
        );
        $actionCollection->addFieldToFilter('rps.id', array('null' => true));

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();

        /** @var Message $message */
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.', Message::TYPE_ERROR
        );

        foreach ($actions as $actionItem) {
            $this->completeAction($actionItem, array('messages' => array($message->asArray())));
        }
    }

    private function completeNeedSynchRulesCheckActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            array('lp' => $this->resource->getTableName('m2epro_listing_product')),
            'lp.id = main_table.related_id',
            array('need_synch_rules_check')
        );
        $actionCollection->addFieldToFilter('need_synch_rules_check', true);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();
        if (empty($actions)) {
            return;
        }

        /** @var Message $message */
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'There was no need for this action. It was skipped. New action request with updated Product
            information will be performed automatically.',
            Message::TYPE_ERROR
        );

        foreach ($actions as $action) {
            $this->completeAction($action, array('messages' => array($message->asArray())));
        }
    }

    private function executeCompletedRequestsPendingSingle()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action $actionResource */
        $actionResource = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getResource();
        $requestIds = $actionResource->getUniqueRequestPendingSingleIds();

        if (empty($requestIds)) {
            return;
        }

        /** @var AbstractCollection $pendingSingleCollection */
        $pendingSingleCollection = $this->activeRecordFactory->getObject('Request\Pending\Single')->getCollection();
        $pendingSingleCollection->addFieldToFilter('id', array('in' => $requestIds));
        $pendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var Single[] $pendingSingleObjects */
        $pendingSingleObjects = $pendingSingleCollection->getItems();
        if (empty($pendingSingleObjects)) {
            return;
        }

        foreach ($pendingSingleObjects as $requestId => $pendingSingle) {
            /** @var AbstractCollection $actionCollection */

            /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
            $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
            $actionCollection->setRequestPendingSingleIdFilter($requestId);
            $actionCollection->setInProgressFilter();

            /** @var Action[] $actions */
            $actions = $actionCollection->getItems();

            $resultData     = $pendingSingle->getResultData();
            $resultMessages = $pendingSingle->getResultMessages();

            foreach ($actions as $action) {

                $relatedId = $action->getRelatedId();

                $resultActionData = $this->getResponseData($resultData, $relatedId);
                $resultActionData['messages'] = $this->getResponseMessages($resultData, $resultMessages, $relatedId);

                $this->completeAction($action, $resultActionData, $pendingSingle->getData('create_date'));
            }

            $pendingSingle->delete();
        }
    }

    private function executeNotProcessedSingleAccountActions(Account $account)
    {
        foreach ($this->getSingleAccountActionTypes() as $actionType) {
            while ($this->isNeedExecuteAction($actionType, array($account))) {
                $this->executeAction($actionType, array($account));
            }
        }
    }

    private function executeNotProcessedMultipleAccountsActions(array $accounts)
    {
        foreach ($this->getMultipleAccountsActionTypes() as $actionType) {
            while ($this->isNeedExecuteAction($actionType, $accounts)) {
                $this->executeAction($actionType, $accounts);
            }
        }
    }

    //####################################

    /**
     * @param $actionType
     * @param Account[] $accounts
     * @return bool
     */
    private function isNeedExecuteAction($actionType, array $accounts)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
        $actionCollection->setNotProcessedFilter();
        $actionCollection->setActionTypeFilter($actionType);
        $actionCollection->setAccountsFilter($accounts);

        if ($actionCollection->getSize() > $this->getMaxAllowedWaitingItemsCount()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
        $actionCollection->setNotProcessedFilter();
        $actionCollection->setActionTypeFilter($actionType);
        $actionCollection->setAccountsFilter($accounts);
        $actionCollection->setStartedBeforeFilter($this->getMaxAllowedMinutesDelay($actionType));

        return (bool)$actionCollection->getSize();
    }

    /**
     * @param $actionType
     * @param Account[] $accounts
     */
    private function executeAction($actionType, array $accounts)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getCollection();
        $actionCollection->setNotProcessedFilter();
        $actionCollection->setActionTypeFilter($actionType);
        $actionCollection->setAccountsFilter($accounts);
        $actionCollection->setPageSize($this->getMaxItemsCountInRequest());
        $actionCollection->setOrder('start_date', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        if ($actionCollection->getSize() <= 0) {
            return;
        }

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $command = $this->getCommand($actionType);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();

        $requestData = $this->getRequestData($actions, $actionType);

        if ($this->isMultipleAccountsActionType($actionType)) {
            foreach ($accounts as $account) {
                $requestData['accounts'][] = $account->getChildObject()->getServerHash();
            }
        } else {
            $requestData['account'] = reset($accounts)->getChildObject()->getServerHash();
        }

        $connectorObj = $dispatcherObject->getVirtualConnector(
            $command[0], $command[1], $command[2],
            $requestData, null, null
        );

        try {
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
            $this->processFailedActionsRequest($actions, $exception->getMessage());
            return;
        }

        $responseData = $connectorObj->getResponseData();
        $responseMessages = $connectorObj->getResponseMessages();

        if (empty($responseData['processing_id'])) {
            foreach ($actions as $action) {

                $messages = $this->getResponseMessages($responseData, $responseMessages, $action->getRelatedId());
                $this->completeAction($action, array('messages' => $messages)
                );
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action $actionResource */
        $actionResource = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getResource();

        $actionResource->markAsInProgress(
            $actionCollection->getColumnValues('id'),
            $this->buildRequestPendingSingle($responseData['processing_id'])
        );
    }

    //####################################

    private function getMaxItemsCountInRequest()
    {
        return 10000;
    }

    private function getMaxAllowedWaitingItemsCount()
    {
        return 1000;
    }

    private function getMaxAllowedMinutesDelay($actionType)
    {
        if (!$this->helperFactory->getObject('Module')->isProductionEnvironment()) {
            return 1;
        }

        if ($this->isProductActionType($actionType)) {
            return 15;
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
     * @param $actions Action[]
     * @param $messageText string
     */
    private function processFailedActionsRequest($actions, $messageText)
    {
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $messageText, Message::TYPE_ERROR
        );

        foreach ($actions as $action) {
            $this->completeAction($action, array('messages' => array($message->asArray())));
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
                $this->helperFactory->getObject('Data')->getCurrentGmtDate(true) + self::PENDING_REQUEST_MAX_LIFE_TIME
            )
        ));
        $requestPendingSingle->save();

        return $requestPendingSingle;
    }

    //####################################

    private function getSingleAccountActionTypes()
    {
        return array(
            Action::TYPE_PRODUCT_ADD,
            Action::TYPE_PRODUCT_UPDATE,
            Action::TYPE_PRODUCT_DELETE,
            Action::TYPE_ORDER_CANCEL,
            Action::TYPE_ORDER_REFUND,
        );
    }

    // ---------------------------------------

    private function getMultipleAccountsActionTypes()
    {
        return array(
            Action::TYPE_ORDER_UPDATE,
        );
    }

    private function isMultipleAccountsActionType($actionType)
    {
        return in_array($actionType, $this->getMultipleAccountsActionTypes());
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

    //####################################

    private function getResponseData(array $responseData, $relatedId)
    {
        $itemData = array();

        if (!empty($responseData['asins'][$relatedId.'-id'])) {
            $itemData['asins'] = $responseData['asins'][$relatedId.'-id'];
        }

        return $itemData;
    }

    private function getResponseMessages(array $responseData, array $responseMessages, $relatedId)
    {
        $itemMessages = $responseMessages;

        if (!empty($responseData['messages'][0])) {
            $itemMessages = array_merge($itemMessages, $responseData['messages']['0']);
        }

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
     * @param Action[] $actions
     * @param string $actionType
     * @return array
     */
    private function getRequestData(array $actions, $actionType)
    {
        $requestData = array();

        foreach ($actions as $action) {
            $requestData[$action->getRelatedId()] = $action->getRequestData();
        }

        $dataKey = 'items';
        if (in_array($actionType, array(\Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_ORDER_CANCEL,
            \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_ORDER_REFUND))
        ) {
            $dataKey = 'orders';
        }

        return array($dataKey => $requestData);
    }

    //####################################

    private function completeAction(Action $action, array $data, $requestTime = NULL)
    {
        $processing = $action->getProcessing();

        $data['start_processing_date'] = $action->getStartDate();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        if (!is_null($requestTime)) {
            $processingParams = $processing->getParams();
            $processingParams['request_time'] = $requestTime;
            $processing->setSettings('params', $processingParams);
        }

        $processing->save();

        $action->delete();
    }

    //####################################
}