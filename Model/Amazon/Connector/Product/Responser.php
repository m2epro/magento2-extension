<?php

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

abstract class Responser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Buffer */
    private $tagBuffer;
    /** @var \Ess\M2ePro\Model\Amazon\TagFactory */
    private $amazonTagFactory;
    /** @var \Ess\M2ePro\Model\TagFactory */
    private $baseTagFactory;
    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger */
    protected $logger = null;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
    protected $configurator = null;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response */
    protected $responseObject = null;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData*/
    protected $requestDataObject = null;
    /** @var bool  */
    protected $isSuccess = false;

    public function __construct(
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\Amazon\TagFactory $tagFactory,
        \Ess\M2ePro\Model\TagFactory $baseTagFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
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

        $this->tagBuffer = $tagBuffer;
        $this->amazonTagFactory = $tagFactory;
        $this->baseTagFactory = $baseTagFactory;
        $this->listingProduct = $this->amazonFactory->getObjectLoaded(
            'Listing\Product',
            $this->params['product']['id']
        );
    }

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $messageText,
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct,
            $message
        );
    }

    public function eventAfterExecuting()
    {
        if ($this->isTemporaryErrorAppeared($this->getResponse()->getMessages()->getEntities())) {
            $this->getResponseObject()->throwRepeatActionInstructions();
        }

        parent::eventAfterExecuting();

        $this->handleTags();

        $this->processParentProcessor();
    }

    private function handleTags(): void
    {
        $allowedCodesOfWarnings = [];
        $tags = [];

        foreach ($this->getMessagesFromResponseData() as $message) {
            if (!$message->isSenderComponent() || $message->getCode() === null) {
                continue;
            }

            if (
                $message->isError()
                || ($message->isWarning() && in_array($message->getCode(), $allowedCodesOfWarnings))
            ) {
                $tags[] = $this->amazonTagFactory->createByErrorCode($message->getCode(), $message->getText());
            }
        }

        if (!empty($tags)) {
            $tags[] = $this->baseTagFactory->createWithHasErrorCode();
            $this->tagBuffer->addTags($this->listingProduct, $tags);
            $this->tagBuffer->flush();
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    private function getMessagesFromResponseData(): array
    {
        $responseData = $this->getPreparedResponseData();
        if (empty($responseData['messages'])) {
            return [];
        }

        $messages = [];

        foreach ($responseData['messages'] as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromResponseData($messageData);

            $messages[] = $message;
        }

        return $messages;
    }

    protected function processParentProcessor()
    {
        if (!$this->isSuccess) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        $variationManager = $amazonListingProduct->getVariationManager();

        if (!$variationManager->isRelationMode()) {
            return;
        }

        if ($variationManager->isRelationParentType()) {
            $parentListingProduct = $this->listingProduct;
        } else {
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonParentListingProduct */
        $amazonParentListingProduct = $parentListingProduct->getChildObject();

        $parentTypeModel = $amazonParentListingProduct->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();
    }

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['messages']) && is_array($responseData['messages']);
    }

    protected function processResponseData()
    {
        $messages = [];

        $requestLogMessages = isset($this->params['product']['request_metadata']['log_messages'])
            ? $this->params['product']['request_metadata']['log_messages'] : [];

        foreach ($requestLogMessages as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $messages[] = $message;
        }

        $messages = array_merge($messages, $this->getMessagesFromResponseData());

        if (!$this->processMessages($messages)) {
            return;
        }

        $successParams = $this->getSuccessfulParams();
        $this->processSuccess($successParams);
    }

    //----------------------------------------

    protected function processMessages(array $messages)
    {
        $hasError = false;

        foreach ($messages as $message) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */

            !$hasError && $hasError = $message->isError();
            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }

        return !$hasError;
    }

    protected function processSuccess(array $params = [])
    {
        $this->getResponseObject()->processSuccess($params);

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

    protected function getSuccessfulParams()
    {
        return [];
    }

    //----------------------------------------

    /**
     * @return string
     */
    abstract protected function getSuccessfulMessage();

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getLogger()
    {
        if ($this->logger === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Logger');

            $logger->setActionId($this->getLogsActionId());
            $logger->setAction($this->getLogsAction());

            switch ($this->getStatusChanger()) {
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

    protected function getConfigurator()
    {
        if ($this->configurator === null) {
            $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
            $configurator->setUnserializedData($this->params['product']['configurator']);

            $this->configurator = $configurator;
        }

        return $this->configurator;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getResponseObject()
    {
        if ($this->responseObject === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response $response */
            $response = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Response'
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
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRequestDataObject()
    {
        if ($this->requestDataObject === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Amazon_Listing_Product_Action_RequestData');

            $requestData->setData($this->params['product']['request']);
            $requestData->setListingProduct($this->listingProduct);

            $this->requestDataObject = $requestData;
        }

        return $this->requestDataObject;
    }

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

    protected function getLogsActionId()
    {
        return (int)$this->params['logs_action_id'];
    }

    //---------------------------------------

    protected function getStatusChanger()
    {
        return (int)$this->params['status_changer'];
    }

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
            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return 'Delete';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     *
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message|bool
     * TODO ERROR CODEs
     */
    protected function isTemporaryErrorAppeared(array $messages)
    {
        $errorCodes = [
            /* TODO ERROR CODEs */
        ];

        foreach ($messages as $message) {
            if (in_array($message->getCode(), $errorCodes, true)) {
                return $message;
            }
        }

        return false;
    }
}
