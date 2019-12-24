<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Actions;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Walmart\Processing\Action;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Request\Pending\Single;
use Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel as AbstractCollection;

/**
 * Class \Ess\M2ePro\Model\Walmart\Actions\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    protected $activeRecordFactory;

    protected $walmartFactory;

    protected $lockItem;

    protected $resource;

    protected $alreadyProcessedItemIds = [];

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->activeRecordFactory = $activeRecordFactory;
        $this->walmartFactory = $walmartFactory;
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
        $accountCollection = $this->walmartFactory->getObject('Account')->getCollection();

        /** @var Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            $this->executeNotProcessedAccountActions($account);
        }
    }

    //####################################

    private function removeMissedProcessingActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            ['p' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_processing')],
            'p.id = main_table.processing_id',
            []
        );
        $actionCollection->addFieldToFilter('p.id', ['null' => true]);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();

        foreach ($actions as $action) {
            $action->delete();
        }
    }

    private function completeExpiredActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getCollection();
        $actionCollection->addFieldToFilter('request_pending_single_id', ['notnull' => true]);
        $actionCollection->getSelect()->joinLeft(
            [
                'rps' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_request_pending_single')
            ],
            'rps.id = main_table.request_pending_single_id',
            []
        );
        $actionCollection->addFieldToFilter('rps.id', ['null' => true]);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();

        /** @var Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.',
            Message::TYPE_ERROR
        );

        foreach ($actions as $actionItem) {
            $this->completeAction($actionItem, ['errors' => [$message->asArray()]]);
        }
    }

    private function completeNeedSynchRulesCheckActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getCollection();
        $actionCollection->setNotProcessedFilter();
        $actionCollection->getSelect()->joinLeft(
            [
                'lp' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing_product')
            ],
            'lp.id = main_table.related_id',
            ['need_synch_rules_check']
        );
        $actionCollection->addFieldToFilter('need_synch_rules_check', true);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();
        if (empty($actions)) {
            return;
        }

        /** @var Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'There was no need for this action. It was skipped. New action request with updated Product
            information will be performed automatically.',
            Message::TYPE_ERROR
        );

        foreach ($actions as $action) {
            $this->completeAction($action, ['errors' => [$message->asArray()]]);
        }
    }

    private function executeCompletedRequestsPendingSingle()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action $actionResource */
        $actionResource = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getResource();
        $requestIds = $actionResource->getUniqueRequestPendingSingleIds();

        if (empty($requestIds)) {
            return;
        }

        /** @var AbstractCollection $pendingSingleCollection */
        $pendingSingleCollection = $this->activeRecordFactory->getObject('Request_Pending_Single')->getCollection();
        $pendingSingleCollection->addFieldToFilter('id', ['in' => $requestIds]);
        $pendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var Single[] $pendingSingleObjects */
        $pendingSingleObjects = $pendingSingleCollection->getItems();
        if (empty($pendingSingleObjects)) {
            return;
        }

        foreach ($pendingSingleObjects as $requestId => $pendingSingle) {
            /** @var AbstractCollection $actionCollection */

            /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\Collection $actionCollection */
            $actionCollection = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getCollection();
            $actionCollection->setRequestPendingSingleIdFilter($requestId);
            $actionCollection->setInProgressFilter();

            /** @var Action[] $actions */
            $actions = $actionCollection->getItems();

            $resultData     = $pendingSingle->getResultData();
            $resultMessages = $pendingSingle->getResultMessages();

            foreach ($actions as $action) {
                $relatedId = $action->getRelatedId();

                $resultActionData = $this->getResponseData($resultData, $relatedId);

                if (empty($resultActionData['errors'])) {
                    $resultActionData['errors'] = [];
                }

                if (!empty($resultMessages)) {
                    $resultActionData['errors'] = array_merge($resultActionData['errors'], $resultMessages);
                }

                $this->completeAction($action, $resultActionData, $pendingSingle->getData('create_date'));
            }

            $pendingSingle->delete();
        }
    }

    private function executeNotProcessedAccountActions(Account $account)
    {
        $actionTypes = [
            Action::TYPE_PRODUCT_ADD,
            Action::TYPE_PRODUCT_UPDATE
        ];

        foreach ($actionTypes as $actionType) {
            $this->executeAction($actionType, $account);
        }
    }

    //####################################

    /**
     * @param $actionType
     * @param Account[] $accounts
     */
    private function executeAction($actionType, Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\Collection $actionCollection */
        $actionCollection = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getCollection();
        $actionCollection->setNotProcessedFilter();
        $actionCollection->setActionTypeFilter($actionType);
        $actionCollection->setAccountsFilter([$account]);
        $actionCollection->setPageSize($this->getMaxItemsCountInRequest());
        $actionCollection->setOrder('start_date', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        if ($actionCollection->getSize() <= 0) {
            return;
        }

        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $command = $this->getCommand($actionType);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();
        $requestData = $this->getRequestData($actions);
        $requestData['account'] = $account->getChildObject()->getServerHash();

        $connectorObj = $dispatcherObject->getVirtualConnector(
            $command[0],
            $command[1],
            $command[2],
            $requestData,
            null,
            null
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
                $messages = $responseMessages;

                if (!empty($responseData['data'][$action->getRelatedId().'-id']['errors'])) {
                    $messages = array_merge(
                        $messages, $responseData['data'][$action->getRelatedId().'-id']['errors']
                    );
                }

                $this->completeAction($action, ['errors' => $messages]);
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action $actionResource */
        $actionResource = $this->activeRecordFactory->getObject('Walmart_Processing_Action')->getResource();

        $actionResource->markAsInProgress(
            $actionCollection->getColumnValues('id'),
            $this->buildRequestPendingSingle($responseData['processing_id'])
        );
    }

    //####################################

    private function getMaxItemsCountInRequest()
    {
        return 5000;
    }

    //####################################

    private function getCommand($actionType)
    {
        switch ($actionType) {
            case Action::TYPE_PRODUCT_ADD:
                return ['product', 'add', 'entities'];

            case Action::TYPE_PRODUCT_UPDATE:
                return ['product', 'update', 'entities'];

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
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $messageText,
            Message::TYPE_ERROR
        );

        foreach ($actions as $action) {
            $this->completeAction($action, ['errors' => [$message->asArray()]]);
        }
    }

    /**
     * @param $serverHash
     * @return Single
     */
    private function buildRequestPendingSingle($serverHash)
    {
        $requestPendingSingle = $this->activeRecordFactory->getObject('Request_Pending_Single');
        $requestPendingSingle->setData([
            'component'       => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'server_hash'     => $serverHash,
            'expiration_date' => $this->helperFactory->getObject('Data')->getDate(
                $this->helperFactory->getObject('Data')->getCurrentGmtDate(true) + self::PENDING_REQUEST_MAX_LIFE_TIME
            )
        ]);
        $requestPendingSingle->save();

        return $requestPendingSingle;
    }

    //####################################

    private function getResponseData(array $responseData, $relatedId)
    {
        $itemData = [];

        if (!empty($responseData[$relatedId.'-id'])) {
            $itemData = $responseData[$relatedId.'-id'];
        }

        return $itemData;
    }

    // ---------------------------------------

    /**
     * @param Action[] $actions
     * @param string $actionType
     * @return array
     */
    private function getRequestData(array $actions)
    {
        $requestData = [];

        foreach ($actions as $action) {
            $requestData[$action->getRelatedId()] = $action->getRequestData();
        }

        return ['items' => $requestData];
    }

    //####################################

    private function completeAction(Action $action, array $data, $requestTime = null)
    {
        $processing = $action->getProcessing();

        $data['start_processing_date'] = $action->getStartDate();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        if ($requestTime !== null) {
            $processingParams = $processing->getParams();
            $processingParams['request_time'] = $requestTime;
            $processing->setSettings('params', $processingParams);
        }

        $processing->save();

        $action->delete();
    }

    //####################################
}
