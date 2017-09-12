<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description as RequestDescription;

/**
 * @method \Ess\M2ePro\Model\Ebay\Connector\Item\Responser getResponser()
 */
abstract class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    const DEFAULT_REQUEST_TIMEOUT         = 300;
    const TIMEOUT_INCREMENT_FOR_ONE_IMAGE = 30;

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator */
    protected $validatorObject = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request */
    protected $requestObject = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData */
    protected $requestDataObject = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger */
    protected $logger = NULL;

    protected $isRealTime = false;

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!is_null($listingProduct->getActionConfigurator())) {
            $actionConfigurator = $listingProduct->getActionConfigurator();
        } else {
            $actionConfigurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
        }

        $this->listingProduct = $listingProduct->load($listingProduct->getId());

        if ($this->listingProduct->needSynchRulesCheck()) {
            $this->listingProduct->setData('need_synch_rules_check', 0);
            $this->listingProduct->save();
        }

        $this->listingProduct->setActionConfigurator($actionConfigurator);

        $this->marketplace = $this->listingProduct->getMarketplace();
        $this->account     = $this->listingProduct->getAccount();
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
        return 'Ebay\Connector\Item\ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        $configuratorParams = $this->listingProduct->getActionConfigurator()->getParams();

        $startDate = $this->getHelper('Data')->getCurrentGmtDate();
        if (!empty($configuratorParams['start_processing_date'])) {
            $startDate = $configuratorParams['start_processing_date'];
        }

        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data'        => $this->getRequestData(),
                'listing_product_id'  => $this->listingProduct->getId(),
                'lock_identifier'     => $this->getLockIdentifier(),
                'action_type'         => $this->getActionType(),
                'priority'            => $this->listingProduct->getActionConfigurator()->getPriority(),
                'request_timeout'     => $this->getRequestTimeout(),
                'start_date'          => $startDate,
            )
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
            (is_null($requestData['is_eps_ebay_images_mode']) &&
                $requestData['upload_images_mode'] == RequestDescription::UPLOAD_IMAGES_MODE_SELF)) {
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
                return;
            }

            $this->lockListingProduct();
            $this->initializeVariations();

            if (!$this->validateListingProduct()) {
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
        try {

            parent::process();

        } catch (\Ess\M2ePro\Model\Exception\Connection $exception) {

            if ($this->account->getChildObject()->isModeSandbox()) {
                throw $exception;
            }

            $this->processResponser();

        } catch (\Exception $exception) {

            if (strpos($exception->getMessage(), 'code:34') === false ||
                $this->account->getChildObject()->isModeSandbox()
            ) {
                throw $exception;
            }

            $this->processResponser();
        }

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

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                $messageText, \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );
        }

        return $this->buildRequestDataObject($tempData)->getData();
    }

    //########################################

    protected function getResponserParams()
    {
        $product = array(
            'request'          => $this->getRequestDataObject()->getData(),
            'request_metadata' => $this->getRequestObject()->getMetaData(),
            'configurator'     => $this->listingProduct->getActionConfigurator()->getSerializedData(),
            'id'               => $this->listingProduct->getId(),
        );

        return array(
            'is_realtime'     => $this->isRealTime(),
            'account_id'      => $this->account->getId(),
            'action_type'     => $this->getActionType(),
            'lock_identifier' => $this->getLockIdentifier(),
            'logs_action'     => $this->getLogsAction(),
            'logs_action_id'  => $this->getLogger()->getActionId(),
            'status_changer'  => $this->params['status_changer'],
            'params'          => $this->params,
            'product'         => $product,
        );
    }

    //########################################

    protected function validateListingProduct()
    {
        $validator = $this->getValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );
        }

        if ($validationResult) {
            return true;
        }

        $this->unlockListingProduct();

        return false;
    }

    protected function initializeVariations()
    {
        $variationUpdater = $this->modelFactory->getObject('Ebay\Listing\Product\Variation\Updater');
        $variationUpdater->process($this->listingProduct);
        $variationUpdater->afterMassProcessEvent();
    }

    //########################################

    protected function isListingProductLocked()
    {
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        if ($this->listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {

            // M2ePro\TRANSLATIONS
            // Another Action is being processed. Try again when the Action is completed.
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Another Action is being processed. Try again when the Action is completed.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            return true;
        }

        return false;
    }

    // ########################################

    protected function lockListingProduct()
    {
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        $lockItem->create();
        $lockItem->makeShutdownFunction();
    }

    protected function unlockListingProduct()
    {
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        $lockItem->remove();
    }

    // ########################################

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
                'Ebay\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Validator'
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
     */
    protected function makeRequestObject()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request $request */

        $request = $this->modelFactory->getObject(
            'Ebay\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Request'
        );

        $request->setParams($this->params);
        $request->setListingProduct($this->listingProduct);
        $request->setConfigurator($this->listingProduct->getActionConfigurator());
        $request->setValidatorsData($this->getValidatorObject()->getData());

        return $request;
    }

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
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
     */
    protected function makeRequestDataObject(array $data)
    {
        $requestData = $this->modelFactory->getObject('Ebay\Listing\Product\Action\RequestData');

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
        if (is_null($this->logger)) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Logger');

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
}