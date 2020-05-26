<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action;

use \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action\Processing\Collection as ProcessingCollection;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const FEED_TYPE_UPDATE_QTY        = 'update_qty';
    const FEED_TYPE_UPDATE_LAG_TIME   = 'update_lag_time';
    const FEED_TYPE_UPDATE_PRICE      = 'update_price';
    const FEED_TYPE_UPDATE_PROMOTIONS = 'update_promotions';
    const FEED_TYPE_UPDATE_DETAILS    = 'update_details';

    const RELIST_PRIORITY            = 125;
    const STOP_PRIORITY              = 1000;
    const REVISE_QTY_PRIORITY        = 500;
    const REVISE_LAG_TIME_PRIORITY   = 500;
    const REVISE_PRICE_PRIORITY      = 250;
    const REVISE_DETAILS_PRIORITY    = 50;
    const REVISE_PROMOTIONS_PRIORITY = 50;

    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    const FIRST_CONNECTION_ERROR_DATE_REGISTRY_KEY = '/walmart/listing/product/action/first_connection_error/date/';

    protected $walmartFactory;
    protected $activeRecordFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function process()
    {
        $this->removeMissedProcessingActions();

        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Account\Collection $accountCollection */
        $accountCollection = $this->walmartFactory->getObject('Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($accountCollection->getItems() as $account) {
            $feedsPacks               = $this->getFilledPacksByFeeds($account);
            $actionsDataForProcessing = $this->prepareAccountsActions($feedsPacks);

            $requestsPacks = $this->prepareRequestsPacks($actionsDataForProcessing);

            foreach ($requestsPacks as $accountId => $accountPacks) {
                foreach ($accountPacks as $actionType => $groupPacks) {
                    foreach ($groupPacks as $groupHash => $packData) {
                        if (empty($packData)) {
                            continue;
                        }

                        $this->initProcessingActions($actionType, $packData, $groupHash);
                        $this->prepareScheduledActions($packData);
                    }
                }
            }

            $this->registerRequestsInThrottling($feedsPacks);
        }

        $this->prepareProcessingActions();

        $fullyPreparedGroupHashes = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getResource()->getFullyPreparedGroupHashes();

        foreach ($fullyPreparedGroupHashes as $groupHash) {
            $processingActionCollection = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
            $processingActionCollection->addFieldToFilter('group_hash', $groupHash);
            $processingActionCollection->addFieldToFilter('is_prepared', 1);
            $processingActionCollection->addFieldToFilter(
                'type',
                [
                    'in' => [\Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing::TYPE_UPDATE]
                ]
            );
            $processingActionCollection->addFieldToFilter('request_pending_single_id', ['null' => true]);

            $this->processGroupedProcessingActions($processingActionCollection->getItems());
        }
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function removeMissedProcessingActions()
    {
        $actionCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $actionCollection->addFieldToFilter('p.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing[] $actions */
        $actions = $actionCollection->getItems();

        foreach ($actions as $action) {
            $action->delete();
        }
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array|false
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getFilledPacksByFeeds(\Ess\M2ePro\Model\Account $account)
    {
        $availableFeeds = [
            self::FEED_TYPE_UPDATE_DETAILS,
            self::FEED_TYPE_UPDATE_PROMOTIONS,
            self::FEED_TYPE_UPDATE_QTY,
            self::FEED_TYPE_UPDATE_LAG_TIME,
            self::FEED_TYPE_UPDATE_PRICE,
        ];

        $canCreateNewPacksByFeedType = array_combine(
            $availableFeeds,
            array_fill(0, count($availableFeeds), true)
        );

        $feedsPacks = array_combine(
            $availableFeeds,
            array_fill(0, count($availableFeeds), [])
        );

        /** @var \Ess\M2ePro\Model\Walmart\ThrottlingManager $throttlingManager */
        $throttlingManager = $this->modelFactory->getObject('Walmart_ThrottlingManager');
        $scheduledActionsDataStatement = $this->getScheduledActionsDataStatement($account);

        while ($scheduledActionData = $scheduledActionsDataStatement->fetch()) {
            $feedTypes = $this->getFeedTypes($scheduledActionData['action_type'], $scheduledActionData['filtered_tag']);

            $canBeAdded = false;

            foreach ($feedTypes as $feedType) {
                if ($this->canAddToLastExistedPack($feedsPacks, $feedType, $scheduledActionData['account_id'])) {
                    $canBeAdded = true;
                    continue;
                }

                $availableRequestsCount = $throttlingManager->getAvailableRequestsCount(
                    $scheduledActionData['account_id'],
                    $feedType
                );

                if (!$canCreateNewPacksByFeedType[$feedType] || $availableRequestsCount <= 0) {
                    $canBeAdded = false;
                    $canCreateNewPacksByFeedType[$feedType] = false;
                    break;
                }

                $canBeAdded = true;
            }

            if (!$canBeAdded) {
                continue;
            }

            foreach ($feedTypes as $feedType) {
                if ($this->canAddToLastExistedPack($feedsPacks, $feedType, $scheduledActionData['account_id'])) {
                    $this->addToLastExistedPack($feedsPacks, $feedType, $scheduledActionData);
                    continue;
                }

                if (!$canCreateNewPacksByFeedType[$feedType]) {
                    continue;
                }

                $this->addToNewPack($feedsPacks, $feedType, $scheduledActionData);
            }
        }

        return $feedsPacks;
    }

    /**
     * @param array $feedsPacks
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareAccountsActions(array $feedsPacks)
    {
        $result = [];
        $accounts = [];

        foreach ($feedsPacks as $feedType => $feedPacks) {
            foreach ($feedPacks as $accountId => $accountPacks) {
                if (!isset($accounts[$accountId])) {
                    $accounts[$accountId] = $this->walmartFactory->getObjectLoaded('Account', $accountId);
                }

                foreach ($accountPacks as $accountPack) {
                    foreach ($accountPack as $listingProductData) {
                        $listingProductId = $listingProductData['listing_product_id'];
                        $actionType       = $listingProductData['action_type'];

                        if (empty($result[$accountId][$actionType])) {
                            $result[$accountId][$actionType] = [];
                        }

                        if ($actionType != \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE &&
                            $actionType != \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST
                        ) {
                            $result[$accountId][$actionType][$listingProductId] = $listingProductData;
                            continue;
                        }

                        /** @var Configurator $listingProductConfigurator */
                        $listingProductConfigurator = $this->modelFactory
                            ->getObject('Walmart_Listing_Product_Action_Configurator');

                        $additionalData = $this->getHelper('Data')->jsonDecode($listingProductData['additional_data']);
                        if (!empty($additionalData['configurator'])) {
                            $listingProductConfigurator->setUnserializedData($additionalData['configurator']);
                        }

                        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
                        if (!empty($result[$accountId][$actionType][$listingProductId]['configurator'])) {
                            $configurator = $result[$accountId][$actionType][$listingProductId]['configurator'];
                        } else {
                            $configurator = $this->modelFactory
                                ->getObject('Walmart_Listing_Product_Action_Configurator');
                            $configurator->disableAll();
                        }

                        switch ($listingProductData['filtered_tag']) {
                            case 'qty':
                                if ($listingProductConfigurator->isQtyAllowed()) {
                                    $configurator->allowQty();
                                }
                                break;

                            case 'lag_time':
                                if ($listingProductConfigurator->isLagTimeAllowed()) {
                                    $configurator->allowLagTime();
                                }
                                break;

                            case 'price':
                                if ($listingProductConfigurator->isPriceAllowed()) {
                                    $configurator->allowPrice();
                                }
                                break;

                            case 'promotions':
                                if ($listingProductConfigurator->isPromotionsAllowed()) {
                                    $configurator->allowPromotions();
                                }
                                break;

                            case 'details':
                                if ($listingProductConfigurator->isDetailsAllowed()) {
                                    $configurator->allowDetails();
                                }
                                break;
                        }

                        $listingProductData['configurator'] = $configurator;
                        $result[$accountId][$actionType][$listingProductId] = $listingProductData;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $accountsActions
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareRequestsPacks(array $accountsActions)
    {
        $groupHashesMetadata = [];
        $requestsPacks = [];

        foreach ($accountsActions as $accountId => $accountData) {
            foreach ($accountData as $actionType => $actionData) {
                foreach ($actionData as $listingProductId => $listingProductData) {
                    $groupHash = $this->getActualGroupHash($accountId, $groupHashesMetadata, $listingProductData);
                    if (!isset($groupHashesMetadata[$accountId][$groupHash])) {
                        $groupHashesMetadata[$accountId][$groupHash] = [
                            'slow_actions_count' => 0
                        ];
                    }

                    if ($listingProductData['action_type'] == \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE) {

                        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
                        $configurator = $listingProductData['configurator'];
                        if ($configurator->isDetailsAllowed()) {
                            $groupHashesMetadata[$accountId][$groupHash]['slow_actions_count']++;
                        }
                    }

                    $requestsPacks[$accountId][$actionType][$groupHash][$listingProductId] = $listingProductData;
                }
            }
        }

        return $requestsPacks;
    }

    /**
     * @param $accountId
     * @param array $groupHashesMetadata
     * @param array $listingProductData
     * @return int|string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getActualGroupHash($accountId, array $groupHashesMetadata, array $listingProductData)
    {
        if (empty($groupHashesMetadata[$accountId])) {
            return $this->getHelper('Data')->generateUniqueHash();
        }

        end($groupHashesMetadata[$accountId]);
        $lastGroupHash = key($groupHashesMetadata[$accountId]);

        if ($listingProductData['action_type'] != \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE) {
            return $lastGroupHash;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
        $configurator = $listingProductData['configurator'];
        if (!$configurator->isDetailsAllowed()) {
            return $lastGroupHash;
        }

        foreach ($groupHashesMetadata[$accountId] as $groupHash => $metadata) {
            if ($metadata['slow_actions_count'] < $this->getMaxPackSize(self::FEED_TYPE_UPDATE_DETAILS)) {
                return $groupHash;
            }
        }

        return $this->getHelper('Data')->generateUniqueHash();
    }

    /**
     * @param $actionType
     * @param array $listingsProductsData
     * @param $groupHash
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function initProcessingActions($actionType, array $listingsProductsData, $groupHash)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingsProductsCollection */
        $listingsProductsCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', array_keys($listingsProductsData));

        foreach ($listingsProductsData as $listingProductId => $listingProductData) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($listingProductId);

            /** @var \Ess\M2ePro\Model\Walmart\Connector\Product\ProcessingRunner $processingRunner */
            $processingRunner = $this->modelFactory->getObject('Walmart_Connector_Product_ProcessingRunner');
            $processingRunner->setListingProduct($listingProduct);

            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            if (isset($listingProductData['configurator'])) {
                $configurator = clone $listingProductData['configurator'];
            }

            $params = [];
            if (!empty($listingProductData['additional_data'])) {
                $additionalData = $this->getHelper('Data')->jsonDecode($listingProductData['additional_data']);
                !empty($additionalData['params']) && $params = $additionalData['params'];
            }

            $processingRunner->setParams(
                [
                    'account_id'         => $listingProduct->getAccount()->getId(),
                    'listing_product_id' => $listingProductId,
                    'configurator'       => $configurator->getSerializedData(),
                    'action_type'        => $actionType,
                    'lock_identifier'    => $this->getLockIdentifier($actionType, $params),
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
        $processingActionColl->addFieldToFilter(
            'type',
            [
                'in' => [\Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing::TYPE_UPDATE]
            ]
        );
        $processingActionColl->getSelect()->limit($processingActionPreparationLimit);
        $processingActionColl->getSelect()->order('id ASC');

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing[] $processingActions */
        $processingActions = $processingActionColl->getItems();

        $listingsProductsIds = $processingActionColl->getColumnValues('listing_product_id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingsProductsCollection */
        $listingsProductsCollection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', ['in' => $listingsProductsIds]);

        $processingIds = $processingActionColl->getColumnValues('processing_id');

        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->addFieldToFilter('id', ['in' => $processingIds]);

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Product\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Product_Dispatcher');

        foreach ($processingActions as $processingAction) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($processingAction->getListingProductId());

            if ($listingProduct === null) {
                $processingAction->getProcessing()->delete();
                $processingAction->delete();
                continue;
            }

            /** @var \Ess\M2ePro\Model\Processing $processing */
            $processing = $processingCollection->getItemById($processingAction->getProcessingId());
            $processingAction->setProcessing($processing);

            $listingProduct->setProcessingAction($processingAction);

            $processingParams = $processing->getParams();

            /** @var \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator */
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

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager */
        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');

        $scheduledActionsCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', array_keys($listingsProductsData));

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction[] $scheduledActions */
        $scheduledActions = $scheduledActionsCollection->getItems();

        foreach ($scheduledActions as $scheduledAction) {
            $listingProductData = $listingsProductsData[$scheduledAction->getListingProductId()];

            if (!$scheduledAction->isActionTypeRevise() || empty($listingProductData['configurator'])) {
                $scheduledActionManager->deleteAction($scheduledAction);
                continue;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $newConfigurator */
            $newConfigurator = $listingProductData['configurator'];

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $existedConfigurator */
            $existedConfigurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

            $tags = array_filter(explode('/', $scheduledAction->getTag()));
            $tags = array_flip($tags);

            $additionalData = $scheduledAction->getAdditionalData();
            if (!empty($additionalData['configurator'])) {
                $existedConfigurator->setUnserializedData($additionalData['configurator']);
            }

            $existedConfigurator->setModeIncluding();

            foreach ($newConfigurator->getAllowedDataTypes() as $allowedDataType) {
                switch ($allowedDataType) {
                    case 'qty':
                        $existedConfigurator->disallowQty();
                        unset($tags['qty']);
                        break;

                    case 'lag_time':
                        $existedConfigurator->disallowLagTime();
                        unset($tags['lag_time']);
                        break;

                    case 'price':
                        $existedConfigurator->disallowPrice();
                        unset($tags['price']);
                        break;

                    case 'promotions':
                        $existedConfigurator->disallowPromotions();
                        unset($tags['promotions']);
                        break;

                    case 'details':
                        $existedConfigurator->disallowDetails();
                        unset($tags['details']);
                        break;
                }
            }

            $additionalData['configurator'] = $existedConfigurator->getSerializedData();
            $scheduledAction->setSettings('additional_data', $additionalData);

            $types = $existedConfigurator->getAllowedDataTypes();
            if (empty($types)) {
                $scheduledActionManager->deleteAction($scheduledAction);
            } else {
                $tags = array_keys($tags);
                $scheduledAction->setData('tag', '/' . trim(implode('/', $tags), '/') . '/');
                $scheduledActionManager->updateAction($scheduledAction);
            }
        }
    }

    /**
     * @param array $processingActions
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processGroupedProcessingActions(array $processingActions)
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
            'product',
            'update',
            'entities',
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

        $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_Processing')
            ->getResource()->markAsInProgress($actionsIds, $requestPendingSingle);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Zend_Db_Statement
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    protected function getScheduledActionsDataStatement(\Ess\M2ePro\Model\Account $account)
    {

        $connection = $this->resourceConnection->getConnection();

        $unionSelect = $connection->select()->union(
            [
                $this->getRelistQtyScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getRelistPriceScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getReviseQtyScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getReviseLagTimeScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getRevisePriceScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getRevisePromotionsScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getReviseDetailsScheduledActionsPreparedCollection($account->getId())->getSelect(),
                $this->getStopScheduledActionsPreparedCollection($account->getId())->getSelect()
            ]
        );

        $unionSelect->order(['coefficient DESC']);
        $unionSelect->order(['create_date ASC']);

        $limit = (int)$this->getConfigValue('/walmart/listing/product/action/scheduled_data/', 'limit');
        $unionSelect->limit($limit);

        return $unionSelect->query();
    }

    // ---------------------------------------

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRelistQtyScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::RELIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'qty'"))
            ->addTagFilter('qty', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/relist/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRelistPriceScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::RELIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'price'"))
            ->addTagFilter('price', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/relist/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseQtyScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_QTY_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'qty'"))
            ->addTagFilter('qty', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/revise_qty/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseLagTimeScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_LAG_TIME_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'lag_time'"))
            ->addTagFilter('lag_time', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/revise_lag_time/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRevisePriceScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_PRICE_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'price'"))
            ->addTagFilter('price', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/revise_price/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRevisePromotionsScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_PROMOTIONS_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'promotions'"))
            ->addTagFilter('promotions', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/revise_promotions/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseDetailsScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_DETAILS_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'details'"))
            ->addTagFilter('details', true)
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/revise_details/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $accountId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStopScheduledActionsPreparedCollection($accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->getScheduledActionsPreparedCollection(
                self::STOP_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
            )
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("''"))
            ->addFieldToFilter('l.account_id', $accountId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/walmart/listing/product/action/stop/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    //########################################

    /**
     * @param array $feedsPacks
     * @param $feedType
     * @param $accountId
     * @return bool
     */
    protected function canAddToLastExistedPack(array $feedsPacks, $feedType, $accountId)
    {
        if (empty($feedsPacks[$feedType][$accountId])) {
            return false;
        }

        $lastPackIndex = count($feedsPacks[$feedType][$accountId]) - 1;

        return count($feedsPacks[$feedType][$accountId][$lastPackIndex]) < $this->getMaxPackSize($feedType);
    }

    /**
     * @param array $feedsPacks
     * @param $feedType
     * @param $scheduledActionData
     */
    protected function addToLastExistedPack(array &$feedsPacks, $feedType, $scheduledActionData)
    {
        if (empty($feedsPacks[$feedType][$scheduledActionData['account_id']])) {
            $lastPackIndex = 0;
        } else {
            $lastPackIndex = count($feedsPacks[$feedType][$scheduledActionData['account_id']]) - 1;
        }

        $feedsPacks[$feedType][$scheduledActionData['account_id']][$lastPackIndex][] = $scheduledActionData;
    }

    // ---------------------------------------

    /**
     * @param array $feedsPacks
     * @param $feedType
     * @param $scheduledActionData
     */
    protected function addToNewPack(array &$feedsPacks, $feedType, $scheduledActionData)
    {
        if (empty($feedsPacks[$feedType][$scheduledActionData['account_id']])) {
            $newPackIndex = 0;
        } else {
            $newPackIndex = count($feedsPacks[$feedType][$scheduledActionData['account_id']]);
        }

        $feedsPacks[$feedType][$scheduledActionData['account_id']][$newPackIndex][] = $scheduledActionData;
    }

    //########################################

    /**
     * @param $actionType
     * @param string $tag
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getFeedTypes($actionType, $tag = null)
    {
        if (!in_array(
            $actionType,
            [
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
            ]
        )) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type.');
        }

        $feedTypesByTags = [
            'qty'        => self::FEED_TYPE_UPDATE_QTY,
            'lag_time'   => self::FEED_TYPE_UPDATE_LAG_TIME,
            'price'      => self::FEED_TYPE_UPDATE_PRICE,
            'promotions' => self::FEED_TYPE_UPDATE_PROMOTIONS,
            'details'    => self::FEED_TYPE_UPDATE_DETAILS,
        ];

        $feedTypes = [];
        if ($actionType == \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {
            $feedTypes[] = self::FEED_TYPE_UPDATE_QTY;

            if ($tag === 'price' || $tag === 'promotions') {
                $feedTypes[] = $feedTypesByTags[$tag];
            }

            return $feedTypes;
        }

        if ($actionType == \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE) {
            if ($tag !== null && isset($feedTypesByTags[$tag])) {
                $feedTypes[] = $feedTypesByTags[$tag];
            }

            return $feedTypes;
        }

        if ($actionType == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
            $feedTypes[] = self::FEED_TYPE_UPDATE_QTY;

            return $feedTypes;
        }

        return $feedTypes;
    }

    /**
     * @param $feedType
     * @return int
     */
    protected function getMaxPackSize($feedType)
    {
        if ($feedType == self::FEED_TYPE_UPDATE_DETAILS) {
            return 100;
        }

        return 1000;
    }

    //########################################

    /**
     * @param $feedsPacks
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function registerRequestsInThrottling($feedsPacks)
    {
        $throttlingManager = $this->modelFactory->getObject('Walmart_ThrottlingManager');

        foreach ($feedsPacks as $feedType => $feedPacks) {
            foreach ($feedPacks as $accountId => $accountPacks) {
                $throttlingManager->registerRequests($accountId, $feedType, count($accountPacks));
            }
        }
    }

    //########################################

    /**
     * @param Processing $action
     * @param array $data
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function completeProcessingAction(
        \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing $action,
        array $data
    ) {
        $processing = $action->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        $processing->save();

        $action->delete();
    }

    /**
     * @param $actionType
     * @param array $params
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getLockIdentifier($actionType, array $params)
    {
        switch ($actionType) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return 'relist';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return 'revise';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                if (!empty($params['remove'])) {
                    return 'stop_and_remove';
                } else {
                    return 'stop';
                }
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
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

    //########################################
}
