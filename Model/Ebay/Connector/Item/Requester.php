<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

/**
 * @method \Ess\M2ePro\Model\Ebay\Connector\Item\Responser getResponser()
 */
abstract class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    const DEFAULT_REQUEST_TIMEOUT = 300;
    const TIMEOUT_INCREMENT_FOR_ONE_IMAGE = 30;

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Listing\Product\LockManager */
    protected $lockManager = null;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator */
    protected $validatorObject = null;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request */
    protected $requestObject = null;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData */
    protected $requestDataObject = null;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger */
    protected $logger = null;

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message[] */
    protected $storedLogMessages = [];

    protected $isRealTime = false;

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        if ($listingProduct->getActionConfigurator() === null) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
            $this->listingProduct->setActionConfigurator($configurator);
        }

        $this->marketplace = $this->listingProduct->getMarketplace();
        $this->account = $this->listingProduct->getAccount();
    }

    //########################################

    public function setIsRealTime($isRealTime = true)
    {
        $this->isRealTime = $isRealTime;
        return $this;
    }

    public function isRealTime()
    {
        return $this->isRealTime;
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay_Connector_Item_ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            [
                'request_data' => $this->getRequestData(),
                'listing_product_id' => $this->listingProduct->getId(),
                'lock_identifier' => $this->getLockIdentifier(),
                'action_type' => $this->getActionType(),
                'request_timeout' => $this->getRequestTimeout()
            ]
        );
    }

    //########################################

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout($this->getRequestTimeOut());

        return $connection;
    }

    public function getRequestTimeout()
    {
        $requestDataObject = $this->getRequestDataObject();
        $requestData = $requestDataObject->getData();

        if (!isset($requestData['is_eps_ebay_images_mode']) || !isset($requestData['upload_images_mode']) ||
            $requestData['is_eps_ebay_images_mode'] === false ||
            ($requestData['is_eps_ebay_images_mode'] === null &&
                $requestData['upload_images_mode'] ==
                \Ess\M2ePro\Helper\Component\Ebay\Configuration::UPLOAD_IMAGES_MODE_SELF)) {
            return self::DEFAULT_REQUEST_TIMEOUT;
        }

        $imagesTimeout = self::TIMEOUT_INCREMENT_FOR_ONE_IMAGE * $requestDataObject->getTotalImagesCount();
        return self::DEFAULT_REQUEST_TIMEOUT + $imagesTimeout;
    }

    //########################################

    public function process()
    {
        try {
            $this->getLogger()->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);

            if ($this->isListingProductLocked()) {
                $this->writeStoredLogMessages();
                return;
            }

            $this->lockListingProduct();
            $this->initializeVariations();

            if (!$this->validateListingProduct() || !$this->validateConfigurator()) {
                $this->writeStoredLogMessages();
                return;
            }

            if ($this->isRealTime()) {
                $this->processRealTime();
                return;
            }

            $this->eventBeforeExecuting();
            $this->getProcessingRunner()->start();
        } catch (\Exception $exception) {
            $this->unlockListingProduct();
            throw $exception;
        }

        $this->unlockListingProduct();
    }

    protected function processRealTime()
    {
        parent::process();

        if ($this->getResponser()->getStatus() != \Ess\M2ePro\Helper\Data::STATUS_SUCCESS) {
            $this->getLogger()->setStatus($this->getResponser()->getStatus());
        }
        $this->params['logs_action_id'] = $this->getResponser()->getLogsActionId();
    }

    protected function processResponser()
    {
        $this->unlockListingProduct();
        parent::processResponser();
    }

    //########################################

    protected function getRequestData()
    {
        $tempData = $this->getRequestObject()->getRequestData();

        foreach ($this->getRequestObject()->getWarningMessages() as $messageText) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $messageText,
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $this->storeLogMessage($message);
        }

        return $this->buildRequestDataObject($tempData)->getData();
    }

    //########################################

    protected function getResponserParams()
    {
        $logMessages = [];
        foreach ($this->getStoredLogMessages() as $message) {
            $logMessages[] = $message->asArray();
        }

        $metaData = $this->getRequestObject()->getMetaData();
        $metaData['log_messages'] = $logMessages;

        $product = [
            'request'          => $this->getRequestDataObject()->getData(),
            'request_metadata' => $metaData,
            'configurator'     => $this->listingProduct->getActionConfigurator()->getSerializedData(),
            'id'               => $this->listingProduct->getId(),
        ];

        return [
            'is_realtime'     => $this->isRealTime(),
            'account_id'      => $this->account->getId(),
            'action_type'     => $this->getActionType(),
            'lock_identifier' => $this->getLockIdentifier(),
            'logs_action'     => $this->getLogsAction(),
            'logs_action_id'  => $this->getLogger()->getActionId(),
            'status_changer'  => $this->params['status_changer'],
            'params'          => $this->params,
            'product'         => $product,
        ];
    }

    //########################################

    protected function validateListingProduct()
    {
        $validator = $this->getValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        if ($validationResult) {
            return true;
        }

        $this->unlockListingProduct();

        return false;
    }

    /**
     * Some data parts can be disallowed from configurator on validateListingProduct() action
     * @return bool
     */
    protected function validateConfigurator()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->listingProduct->getActionConfigurator();
        if (empty($configurator->getAllowedDataTypes())) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'There was no need for this action. It was skipped.
                Please check the log message above for more detailed information.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->storeLogMessage($message);
            $this->unlockListingProduct();

            return false;
        }

        return true;
    }

    //########################################

    protected function initializeVariations()
    {
        $variationUpdater = $this->modelFactory->getObject('Ebay_Listing_Product_Variation_Updater');
        $variationUpdater->process($this->listingProduct);
        $variationUpdater->afterMassProcessEvent();
    }

    //########################################

    protected function isListingProductLocked()
    {
        if ($this->listingProduct->isSetProcessingLock('in_action') || $this->getLockManager()->isLocked()) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Another Action is being processed. Try again when the Action is completed.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->storeLogMessage($message);

            return true;
        }

        return false;
    }

    //########################################

    protected function lockListingProduct()
    {
        $this->getLockManager()->lock();
    }

    protected function unlockListingProduct()
    {
        $this->getLockManager()->unlock();
    }

    //########################################

    public function getStatus()
    {
        return $this->getLogger()->getStatus();
    }

    /**
     * @return array|integer
     */
    public function getLogsActionId()
    {
        return $this->params['logs_action_id'];
    }

    //########################################

    abstract protected function getActionType();

    abstract protected function getLogsAction();

    protected function getLockManager()
    {
        if ($this->lockManager !== null) {
            return $this->lockManager;
        }

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

        $this->lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
        $this->lockManager->setListingProduct($this->listingProduct);
        $this->lockManager->setInitiator($initiator);
        $this->lockManager->setLogsActionId($this->params['logs_action_id']);
        $this->lockManager->setLogsAction($this->getLogsAction());

        return $this->lockManager;
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
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    protected function getLockIdentifier()
    {
        if ($this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
            return 'list';
        }

        return strtolower($this->getOrmActionType());
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
     */
    protected function getValidatorObject()
    {
        if (empty($this->validatorObject)) {

            /** @var $validator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator */
            $validator = $this->modelFactory->getObject(
                'Ebay\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Validator'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->validatorObject = $validator;
        }

        return $this->validatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRequestObject()
    {
        if (empty($this->requestObject)) {
            $this->requestObject = $this->makeRequestObject();
        }

        return $this->requestObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function makeRequestObject()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request $request */

        $request = $this->modelFactory->getObject(
            'Ebay\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Request'
        );

        $request->setParams($this->params);
        $request->setListingProduct($this->listingProduct);
        $request->setConfigurator($this->listingProduct->getActionConfigurator());
        $request->setCachedData($this->getValidatorObject()->getData());

        return $request;
    }

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function buildRequestDataObject(array $data)
    {
        if (empty($this->requestDataObject)) {
            $this->requestDataObject = $this->makeRequestDataObject($data);
        }

        return $this->requestDataObject;
    }

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function makeRequestDataObject(array $data)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $requestData */
        $requestData = $this->modelFactory->getObject('Ebay_Listing_Product_Action_RequestData');

        $requestData->setData($data);
        $requestData->setListingProduct($this->listingProduct);

        return $requestData;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        return $this->requestDataObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getLogger()
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

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    protected function getStoredLogMessages()
    {
        return $this->storedLogMessages;
    }

    protected function storeLogMessage(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
    {
        $this->storedLogMessages[] = $message;
    }

    protected function writeStoredLogMessages()
    {
        foreach ($this->getStoredLogMessages() as $message) {
            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message
            );
        }
    }

    //########################################
}
