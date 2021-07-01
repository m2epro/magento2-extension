<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;
use Ess\M2ePro\Model\Exception\Logic;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
 */
abstract class Responser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
    protected $configurator;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response */
    protected $responseObject;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData */
    protected $requestDataObject;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger $logger */
    protected $logger;

    /** @var EbayVariation\Resolver */
    protected $variationResolver;

    protected $isSuccess = false;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        EbayVariation\Resolver $variationResolver,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );

        $this->variationResolver = $variationResolver;
        $this->listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $this->params['product']['id']);
    }

    //########################################

    protected function validateResponse()
    {
        return true;
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $messageText,
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }

    public function eventAfterExecuting()
    {
        if ($this->isTemporaryErrorAppeared($this->getResponse()->getMessages()->getEntities())) {
            $this->getResponseObject()->throwRepeatActionInstructions();
        }

        parent::eventAfterExecuting();
    }

    //########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        $messages = [];

        $requestLogMessages = isset($this->params['product']['request_metadata']['log_messages'])
            ? $this->params['product']['request_metadata']['log_messages'] : [];

        foreach ($requestLogMessages as $messageData) {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $messages[] = $message;
        }

        $messages = array_merge($messages, $this->getResponse()->getMessages()->getEntities());

        $this->processMessages($messages);
    }

    protected function processResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getPreparedResponseData();
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        $this->processCompleted(
            $responseData,
            [
                'is_images_upload_error' => $this->isImagesUploadFailed($responseMessages)
            ]
        );
    }

    protected function processMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }
    }

    protected function processCompleted(array $data = [], array $params = [])
    {
        $this->getResponseObject()->processSuccess($data, $params);

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $this->getSuccessfulMessage(),
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        if ($message->getText() !== null) {
            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }

        $this->isSuccess = true;
    }

    //----------------------------------------

    abstract protected function getSuccessfulMessage();

    //########################################

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * eBay internal error. The operation was not completed (code:34) (returned by M2e Pro server)
     */
    protected function isEbayApplicationErrorAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if (strpos($message->getText(), 'code:34') !== false) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 32704531: Can't upload product image on eBay (returned by M2e Pro server)
     */
    protected function isImagesUploadFailed(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 32704531) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 17: This item cannot be accessed because the listing has been deleted, is a Half.com listing,
     *     or you are not the seller.
     */
    protected function isItemCanNotBeAccessed(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 17) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 21919301: (UPC/EAN/ISBN) is missing a value. Enter a value and try again.
     */
    protected function isProductIdentifierNeeded(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21919301) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 21919303: The item specific Type is missing. Add Type to this listing, enter a valid value, and then try again.
     */
    protected function isNewRequiredSpecificNeeded(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21919303) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 21916587: The multi-variation titles have been changed and were not updated on the eBay.
     * 21916626: Variations Specifics and Item Specifics entered for a Multi-SKU item should be different.
     * 21916603: Variation specifics cannot be changed in restricted revise
     * 21916664: Variation Specifics provided does not match with the variation specifics of the variations on the item.
     * 21916585: Duplicate custom variation label.
     * 21916582: Duplicate VariationSpecifics trait value in the VariationSpecificsSet container.
     * 21916672: The tags (MPN) is/are disabled as Variant.
     * 21919061: This item was created from Selling Manager product, but the VariationSpecifics or V
     *           ariationSpecificsSet provided for this item does not match with the product.
     *           Please update variation specifics on the product and try again.
     */
    protected function isVariationErrorAppeared(array $messages)
    {
        $errorCodes = [
            21916587,
            21916626,
            21916603,
            21916664,
            21916585,
            21916582,
            21916672,
            21919061,
        ];

        foreach ($messages as $message) {
            if (in_array($message->getCode(), $errorCodes)) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 21916884: Condition is required for this category.
     */
    protected function isConditionErrorAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21916884) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 488: The specified UUID has already been used; ListedByRequestAppId=1, item ID=%item_id%.
     */
    protected function isDuplicateErrorByUUIDAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 488) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return Message|bool
     *
     * 21919067: This Listing is a duplicate of your item: %item_title% (%item_id%).
     */
    protected function isDuplicateErrorByEbayEngineAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21919067) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message|bool
     *
     * 10007: Sorry, Something Went Wrong. Please Wait A Moment And Try Again.
     * 931: The token of eBay account is no longer valid. Please edit your eBay account and get a new token.
     */
    protected function isTemporaryErrorAppeared(array $messages)
    {
        $errorCodes = [
            10007,
            931
        ];

        foreach ($messages as $message) {
            if (in_array($message->getCode(), $errorCodes, true)) {
                return $message;
            }
        }

        return false;
    }

    //########################################

    /**
     * @throws Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function tryToResolveVariationErrors()
    {
        if (isset($this->params['params']['is_additional_action']) &&
            $this->params['params']['is_additional_action'] === true
        ) {
            return;
        }

        if (!$this->canPerformGetItemCall()) {
            return;
        }

        $additionalData = $this->listingProduct->getAdditionalData();

        $this->variationResolver
            ->setListingProduct($this->listingProduct)
            ->setIsAllowedToSave(true)
            ->setIsAllowedToProcessVariationsWhichAreNotExistInTheModule(true)
            ->setIsAllowedToProcessVariationMpnErrors(!isset($additionalData['is_variation_mpn_filled']));

        $this->variationResolver->resolve();

        foreach ($this->variationResolver->getMessagesSet()->getEntities() as $resolverMessage) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $resolverMessage->getText(),
                $resolverMessage->getType()
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $this->getHelper('Module\Translation')->__(
                'It has been detected that this Item failed to be updated on eBay because of the errors.
                M2E Pro will automatically try to apply another solution to Revise this Item.'
            ),
            Message::TYPE_WARNING
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

        $this->processAdditionalAction($this->getActionType(), $this->getConfigurator());
    }

    protected function canPerformGetItemCall()
    {
        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            return true;
        }

        $getItemCallsCount = 0;
        $getItemLastCallDate = null;

        $maxAllowedGetItemCallsCount = 2;

        $additionalData = $this->listingProduct->getAdditionalData();
        if (!empty($additionalData['get_item_calls_statistic'])) {
            $getItemCallsCount = $additionalData['get_item_calls_statistic']['count'];
            $getItemLastCallDate = $additionalData['get_item_calls_statistic']['last_call_date'];
        }

        if ($getItemCallsCount >= $maxAllowedGetItemCallsCount) {
            $minAllowedDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $minAllowedDate->modify('- 1 day');

            if (strtotime($getItemLastCallDate) > $minAllowedDate->format('U')) {
                return false;
            }

            $getItemCallsCount = 0;
        }

        $getItemCallsCount++;
        $getItemLastCallDate = $this->getHelper('Data')->getCurrentGmtDate();

        $additionalData['get_item_calls_statistic']['count'] = $getItemCallsCount;
        $additionalData['get_item_calls_statistic']['last_call_date'] = $getItemLastCallDate;

        $this->listingProduct->setSettings('additional_data', $additionalData);
        $this->listingProduct->save();

        return true;
    }

    protected function processDuplicateByUUID(Message $message)
    {
        $duplicateItemId = null;
        preg_match('/item\s*ID=(?<itemId>\d+)\.$/i', $message->getText(), $matches);
        if (!empty($matches['itemId'])) {
            $duplicateItemId = $matches['itemId'];
        }

        $this->listingProduct->getChildObject()->setData('is_duplicate', 1);
        $this->listingProduct->setSetting(
            'additional_data',
            'item_duplicate_action_required',
            [
                'item_id' => $duplicateItemId,
                'source' => 'uuid',
                'message' => $message->getText()
            ]
        );
        $this->listingProduct->save();
    }

    protected function processDuplicateByEbayEngine(Message $message)
    {
        $duplicateItemId = null;
        preg_match('/.*\((\d+)\)/', $message->getText(), $matches);
        if (!empty($matches[1])) {
            $duplicateItemId = $matches[1];
        }

        $this->listingProduct->getChildObject()->setData('is_duplicate', 1);
        $this->listingProduct->setSetting(
            'additional_data',
            'item_duplicate_action_required',
            [
                'item_id' => $duplicateItemId,
                'source' => 'ebay_engine',
                'message' => $message->getText()
            ]
        );
        $this->listingProduct->save();
    }

    //########################################

    protected function getConfigurator()
    {
        if (empty($this->configurator)) {
            $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
            $configurator->setUnserializedData($this->params['product']['configurator']);

            $this->configurator = $configurator;
        }

        return $this->configurator;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
     */
    protected function getResponseObject()
    {
        if (empty($this->responseObject)) {
            /** @var $response \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Ebay\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($this->listingProduct);
            $response->setConfigurator($this->getConfigurator());
            $response->setRequestData($this->getRequestDataObject());

            $requestMetaData = !empty($this->params['product']['request_metadata'])
                ? $this->params['product']['request_metadata'] : [];

            $response->setRequestMetaData($requestMetaData);

            $this->responseObject = $response;
        }

        return $this->responseObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        if (empty($this->requestDataObject)) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Ebay_Listing_Product_Action_RequestData');

            $requestData->setData($this->params['product']['request']);
            $requestData->setListingProduct($this->listingProduct);

            $this->requestDataObject = $requestData;
        }

        return $this->requestDataObject;
    }

    //########################################

    protected function markAsPotentialDuplicate()
    {
        $additionalData = $this->listingProduct->getAdditionalData();

        $additionalData['last_failed_action_data'] = [
            'native_request_data' => $this->getRequestDataObject()->getData(),
            'previous_status' => $this->listingProduct->getStatus(),
            'action' => $this->getActionType(),
            'request_time' => $this->getResponse()->getRequestTime(),
        ];

        $this->listingProduct->addData([
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData),
        ])->save();

        $this->listingProduct->getChildObject()->updateVariationsStatus();
    }

    //########################################

    protected function processAdditionalAction(
        $actionType,
        Configurator $configurator,
        array $params = []
    ) {
        $listingProduct = clone $this->listingProduct;
        $listingProduct->setActionConfigurator($configurator);

        $params = array_merge(
            $params,
            [
                'status_changer' => $this->getStatusChanger(),
                'is_realtime' => true,
                'is_additional_action' => true
            ]
        );

        $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Item_Dispatcher');
        $dispatcher->process($actionType, [$listingProduct], $params);

        $logsActionId = $this->params['logs_action_id'];
        if (!is_array($logsActionId)) {
            $logsActionId = [$logsActionId];
        }

        $logsActionId[] = $dispatcher->getLogsActionId();

        $this->params['logs_action_id'] = $logsActionId;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getLogger()
    {
        if ($this->logger === null) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Logger');

            if (!isset($this->params['logs_action_id']) || !isset($this->params['status_changer'])) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has not received some params');
            }

            $logger->setActionId((int)$this->params['logs_action_id']);
            $logger->setAction($this->getLogsAction());

            switch ($this->params['status_changer']) {
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

            $this->logger = $logger;
        }

        return $this->logger;
    }

    //########################################

    public function getStatus()
    {
        return $this->getLogger()->getStatus();
    }

    public function getLogsActionId()
    {
        return $this->params['logs_action_id'];
    }

    //########################################

    /**
     * @return int
     */
    protected function getAccountId()
    {
        return (int)$this->params['account_id'];
    }

    /**
     * @return int
     */
    protected function getMarketplaceId()
    {
        return (int)$this->params['marketplace_id'];
    }

    //---------------------------------------

    protected function getActionType()
    {
        return $this->params['action_type'];
    }

    protected function getLockIdentifier()
    {
        return $this->params['lock_identifier'];
    }

    //---------------------------------------

    protected function getLogsAction()
    {
        return $this->params['logs_action'];
    }

    //---------------------------------------

    protected function getStatusChanger()
    {
        return (int)$this->params['status_changer'];
    }

    //########################################

    protected function getOrmActionType()
    {
        switch ($this->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return 'ListAction';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return 'Relist';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return 'Revise';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return 'Stop';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    //########################################
}
