<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\ListAction;

use Ess\M2ePro\Model\Walmart\Listing\Product\Action\ListAction\ProcessingRunner as ProcessingRunner;
use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processor as ActionProcessor;
use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList as ProcessingList;
use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing as ActionProcessing;
use \Ess\M2ePro\Model\Connector\Connection\Response\Message as ResponseMessage;
use \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action\Processing\Collection as ProcessingCollection;
use \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action\ProcessingList as ProcessingListResourceModel;

/**
 * Class Ess\M2ePro\Model\Walmart\Listing\Product\Action\ListAction\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const LIST_PRIORITY = 25;

    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    const FIRST_CONNECTION_ERROR_DATE_REGISTRY_KEY = '/walmart/listing/product/action/first_connection_error/date/';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory  $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->activeRecordFactory = $activeRecordFactory;
        $this->walmartFactory = $walmartFactory;
        $this->resourceConnection = $resourceConnection;
        $this->localeCurrency = $localeCurrency;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function process()
    {
        $this->executeReadyForList();
        $this->executeCheckListResults();

        $this->executeReadyForRelist();
        $this->executeCheckRelistResults();
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    protected function executeReadyForList()
    {
        $throttlingManager = $this->modelFactory->getObject('Walmart_ThrottlingManager');

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->walmartFactory->getObject('Account')->getCollection();
        foreach ($accountCollection->getItems() as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            $availableRequestsCount = $throttlingManager->getAvailableRequestsCount(
                $account->getId(),
                ActionProcessor::FEED_TYPE_UPDATE_DETAILS
            );

            if ($availableRequestsCount <= 0) {
                continue;
            }

            $feedsPacks = [];

            $this->fillFeedsPacks($feedsPacks, $this->getScheduledActionsDataStatement($account));

            $actionsDataForProcessing = $this->processExistedSkus($feedsPacks);

            foreach ($actionsDataForProcessing as $accountId => $accountPacks) {
                foreach ($accountPacks as $listingsProductsData) {
                    if (empty($listingsProductsData)) {
                        continue;
                    }

                    $this->initProcessingActions($listingsProductsData);
                    $this->prepareScheduledActions($listingsProductsData);
                }
            }

            foreach ($feedsPacks as $accountId => $accountPacks) {
                $throttlingManager->registerRequests(
                    $accountId,
                    ActionProcessor::FEED_TYPE_UPDATE_DETAILS,
                    count($accountPacks)
                );
            }
        }

        $this->prepareProcessingActions();

        $fullyPreparedGroupHashes = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getResource()->getFullyPreparedGroupHashes();

        foreach ($fullyPreparedGroupHashes as $groupHash) {
            /** @var ProcessingCollection $actionCollection */
            $actionCollection = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();

            $actionCollection->addFieldToFilter('group_hash', $groupHash);
            $actionCollection->addFieldToFilter('is_prepared', 1);
            $actionCollection->addFieldToFilter('type', ActionProcessing::TYPE_ADD);
            $actionCollection->addFieldToFilter('request_pending_single_id', ['null' => true]);

            /** @var ActionProcessing[] $processingActions */
            $processingActions = $actionCollection->getItems();
            if (empty($processingActions)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
            $listingProductCollection->addFieldToFilter(
                'id',
                $actionCollection->getColumnValues('listing_product_id')
            );

            $createProcessingActions = [];
            $updateProcessingActions = [];

            foreach ($processingActions as $processingAction) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $listingProduct = $listingProductCollection->getItemById($processingAction->getListingProductId());
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();

                if ($walmartListingProduct->getSku()) {
                    $updateProcessingActions[$processingAction->getId()] = $processingAction;
                } else {
                    $createProcessingActions[$processingAction->getId()] = $processingAction;
                }
            }

            if (!empty($createProcessingActions)) {
                $this->processGroupedProcessingActions(
                    $createProcessingActions,
                    ['product', 'add', 'entities']
                );
            }

            if (!empty($updateProcessingActions)) {
                $this->processGroupedProcessingActions(
                    $updateProcessingActions,
                    ['product', 'update', 'entities']
                );
            }
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function executeCheckListResults()
    {
        $requestIds = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getResource()
            ->getUniqueRequestPendingSingleIds();

        if (empty($requestIds)) {
            return;
        }

        $requestPendingSingleCollection = $this->activeRecordFactory
            ->getObject('Request_Pending_Single')->getCollection();
        $requestPendingSingleCollection->addFieldToFilter('id', ['in' => $requestIds]);
        $requestPendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Request\Pending\Single[] $requestPendingSingleObjects */
        $requestPendingSingleObjects = $requestPendingSingleCollection->getItems();
        if (empty($requestPendingSingleObjects)) {
            return;
        }

        foreach ($requestPendingSingleObjects as $requestId => $requestPendingSingle) {

            /** @var ProcessingCollection $actionCollection */
            $actionCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();

            $actionCollection
                ->setInProgressFilter()
                ->addFieldToFilter('type', ActionProcessing::TYPE_ADD)
                ->getSelect()->joinInner(
                    ['apl' => $this->activeRecordFactory
                        ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getResource()->getMainTable()],
                    'apl.listing_product_id = main_table.listing_product_id',
                    []
                );

            $actionCollection
                ->setRequestPendingSingleIdFilter($requestId)
                ->addFieldToFilter('apl.stage', ProcessingList::STAGE_LIST_DETAILS);

            /** @var ActionProcessing[] $processingActions */
            $processingActions = $actionCollection->getItems();
            if (empty($processingActions)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
            $listingProductCollection->addFieldToFilter(
                'id',
                $actionCollection->getColumnValues('listing_product_id')
            );

            $resultMessages = $requestPendingSingle->getResultMessages();
            $resultData     = $requestPendingSingle->getResultData();

            foreach ($processingActions as $processingAction) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $listingProduct = $listingProductCollection->getItemById($processingAction->getListingProductId());

                $resultActionData = [];

                //worker may return different data structure
                if (isset($resultData[$processingAction->getListingProductId() . '-id'])) {
                    $resultActionData = $resultData[$processingAction->getListingProductId() . '-id'];
                } elseif (isset($resultData['data'][$processingAction->getListingProductId().'-id'])) {
                    $resultActionData = $resultData['data'][$processingAction->getListingProductId().'-id'];
                }

                if (empty($resultActionData['errors'])) {
                    $resultActionData['errors'] = [];
                }

                if (!empty($resultMessages)) {
                    // @codingStandardsIgnoreLine
                    $resultActionData['errors'] = array_merge($resultActionData['errors'], $resultMessages);
                }

                if (empty($resultActionData['errors']) && empty($resultActionData['wpid'])) {
                    $message = $this->getHelper('Module\Translation')->__(
                        'The Item was not listed due to the unexpected error on Walmart side.
                        Please try to list this Item later.'
                    );

                    $resultActionData['errors'][] = [
                        ResponseMessage::TYPE_KEY   => \Ess\M2ePro\Model\Response\Message::TYPE_ERROR,
                        ResponseMessage::TEXT_KEY   => $message,
                        ResponseMessage::SENDER_KEY => ResponseMessage::SENDER_COMPONENT,
                        ResponseMessage::CODE_KEY   => '',
                    ];
                }

                $processing = $processingAction->getProcessing();
                $processing->setSettings('result_data', $resultActionData);
                $processing->save();

                /** @var ProcessingRunner $processingRunner */
                $processingRunner = $this->modelFactory->getObject($processing->getModel());
                $processingRunner->setProcessingObject($processing);
                $processingRunner->setListingProduct($listingProduct);

                if (!$processingRunner->processAddResult()) {
                    $processingRunner->complete();
                    $processingAction->delete();
                    continue;
                }

                /** @var ProcessingListResourceModel $listResource */
                $listResource = $this->activeRecordFactory
                    ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getResource();
                $listResource->markAsRelistInventoryReady([$processingAction->getListingProductId()]);
            }

            $requestPendingSingle->delete();
        }
    }

    // ---------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function executeReadyForRelist()
    {
        /** @var \Ess\M2ePro\Model\Walmart\ThrottlingManager $throttlingManager */
        $throttlingManager = $this->modelFactory->getObject('Walmart_ThrottlingManager');

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->walmartFactory->getObject('Account')->getCollection();
        foreach ($accountCollection->getItems() as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            $qtyRequestCount = $throttlingManager->getAvailableRequestsCount(
                $account->getId(),
                ActionProcessor::FEED_TYPE_UPDATE_QTY
            );
            $lagTimeRequestCount = $throttlingManager->getAvailableRequestsCount(
                $account->getId(),
                ActionProcessor::FEED_TYPE_UPDATE_LAG_TIME
            );

            if ($qtyRequestCount <= 0 || $lagTimeRequestCount <= 0) {
                continue;
            }

            /** @var ProcessingCollection $actionCollection */
            $actionCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
            $actionCollection
                ->setInProgressFilter()
                ->addFieldToFilter('type', ActionProcessing::TYPE_ADD)
                ->getSelect()->joinInner(
                    ['apl' => $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_ProcessingList')
                                        ->getResource()->getMainTable()],
                    'apl.listing_product_id = main_table.listing_product_id',
                    ['processing_list_id' => 'id']
                );

            $actionCollection
                ->addFieldToFilter('apl.stage', ProcessingList::STAGE_RELIST_INVENTORY_READY)
                ->addFieldToFilter('apl.account_id', $account->getId())
                ->getSelect()->limit($this->getMaxPackSize());

            /** @var ActionProcessing[] $processingActions */
            $processingActions = $actionCollection->getItems();
            if (empty($processingActions)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
            $listingProductCollection->addFieldToFilter(
                'id',
                $actionCollection->getColumnValues('listing_product_id')
            );

            $listCollection = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getCollection();
            $listCollection->addFieldToFilter(
                'id',
                $actionCollection->getColumnValues('processing_list_id')
            );

            $itemsRequestData = [];
            foreach ($processingActions as $processingAction) {

                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $listingProduct = $listingProductCollection->getItemById($processingAction->getListingProductId());
                $processingAction->setListingProduct($listingProduct);

                /** @var ProcessingList $processingList */
                $processingList = $listCollection->getItemById($processingAction->getData('processing_list_id'));

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->disableAll();
                $configurator->allowQty()
                             ->allowLagTime();

                $listingProduct->setActionConfigurator($configurator);
                $listingProduct->setProcessingAction($processingAction);

                $params = $processingAction->getProcessing()->getParams();
                /** @var \Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\UpdateInventory\Requester $connector */
                $connector = $this->modelFactory->getObject('Walmart_Connector_Dispatcher')->getCustomConnector(
                    'Walmart_Connector_Product_ListAction_UpdateInventory_Requester',
                    $params['requester_params']
                );
                $connector->setListingProduct($listingProduct);

                $requestData = $connector->getRequestData();
                $itemsRequestData[$listingProduct->getId()] = $requestData;

                $processingList->addData(
                    [
                        'relist_request_data'      => $this->getHelper('Data')->jsonEncode($requestData),
                        'relist_configurator_data' =>
                            $this->getHelper('Data')->jsonEncode($configurator->getSerializedData())
                    ]
                );
                $processingList->save();
            }

            $throttlingManager->registerRequests($account->getId(), ActionProcessor::FEED_TYPE_UPDATE_QTY, 1);
            $throttlingManager->registerRequests($account->getId(), ActionProcessor::FEED_TYPE_UPDATE_LAG_TIME, 1);

            /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
            $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
            $connector = $dispatcher->getVirtualConnector(
                'product',
                'update',
                'entities',
                ['items' => $itemsRequestData],
                null,
                $account
            );

            try {
                $dispatcher->process($connector);
            } catch (\Exception $exception) {
                $this->getHelper('Module\Exception')->process($exception);

                $this->failedOnRelistAttemptCallback($actionCollection->getColumnValues('id'));
                continue;
            }

            $responseData = $connector->getResponseData();
            if (empty($responseData['processing_id'])) {
                $this->failedOnRelistAttemptCallback($actionCollection->getColumnValues('id'));
                continue;
            }

            $requestPendingSingle = $this->activeRecordFactory->getObject('Request_Pending_Single');
            $requestPendingSingle->setData(
                [
                    'component'       => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                    'server_hash'     => $responseData['processing_id'],
                    'expiration_date' => $this->getHelper('Data')->getDate(
                        $this->getHelper('Data')->getCurrentGmtDate(true) + self::PENDING_REQUEST_MAX_LIFE_TIME
                    )
                ]
            );
            $requestPendingSingle->save();

            /** @var ProcessingListResourceModel $processingListResource */
            $processingListResource = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getResource();
            $processingListResource->markAsRelistInventoryWaitingResult(
                $actionCollection->getColumnValues('listing_product_id'),
                $requestPendingSingle->getId()
            );
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function executeCheckRelistResults()
    {
        /** @var ProcessingListResourceModel $processingListResource */
        $processingListResource = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getResource();
        $requestIds = $processingListResource->getUniqueRelistRequestPendingSingleIds();

        if (empty($requestIds)) {
            return;
        }

        $requestPendingSingleCollection = $this->activeRecordFactory
            ->getObject('Request_Pending_Single')->getCollection();
        $requestPendingSingleCollection->addFieldToFilter('id', ['in' => $requestIds]);
        $requestPendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Request\Pending\Single[] $requestPendingSingleObjects */
        $requestPendingSingleObjects = $requestPendingSingleCollection->getItems();
        if (empty($requestPendingSingleObjects)) {
            return;
        }

        foreach ($requestPendingSingleObjects as $requestId => $requestPendingSingle) {

            /** @var ProcessingCollection $actionCollection */
            $actionCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
            $actionCollection
                ->setInProgressFilter()
                ->addFieldToFilter('type', ActionProcessing::TYPE_ADD)
                ->getSelect()->joinInner(
                    ['apl' => $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_ProcessingList')
                        ->getResource()->getMainTable()],
                    'apl.listing_product_id = main_table.listing_product_id',
                    ['processing_list_id' => 'id']
                );

            $actionCollection
                ->addFieldToFilter('apl.stage', ProcessingList::STAGE_RELIST_INVENTORY_WAITING_RESULT)
                ->addFieldToFilter('apl.relist_request_pending_single_id', $requestPendingSingle->getId());

            /** @var ActionProcessing[] $processingActions */
            $processingActions = $actionCollection->getItems();
            if (empty($processingActions)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
            $listingProductCollection->addFieldToFilter('id', $actionCollection->getColumnValues('listing_product_id'));

            /** @var ProcessingListResourceModel\Collection $listCollection */
            $listCollection = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getCollection();
            $listCollection->addFieldToFilter('id', $actionCollection->getColumnValues('processing_list_id'));

            $resultMessages = $requestPendingSingle->getResultMessages();
            $resultData     = $requestPendingSingle->getResultData();

            foreach ($processingActions as $processingAction) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $listingProduct = $listingProductCollection->getItemById($processingAction->getListingProductId());
                $processingAction->setListingProduct($listingProduct);

                /** @var ProcessingList $processingList */
                $processingList = $listCollection->getItemById($processingAction->getData('processing_list_id'));

                $resultActionData = [];

                //worker may return different data structure
                if (isset($resultData[$processingAction->getListingProductId() . '-id'])) {
                    $resultActionData = $resultData[$processingAction->getListingProductId() . '-id'];
                } elseif (isset($resultData['data'][$processingAction->getListingProductId().'-id'])) {
                    $resultActionData = $resultData['data'][$processingAction->getListingProductId().'-id'];
                }

                if (empty($resultActionData['errors'])) {
                    $resultActionData['errors'] = [];
                }

                if (!empty($resultMessages)) {
                    // @codingStandardsIgnoreLine
                    $resultActionData['errors'] = array_merge($resultActionData['errors'], $resultMessages);
                }

                /** @var ProcessingRunner $processingRunner */
                $processingRunner = $this->modelFactory->getObject($processingAction->getProcessing()->getModel());
                $processingRunner->setProcessingObject($processingAction->getProcessing());
                $processingRunner->setListingProduct($listingProduct);

                if (!$processingRunner->processRelistResult($processingList, $resultActionData)) {
                    $this->completeListProcessingActionFail($processingAction);
                } else {
                    $this->completeListProcessingActionSuccess($processingAction);
                }
            }

            $requestPendingSingle->delete();
        }
    }

    //########################################

    /**
     * @param array $processingActionsIds
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function failedOnRelistAttemptCallback($processingActionsIds)
    {
        /** @var ProcessingCollection $actionCollection */
        $actionCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
        $actionCollection->addFieldToFilter('id', ['in' => $processingActionsIds]);

        foreach ($actionCollection->getItems() as $processingAction) {
            /** @var ActionProcessing $processingAction */
            $this->completeListProcessingActionFail($processingAction);
        }
    }

    // ---------------------------------------

    /**
     * @param ActionProcessing $processingAction
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function completeListProcessingActionSuccess(ActionProcessing $processingAction)
    {
        $processing       = $processingAction->getProcessing();
        $listingProduct   = $processingAction->getListingProduct();
        $processingParams = $processing->getParams();

        $linking = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Type_ListAction_Linking');
        $linking->setListingProduct($listingProduct);
        $linking->setSku($processingParams['request_data']['sku']);
        $linking->createWalmartItem();

        $logger = $this->createLogger(
            $processingParams['responser_params']['params']['status_changer'],
            $processingParams['responser_params']['logs_action_id']
        );

        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $this->getSuccessMessage($listingProduct),
            ResponseMessage::TYPE_SUCCESS
        );

        $logger->logListingProductMessage($listingProduct, $message);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        if ($walmartListingProduct->getVariationManager()->isRelationChildType()) {
            $parentListingProduct = $walmartListingProduct->getVariationManager()
                ->getTypeModel()
                ->getParentListingProduct();

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartParentListingProduct */
            $walmartParentListingProduct = $parentListingProduct->getChildObject();
            $walmartParentListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
        }

        /** @var ProcessingRunner $processingRunner */
        $processingRunner = $this->modelFactory->getObject($processing->getModel());
        $processingRunner->setProcessingObject($processing);
        $processingRunner->setListingProduct($listingProduct);

        $processingRunner->complete();
        $processingAction->delete();
    }

    /**
     * @param ActionProcessing $processingAction
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function completeListProcessingActionFail(ActionProcessing $processingAction)
    {
        $processing       = $processingAction->getProcessing();
        $listingProduct   = $processingAction->getListingProduct();
        $processingParams = $processing->getParams();

        $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
        $listingProduct->save();

        $logger = $this->createLogger(
            $processingParams['responser_params']['params']['status_changer'],
            $processingParams['responser_params']['logs_action_id']
        );

        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'The Item was listed. However, some product data, i.e. product quantity, cannot yet
            be submitted. It is caused by the technical limitations imposed by Walmart when adding a new offer
            on their website. M2E Pro will try to submit this product data later.',
            ResponseMessage::TYPE_WARNING
        );

        $logger->logListingProductMessage($listingProduct, $message);

        $this->completeListProcessingActionSuccess($processingAction);
    }

    //########################################

    protected function getSuccessMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
            return 'Parent Product was Listed';
        }
        
        $currency = $this->localeCurrency->getCurrency(
            $listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        return sprintf(
            'Product was Listed with QTY %d, Price %s',
            $walmartListingProduct->getOnlineQty(),
            $currency->toCurrency($walmartListingProduct->getOnlinePrice())
        );
    }

    //########################################

    /**
     * @param array $feedsPacks
     * @param \Zend_Db_Statement $scheduledActionsDataStatement
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function fillFeedsPacks(array &$feedsPacks, \Zend_Db_Statement $scheduledActionsDataStatement)
    {
        $throttlingManager = $this->modelFactory->getObject('Walmart_ThrottlingManager');

        $canCreateNewPacks = true;

        while ($scheduledActionData = $scheduledActionsDataStatement->fetch()) {
            $availableRequestsCount = $throttlingManager->getAvailableRequestsCount(
                $scheduledActionData['account_id'],
                ActionProcessor::FEED_TYPE_UPDATE_DETAILS
            );

            if ($availableRequestsCount <= 0) {
                continue;
            }

            if ($this->canAddToLastExistedPack($feedsPacks, $scheduledActionData['account_id'])) {
                $this->addToLastExistedPack($feedsPacks, $scheduledActionData);
                continue;
            }

            if (!$canCreateNewPacks) {
                continue;
            }

            $this->addToNewPack($feedsPacks, $scheduledActionData);
        }
    }

    /**
     * @param array $accountsActions
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processExistedSkus(array $accountsActions)
    {
        $removedListingsProductsIds = [];

        foreach ($accountsActions as $accountId => &$accountPacks) {
            foreach ($accountPacks as &$accountData) {
                /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
                $listingProductCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
                $listingProductCollection->addFieldToFilter('id', array_keys($accountData));

                $listingsProductsSkus = [];

                foreach ($accountData as $listingProductId => $listingProductData) {
                    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                    $listingProduct = $listingProductCollection->getItemById($listingProductId);
                    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                    $walmartListingProduct = $listingProduct->getChildObject();

                    if ($walmartListingProduct->getSku()) {
                        $listingsProductsSkus[$listingProductId] = $walmartListingProduct->getSku();
                        continue;
                    }

                    $skuResolver = $this->modelFactory
                        ->getObject('Walmart_Listing_Product_Action_Type_ListAction_SkuResolver');
                    $skuResolver->setListingProduct($listingProduct);
                    $skuResolver->setSkusInCurrentRequest($listingsProductsSkus);

                    $sku = $skuResolver->resolve();

                    $messages = $skuResolver->getMessages();
                    if (!empty($messages)) {
                        $additionalData = $this->getHelper('Data')->jsonDecode($listingProductData['additional_data']);
                        $logger = $this->createLogger($additionalData['params']['status_changer']);

                        foreach ($skuResolver->getMessages() as $message) {
                            $logger->logListingProductMessage($listingProduct, $message);
                        }
                    }

                    if ($sku === null) {
                        unset($accountData[$listingProductId]);
                        $removedListingsProductsIds[] = $listingProductId;

                        continue;
                    }

                    $listingsProductsSkus[$listingProductId] = $sku;
                }

                $productsData = $this->receiveProductsData($accountId, $listingsProductsSkus);

                foreach ($accountData as $listingProductId => &$listingProductData) {
                    $sku = $listingsProductsSkus[$listingProductId];

                    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                    $listingProduct = $listingProductCollection->getItemById($listingProductId);
                    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                    $walmartListingProduct = $listingProduct->getChildObject();

                    if (isset($productsData[$sku])) {
                        $productData = $productsData[$sku];

                        $linking = $this->modelFactory
                            ->getObject('Walmart_Listing_Product_Action_Type_ListAction_Linking');
                        $linking->setListingProduct($listingProduct);
                        $linking->setSku($sku);
                        $linking->setProductIdentifiers(
                            [
                                'wpid'    => $productData['wpid'],
                                'item_id' => $productData['item_id'],
                                'gtin'    => $productData['gtin'],
                                'upc'     => isset($productData['upc']) ? $productData['upc'] : null,
                                'ean'     => isset($productData['ean']) ? $productData['ean'] : null,
                                'isbn'    => isset($productData['isbn']) ? $productData['isbn'] : null,
                            ]
                        );
                        $linking->link();
                    } elseif ($walmartListingProduct->getSku()) {
                        $listingProduct->addData(
                            [
                                'sku'     => null,
                                'wpid'    => null,
                                'item_id' => null,
                                'gtin'    => null,
                                'upc'     => null,
                                'ean'     => null,
                                'isbn'    => null,
                            ]
                        );
                        $listingProduct->save();
                    }

                    $listingProductData['sku'] = $sku;
                }
                unset($listingProductData);
            }
        }

        if (!empty($removedListingsProductsIds)) {
            $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');

            $scheduledActionsCollection = $this->activeRecordFactory
                ->getObject('Listing_Product_ScheduledAction')->getCollection();
            $scheduledActionsCollection->addFieldToFilter('listing_product_id', $removedListingsProductsIds);

            /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction[] $scheduledActions */
            $scheduledActions = $scheduledActionsCollection->getItems();

            foreach ($scheduledActions as $scheduledAction) {
                $scheduledActionManager->deleteAction($scheduledAction);
            }
        }

        return $accountsActions;
    }

    /**
     * @param array $listingsProductsData
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function initProcessingActions(array $listingsProductsData)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingsProductsCollection */
        $listingsProductsCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', array_keys($listingsProductsData));

        $groupHash = $this->getHelper('Data')->generateUniqueHash();

        foreach ($listingsProductsData as $listingProductId => $listingProductData) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($listingProductId);

            $processingRunner = $this->modelFactory
                ->getObject('Walmart_Listing_Product_Action_ListAction_ProcessingRunner');
            $processingRunner->setListingProduct($listingProduct);

            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            if (isset($listingProductData['configurator'])) {
                $configurator = clone $listingProductData['configurator'];
            }

            $params = ['sku' => $listingProductData['sku']];

            if (!empty($listingProductData['additional_data'])) {
                $additionalData = $this->getHelper('Data')->jsonDecode($listingProductData['additional_data']);
                // @codingStandardsIgnoreLine
                !empty($additionalData['params']) && $params = array_merge($params, $additionalData['params']);
            }

            $processingRunner->setParams(
                [
                    'listing_product_id' => $listingProductId,
                    'account_id'         => $listingProduct->getAccount()->getId(),
                    'configurator'       => $configurator->getSerializedData(),
                    'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_LIST,
                    'lock_identifier'    => 'list',
                    'requester_params'   => $params,
                    'group_hash'         => $groupHash,
                ]
            );

            $processingRunner->start();
        }

        return $groupHash;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareProcessingActions()
    {
        $processingActionPreparationLimit = (int)$this->getConfigValue(
            '/walmart/listing/product/action/processing/prepare/',
            'max_listings_products_count'
        );

        /** @var ProcessingCollection $processingActionColl */
        $processingActionColl = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
        $processingActionColl->addFieldToFilter('is_prepared', 0);
        $processingActionColl->addFieldToFilter('type', ActionProcessing::TYPE_ADD);
        $processingActionColl->getSelect()->limit($processingActionPreparationLimit);
        $processingActionColl->getSelect()->order('id ASC');

        /** @var ActionProcessing[] $processingActions */
        $processingActions   = $processingActionColl->getItems();
        $listingsProductsIds = $processingActionColl->getColumnValues('listing_product_id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingsProductsCollection */
        $listingsProductsCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', ['in' => $listingsProductsIds]);

        $processingIds = $processingActionColl->getColumnValues('processing_id');

        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->addFieldToFilter('id', ['in' => $processingIds]);

        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Product_Dispatcher');

        foreach ($processingActions as $processingAction) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($processingAction->getListingProductId());

            if ($listingProduct === null) {
                $processingAction->delete();
                continue;
            }

            /** @var \Ess\M2ePro\Model\Processing $processing */
            $processing = $processingCollection->getItemById($processingAction->getProcessingId());
            $processingAction->setProcessing($processing);

            $listingProduct->setProcessingAction($processingAction);

            $processingParams = $processing->getParams();

            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            $configurator->setUnserializedData($processingParams['configurator']);

            $listingProduct->setActionConfigurator($configurator);

            $params = [];
            if (isset($processingParams['requester_params'])) {
                $params = $processingParams['requester_params'];
            }

            $dispatcher->process($processingParams['action_type'], $listingProduct, $params);
        }
    }

    /**
     * @param array $listingsProductsData
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareScheduledActions(array $listingsProductsData)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingsProductsCollection */
        $listingsProductsCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', array_keys($listingsProductsData));

        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');

        $scheduledActionsCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', array_keys($listingsProductsData));

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction[] $scheduledActions */
        $scheduledActions = $scheduledActionsCollection->getItems();

        foreach ($scheduledActions as $scheduledAction) {
            $scheduledActionManager->deleteAction($scheduledAction);
        }
    }

    /**
     * @param array $processingActions
     * @param array $serverCommand
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processGroupedProcessingActions(array $processingActions, array $serverCommand)
    {
        if (empty($processingActions)) {
            return;
        }

        $account = reset($processingActions)->getListingProduct()->getListing()->getAccount();

        $itemsRequestData = [];

        foreach ($processingActions as $processingAction) {
            $itemsRequestData[$processingAction->getListingProductId()] = $processingAction->getRequestData();
        }

        /** @var \Ess\M2ePro\Model\Walmart\Account $walmartAccount */
        $walmartAccount = $account->getChildObject();

        $requestData = [
            'items'   => $itemsRequestData,
            'account' => $walmartAccount->getServerHash(),
        ];

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        $connector = $dispatcher->getVirtualConnector(
            $serverCommand[0],
            $serverCommand[1],
            $serverCommand[2],
            $requestData,
            null,
            null
        );

        try {
            $dispatcher->process($connector);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            if ($exception instanceof \Ess\M2ePro\Model\Exception\Connection) {
                $isRepeat = $exception->handleRepeatTimeout(self::FIRST_CONNECTION_ERROR_DATE_REGISTRY_KEY);
                if ($isRepeat) {
                    return;
                }
            }

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromException($exception);

            foreach ($processingActions as $processingAction) {
                $this->completeProcessingAction($processingAction, ['errors' => [$message->asArray()]]);
            }

            return;
        }

        $responseData = $connector->getResponseData();
        $responseMessages = $connector->getResponseMessages();

        if (empty($responseData['processing_id'])) {
            foreach ($processingActions as $processingAction) {
                $messages = $responseMessages;

                if (!empty($responseData['data'][$processingAction->getListingProductId() . '-id']['errors'])) {
                    $messages = array_merge(
                        $messages,
                        $responseData['data'][$processingAction->getListingProductId() . '-id']['errors']
                    );
                }

                $this->completeProcessingAction($processingAction, ['errors' => $messages]);
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
        $requestPendingSingle = $this->activeRecordFactory->getObject('Request_Pending_Single');
        $requestPendingSingle->setData(
            [
                'component'       => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'server_hash'     => $responseData['processing_id'],
                'expiration_date' => $this->getHelper('Data')->getDate(
                    $this->getHelper('Data')->getCurrentGmtDate(true) + self::PENDING_REQUEST_MAX_LIFE_TIME
                )
            ]
        );
        $requestPendingSingle->save();

        $actionsIds = [];
        foreach ($processingActions as $processingAction) {
            $actionsIds[] = $processingAction->getId();
        }

        $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')
            ->getResource()->markAsInProgress($actionsIds, $requestPendingSingle);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Zend_Db_Statement
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getScheduledActionsDataStatement(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::LIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_LIST
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("''"))
            ->addFieldToFilter('l.account_id', $account->getId());

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/list/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        $select = $collection->getSelect();

        $select->order(['coefficient DESC']);
        $select->order(['create_date ASC']);

        $limit = (int)$this->getConfigValue('/walmart/listing/product/action/scheduled_data/', 'limit');
        $select->limit($limit);

        return $select->query();
    }

    //########################################

    /**
     * @param array $feedsPacks
     * @param $accountId
     * @return bool
     */
    protected function canAddToLastExistedPack(array $feedsPacks, $accountId)
    {
        if (empty($feedsPacks[$accountId])) {
            return false;
        }

        $lastPackIndex = count($feedsPacks[$accountId]) - 1;
        $maxPackSize = $this->getMaxPackSize(ActionProcessor::FEED_TYPE_UPDATE_DETAILS);

        return count($feedsPacks[$accountId][$lastPackIndex]) < $maxPackSize;
    }

    /**
     * @param array $feedsPacks
     * @param $scheduled
     */
    protected function addToLastExistedPack(array &$feedsPacks, $scheduled)
    {
        if (empty($feedsPacks[$scheduled['account_id']])) {
            $lastPackIndex = 0;
        } else {
            $lastPackIndex = count($feedsPacks[$scheduled['account_id']]) - 1;
        }

        $feedsPacks[$scheduled['account_id']][$lastPackIndex][$scheduled['listing_product_id']] = $scheduled;
    }

    // ---------------------------------------

    /**
     * @param array $feedsPacks
     * @param $scheduled
     */
    protected function addToNewPack(array &$feedsPacks, $scheduled)
    {
        if (empty($feedsPacks[$scheduled['account_id']])) {
            $newPackIndex = 0;
        } else {
            $newPackIndex = count($feedsPacks[$scheduled['account_id']]);
        }

        $feedsPacks[$scheduled['account_id']][$newPackIndex][$scheduled['listing_product_id']] = $scheduled;
    }

    //########################################

    /**
     * @param string $feedType
     * @return int
     */
    protected function getMaxPackSize($feedType = null)
    {
        if ($feedType == ActionProcessor::FEED_TYPE_UPDATE_DETAILS) {
            return 100;
        }

        return 1000;
    }

    //########################################

    /**
     * @param int $accountId
     * @param array $skus
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function receiveProductsData($accountId, array $skus)
    {
        if (empty($skus)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->activeRecordFactory->getCachedObjectLoaded('Account', $accountId);

        /** @var \Ess\M2ePro\Model\Walmart\Account $walmartAccount */
        $walmartAccount = $account->getChildObject();

        $onlyItems = [];
        foreach ($skus as $sku) {
            $onlyItems[] = [
                'type'  => 'sku',
                'value' => $sku,
            ];
        }

        $requestData = [
            'account'    => $walmartAccount->getServerHash(),
            'return_now' => true,
            'only_items' => $onlyItems,
        ];

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        $connector = $dispatcher->getVirtualConnector('inventory', 'get', 'items', $requestData, null, null);

        try {
            $connector->process();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            return [];
        }

        $responseData = $connector->getResponseData();

        if (empty($responseData['data'])) {
            return [];
        }

        $productsData = [];

        foreach ($responseData['data'] as $productData) {
            $productsData[$productData['sku']] = $productData;
        }

        return $productsData;
    }

    //########################################

    /**
     * @param ActionProcessing $action
     * @param array $data
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function completeProcessingAction(ActionProcessing $action, array $data)
    {
        $processing = $action->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        $processing->save();

        $action->delete();
    }

    // ---------------------------------------

    /**
     * @param $group
     * @param $key
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getConfigValue($group, $key)
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue($group, $key);
    }

    /**
     * @param $statusChanger
     * @param int $logsActionId
     * @return \Ess\M2ePro\Model\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function createLogger($statusChanger, $logsActionId = null)
    {
        $logger = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Logger');

        if ($logsActionId === null) {
            $logsActionId = $this->activeRecordFactory->getObject('Listing_Log')->getResource()->getNextActionId();
        }

        $logger->setActionId($logsActionId);
        $logger->setAction(\Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT);

        switch ($statusChanger) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN:
                $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
                break;
            case \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER:
                $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
                break;
            default:
                $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
                break;
        }

        $logger->setInitiator($initiator);

        return $logger;
    }

    //########################################
}
