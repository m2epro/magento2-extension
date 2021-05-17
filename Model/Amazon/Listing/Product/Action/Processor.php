<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

use \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\Processing\Collection as ActionProcessingCollection;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const FEED_TYPE_ADD            = 'list';
    const FEED_TYPE_DELETE         = 'delete';
    const FEED_TYPE_UPDATE_QTY     = 'update_qty';
    const FEED_TYPE_UPDATE_PRICE   = 'update_price';
    const FEED_TYPE_UPDATE_DETAILS = 'update_details';
    const FEED_TYPE_UPDATE_IMAGES  = 'update_images';

    const LIST_PRIORITY           = 25;
    const RELIST_PRIORITY         = 125;
    const STOP_PRIORITY           = 1000;
    const DELETE_PRIORITY         = 1000;
    const REVISE_QTY_PRIORITY     = 500;
    const REVISE_PRICE_PRIORITY   = 250;
    const REVISE_DETAILS_PRIORITY = 50;
    const REVISE_IMAGES_PRIORITY  = 50;

    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    const FIRST_CONNECTION_ERROR_DATE_REGISTRY_KEY = '/amazon/listing/product/action/first_connection_error/date/';

    protected $amazonFactory;
    protected $activeRecordFactory;
    protected $resourceConnection;
    protected $feedsPacks;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
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

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();

        $merchantIds = [];

        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($accountCollection->getItems() as $account) {
            $merchantIds[] = $account->getChildObject()->getMerchantId();
        }

        $merchantIds = array_unique($merchantIds);
        if (empty($merchantIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\ThrottlingManager $throttlingManager */
        $throttlingManager = $this->modelFactory->getObject('Amazon_ThrottlingManager');

        foreach ($merchantIds as $merchantId) {
            $availableRequestsCount = $throttlingManager->getAvailableRequestsCount(
                $merchantId,
                \Ess\M2ePro\Model\Amazon\ThrottlingManager::REQUEST_TYPE_FEED
            );
            if ($availableRequestsCount <= 0) {
                continue;
            }

            $this->feedsPacks = [
                self::FEED_TYPE_ADD            => [],
                self::FEED_TYPE_DELETE         => [],
                self::FEED_TYPE_UPDATE_QTY     => [],
                self::FEED_TYPE_UPDATE_PRICE   => [],
                self::FEED_TYPE_UPDATE_DETAILS => [],
                self::FEED_TYPE_UPDATE_IMAGES  => [],
            ];

            $this->fillFeedsPacks(
                $this->getScheduledActionsDataStatement($merchantId),
                $availableRequestsCount
            );

            $actionsDataForProcessing = $this->prepareAccountsActions();

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
        }

        $this->prepareProcessingActions();

        $fullyPreparedGroupHashes = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product_Action_Processing')->getResource()->getFullyPreparedGroupHashes();

        foreach ($fullyPreparedGroupHashes as $groupHash) {
            $processingActionCollection = $this->activeRecordFactory
                ->getObject('Amazon_Listing_Product_Action_Processing')->getCollection();
            $processingActionCollection->addFieldToFilter('group_hash', $groupHash);
            $processingActionCollection->addFieldToFilter('is_prepared', 1);
            $processingActionCollection->addFieldToFilter('request_pending_single_id', ['null' => true]);

            $processingActionsByType = [];

            foreach ($processingActionCollection->getItems() as $processingAction) {
                if (!isset($processingActionsByType[$processingAction->getType()])) {
                    $processingActionsByType[$processingAction->getType()] = [];
                }

                $processingActionsByType[$processingAction->getType()][] = $processingAction;
            }

            foreach ($processingActionsByType as $actionType => $processingActions) {
                $this->processGroupedProcessingActions($processingActions, $actionType);
            }
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
            ->getObject('Amazon_Listing_Product_Action_Processing')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $actionCollection->addFieldToFilter('p.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing[] $actions */
        $actions = $actionCollection->getItems();

        foreach ($actions as $action) {
            $action->delete();
        }
    }

    // ---------------------------------------

    /**
     * @param \Zend_Db_Statement $scheduledActionsDataStatement
     * @param int $availableRequestsCount
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function fillFeedsPacks(
        \Zend_Db_Statement $scheduledActionsDataStatement,
        $availableRequestsCount = null
    ) {
        $canCreateNewPacks = true;

        while ($scheduledActionData = $scheduledActionsDataStatement->fetch()) {
            $feedTypes = $this->getFeedTypes($scheduledActionData['action_type'], $scheduledActionData['filtered_tag']);

            $canBeAdded = false;

            foreach ($feedTypes as $feedType) {
                if ($this->canAddToLastExistedPack($feedType, $scheduledActionData['account_id'])) {
                    $canBeAdded = true;
                    continue;
                }

                if (!$canCreateNewPacks || ($availableRequestsCount !== null && $availableRequestsCount <= 0)) {
                    $canBeAdded = false;
                    break;
                }

                $canBeAdded = true;
            }

            if (!$canBeAdded) {
                $canCreateNewPacks = false;
                continue;
            }

            foreach ($feedTypes as $feedType) {
                if ($this->canAddToLastExistedPack($feedType, $scheduledActionData['account_id'])) {
                    $this->addToLastExistedPack($feedType, $scheduledActionData);
                    continue;
                }

                if (!$canCreateNewPacks) {
                    continue;
                }

                $this->addToNewPack($feedType, $scheduledActionData);
                $availableRequestsCount--;
            }
        }
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareAccountsActions()
    {
        $result = [];

        foreach ($this->feedsPacks as $feedType => $feedPacks) {
            foreach ($feedPacks as $accountId => $accountPacks) {
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
                            ->getObject('Amazon_Listing_Product_Action_Configurator');

                        $additionalData = (array)$this->getHelper('Data')
                            ->jsonDecode($listingProductData['additional_data']);

                        if (!empty($additionalData['configurator'])) {
                            $listingProductConfigurator->setUnserializedData($additionalData['configurator']);
                        }

                        if (!empty($result[$accountId][$actionType][$listingProductId]['configurator'])) {
                            $configurator = $result[$accountId][$actionType][$listingProductId]['configurator'];
                        } else {
                            /** @var Configurator $configurator */
                            $configurator = $this->modelFactory
                                ->getObject('Amazon_Listing_Product_Action_Configurator');
                            $configurator->disableAll();
                        }

                        switch ($listingProductData['filtered_tag']) {
                            case 'qty':
                                if ($listingProductConfigurator->isQtyAllowed()) {
                                    $configurator->allowQty();
                                }
                                break;

                            case 'price':
                                if ($listingProductConfigurator->isRegularPriceAllowed()) {
                                    $configurator->allowRegularPrice();
                                }

                                if ($listingProductConfigurator->isBusinessPriceAllowed()) {
                                    $configurator->allowBusinessPrice();
                                }
                                break;

                            case 'details':
                                if ($listingProductConfigurator->isDetailsAllowed()) {
                                    $configurator->allowDetails();
                                }
                                break;

                            case 'images':
                                if ($listingProductConfigurator->isImagesAllowed()) {
                                    $configurator->allowImages();
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

                        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
                        $configurator = $listingProductData['configurator'];
                        if ($configurator->isDetailsAllowed()) {
                            $groupHashesMetadata[$accountId][$groupHash]['slow_actions_count']++;
                        }
                    }

                    if ($listingProductData['action_type'] == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
                        $groupHashesMetadata[$accountId][$groupHash]['slow_actions_count']++;
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

        if ($listingProductData['action_type'] == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
            $metadata = $groupHashesMetadata[$accountId][$lastGroupHash];
            if ($metadata['slow_actions_count'] < $this->getMaxPackSize(self::FEED_TYPE_ADD)) {
                return $lastGroupHash;
            }

            return $this->getHelper('Data')->generateUniqueHash();
        }

        if ($listingProductData['action_type'] != \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE) {
            return $lastGroupHash;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
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
        $listingsProductsCollection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', array_keys($listingsProductsData));

        foreach ($listingsProductsData as $listingProductId => $listingProductData) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($listingProductId);

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Product\ProcessingRunner $processingRunner */
            $processingRunner = $this->modelFactory->getObject('Amazon_Connector_Product_ProcessingRunner');
            if ($actionType == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
                $processingRunner = $this->modelFactory
                    ->getObject('Amazon_Connector_Product_ListAction_ProcessingRunner');
            }

            $processingRunner->setListingProduct($listingProduct);

            $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
            if (isset($listingProductData['configurator'])) {
                $configurator = clone $listingProductData['configurator'];
            }

            $params = [];
            if (!empty($listingProductData['additional_data'])) {
                $additionalData = (array)$this->getHelper('Data')->jsonDecode($listingProductData['additional_data']);
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
            '/amazon/listing/product/action/processing/prepare/',
            'max_listings_products_count'
        );

        /** @var ActionProcessingCollection $processingActionColl */
        $processingActionColl = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product_Action_Processing')->getCollection();
        $processingActionColl->addFieldToFilter('is_prepared', 0);
        $processingActionColl->getSelect()->limit($processingActionPreparationLimit);
        $processingActionColl->getSelect()->order('id ASC');

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing[] $processingActions */
        $processingActions = $processingActionColl->getItems();

        $listingsProductsIds = $processingActionColl->getColumnValues('listing_product_id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingsProductsCollection */
        $listingsProductsCollection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', ['in' => $listingsProductsIds]);

        $processingIds = $processingActionColl->getColumnValues('processing_id');

        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->addFieldToFilter('id', ['in' => $processingIds]);

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Product\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Product_Dispatcher');

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

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
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
        $listingsProductsCollection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
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

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $newConfigurator */
            $newConfigurator = $listingProductData['configurator'];

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $existedConfigurator */
            $existedConfigurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');

            $tags = array_filter(explode('/', $scheduledAction->getTag()));
            $tags = array_flip($tags);

            $additionalData = $scheduledAction->getAdditionalData();
            if (!empty($additionalData['configurator'])) {
                $existedConfigurator->setUnSerializedData($additionalData['configurator']);
            }

            foreach ($newConfigurator->getAllowedDataTypes() as $allowedDataType) {
                switch ($allowedDataType) {
                    case 'qty':
                        $existedConfigurator->disallowQty();
                        unset($tags['qty']);
                        break;

                    case 'regular_price':
                    case 'business_price':
                        $existedConfigurator->disallowRegularPrice();
                        $existedConfigurator->disallowBusinessPrice();

                        unset($tags['price']);

                        break;

                    case 'details':
                        $existedConfigurator->disallowDetails();
                        unset($tags['details']);
                        break;

                    case 'images':
                        $existedConfigurator->disallowImages();
                        unset($tags['images']);
                        break;
                }
            }

            $tags = array_keys($tags);

            $additionalData['configurator'] = $existedConfigurator->getSerializedData();
            $scheduledAction->setSettings('additional_data', $additionalData);

            if (empty($existedConfigurator->getAllowedDataTypes())) {
                $scheduledActionManager->deleteAction($scheduledAction);
            } else {
                $scheduledAction->setData('tag', '/'.trim(implode('/', $tags), '/').'/');
                $scheduledActionManager->updateAction($scheduledAction);
            }
        }
    }

    /**
     * @param array $processingActions
     * @param $actionType
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processGroupedProcessingActions(array $processingActions, $actionType)
    {
        if (empty($processingActions)) {
            return;
        }

        $account = reset($processingActions)->getListingProduct()->getListing()->getAccount();

        $itemsRequestData = [];

        foreach ($processingActions as $processingAction) {
            $itemsRequestData[$processingAction->getListingProductId()] = $processingAction->getRequestData();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
        $amazonAccount = $account->getChildObject();

        $requestData = [
            'items'   => $itemsRequestData,
            'account' => $amazonAccount->getServerHash(),
        ];

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

        $command = $this->getServerCommand($actionType);

        $connector = $dispatcher->getVirtualConnector(
            $command[0],
            $command[1],
            $command[2],
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
                $this->completeProcessingAction($processingAction, ['messages' => [$message->asArray()]]);
            }

            return;
        }

        $responseData = $connector->getResponseData();
        $responseMessages = $connector->getResponseMessages();

        if (empty($responseData['processing_id'])) {
            foreach ($processingActions as $processingAction) {
                $messages = $this->getResponseMessages(
                    $responseData,
                    $responseMessages,
                    $processingAction->getListingProductId()
                );
                $this->completeProcessingAction($processingAction, ['messages' => $messages]);
            }

            return;
        }

        $requestPendingSingle = $this->activeRecordFactory->getObject('Request_Pending_Single');
        $requestPendingSingle->setData(
            [
                'component'       => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'server_hash'     => $responseData['processing_id'],
                'expiration_date' => $this->getHelper('Data')->getDate(
                    $this->getHelper('Data')->getCurrentGmtDate(true)+self::PENDING_REQUEST_MAX_LIFE_TIME
                )
            ]
        );
        $requestPendingSingle->save();

        $actionsIds = [];
        foreach ($processingActions as $processingAction) {
            $actionsIds[] = $processingAction->getId();
        }

        $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing')
            ->getResource()->markAsInProgress($actionsIds, $requestPendingSingle);
    }

    //########################################

    /**
     * @param $merchantId
     * @return \Zend_Db_Statement
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    protected function getScheduledActionsDataStatement($merchantId)
    {
        $connection = $this->resourceConnection->getConnection();

        $unionSelect = $connection->select()->union(
            [
                $this->getListScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getRelistQtyScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getRelistPriceScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getReviseQtyScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getRevisePriceScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getReviseDetailsScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getReviseImagesScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getStopScheduledActionsPreparedCollection($merchantId)->getSelect(),
                $this->getDeleteScheduledActionsPreparedCollection($merchantId)->getSelect(),
            ]
        );

        $unionSelect->order(['coefficient DESC']);
        $unionSelect->order(['create_date ASC']);

        $limit = (int)$this->getConfigValue('/amazon/listing/product/action/scheduled_data/', 'limit');
        $unionSelect->limit($limit);

        return $unionSelect->query();
    }

    // ---------------------------------------

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getListScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::LIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_LIST
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("''"))
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/list/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRelistQtyScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::RELIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'qty'"))
            ->addTagFilter('qty', true)
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/relist/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRelistPriceScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::RELIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'price'"))
            ->addTagFilter('price', true)
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/relist/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseQtyScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_QTY_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'qty'"))
            ->addTagFilter('qty', true)
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/revise_qty/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRevisePriceScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_PRICE_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'price'"))
            ->addTagFilter('price', true)
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/revise_price/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseDetailsScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_DETAILS_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'details'"))
            ->addTagFilter('details', true)
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/revise_details/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseImagesScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_IMAGES_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("'images'"))
            ->addTagFilter('images', true)
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/revise_images/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStopScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::STOP_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("''"))
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/stop/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getDeleteScheduledActionsPreparedCollection($merchantId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->getScheduledActionsPreparedCollection(
                self::DELETE_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE
            )
            ->joinAccountTable()
            ->addFilteredTagColumnToSelect(new \Zend_Db_Expr("''"))
            ->addFieldToFilter('account.merchant_id', $merchantId);

        if ($this->getHelper('Module')->isProductionEnvironment()) {
            $minAllowedWaitInterval = (int)$this->getConfigValue(
                '/amazon/listing/product/action/delete/',
                'min_allowed_wait_interval'
            );
            $collection->addCreatedBeforeFilter($minAllowedWaitInterval);
        }

        return $collection;
    }

    //########################################

    /**
     * @param string $feedType
     * @param int $accountId
     * @return bool
     */
    protected function canAddToLastExistedPack($feedType, $accountId)
    {
        if (empty($this->feedsPacks[$feedType][$accountId])) {
            return false;
        }

        $lastPackIndex = count($this->feedsPacks[$feedType][$accountId]) - 1;

        return count($this->feedsPacks[$feedType][$accountId][$lastPackIndex]) < $this->getMaxPackSize($feedType);
    }

    /**
     * @param string $feedType
     * @param array $scheduledActionData
     */
    protected function addToLastExistedPack($feedType, $scheduledActionData)
    {
        if (empty($this->feedsPacks[$feedType][$scheduledActionData['account_id']])) {
            $lastPackIndex = 0;
        } else {
            $lastPackIndex = count($this->feedsPacks[$feedType][$scheduledActionData['account_id']]) - 1;
        }

        $this->feedsPacks[$feedType][$scheduledActionData['account_id']][$lastPackIndex][] = $scheduledActionData;
    }

    // ---------------------------------------

    /**
     * @param string $feedType
     * @param array $scheduledActionData
     */
    protected function addToNewPack($feedType, $scheduledActionData)
    {
        if (empty($this->feedsPacks[$feedType][$scheduledActionData['account_id']])) {
            $newPackIndex = 0;
        } else {
            $newPackIndex = count($this->feedsPacks[$feedType][$scheduledActionData['account_id']]);
        }

        $this->feedsPacks[$feedType][$scheduledActionData['account_id']][$newPackIndex][] = $scheduledActionData;
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
        switch ($actionType) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return [self::FEED_TYPE_ADD];

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $feedTypes = [];

                if ($tag == 'qty') {
                    $feedTypes[] = self::FEED_TYPE_UPDATE_QTY;
                }

                if ($tag == 'price') {
                    $feedTypes[] = self::FEED_TYPE_UPDATE_PRICE;
                }

                return $feedTypes;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $feedTypes = [];

                if ($tag == 'qty') {
                    $feedTypes[] = self::FEED_TYPE_UPDATE_QTY;
                }

                if ($tag == 'price') {
                    $feedTypes[] = self::FEED_TYPE_UPDATE_PRICE;
                }

                if ($tag == 'details') {
                    $feedTypes[] = self::FEED_TYPE_UPDATE_DETAILS;
                }

                if ($tag == 'images') {
                    $feedTypes[] = self::FEED_TYPE_UPDATE_IMAGES;
                }
                return $feedTypes;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return [self::FEED_TYPE_UPDATE_QTY];

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return [self::FEED_TYPE_DELETE];

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type.');
        }
    }

    /**
     * @param $feedType
     * @return int
     */
    protected function getMaxPackSize($feedType)
    {
        $slowFeedTypes = [
            self::FEED_TYPE_ADD,
            self::FEED_TYPE_UPDATE_DETAILS,
        ];

        if (in_array($feedType, $slowFeedTypes)) {
            return 1000;
        }

        return 10000;
    }

    //########################################

    /**
     * @param Processing $action
     * @param array $data
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function completeProcessingAction(Processing $action, array $data)
    {
        $processing = $action->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        $processing->save();

        $action->delete();
    }

    /**
     * @param array $responseData
     * @param array $responseMessages
     * @param $listingProductId
     * @return array
     */
    protected function getResponseMessages(array $responseData, array $responseMessages, $listingProductId)
    {
        $messages = $responseMessages;

        if (!empty($responseData['messages'][0])) {
            $messages = array_merge($messages, $responseData['messages']['0']);
        }

        if (!empty($responseData['messages']['0-id'])) {
            $messages = array_merge($messages, $responseData['messages']['0-id']);
        }

        if (!empty($responseData['messages'][$listingProductId.'-id'])) {
            $messages = array_merge($messages, $responseData['messages'][$listingProductId.'-id']);
        }

        return $messages;
    }

    /**
     * @param $processingActionType
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getServerCommand($processingActionType)
    {
        switch ($processingActionType) {
            case Processing::TYPE_ADD:
                return ['product', 'add', 'entities'];

            case Processing::TYPE_UPDATE:
                return ['product', 'update', 'entities'];

            case Processing::TYPE_DELETE:
                return ['product', 'delete', 'entities'];

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
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
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return 'list';
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

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return 'delete_and_remove';
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
