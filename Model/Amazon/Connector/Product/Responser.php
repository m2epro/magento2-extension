<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
 */
abstract class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    protected $listingProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected $logger = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    protected $configurator = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
     */
    protected $responseObject = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected $requestDataObject = null;

    protected $isSuccess = false;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        parent::__construct($amazonFactory, $activeRecordFactory, $response, $helperFactory, $modelFactory, $params);

        $this->listingProduct = $this->amazonFactory->getObjectLoaded(
            'Listing\Product',
            $this->params['product']['id']
        );
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

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        $this->processParentProcessor();
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

    //########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        return isset($responseData['messages']) && is_array($responseData['messages']);
    }

    protected function processResponseData()
    {
        $messages = [];

        $responseData = $this->getPreparedResponseData();

        $requestLogMessages = isset($this->params['product']['request_metadata']['log_messages'])
            ? $this->params['product']['request_metadata']['log_messages'] : [];

        foreach ($requestLogMessages as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $messages[] = $message;
        }

        foreach ($responseData['messages'] as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromResponseData($messageData);

            $messages[] = $message;
        }

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

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message
            );
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

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct,
            $message
        );

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

    //########################################

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

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getResponseObject()
    {
        if ($this->responseObject === null) {
            /** @var $response \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Response'
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

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account', 'account_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getMarketplace()
    {
        return $this->getAccount()->getChildObject()->getMarketplace();
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

    protected function getLogsActionId()
    {
        return (int)$this->params['logs_action_id'];
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
            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return 'Delete';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    //########################################
}
