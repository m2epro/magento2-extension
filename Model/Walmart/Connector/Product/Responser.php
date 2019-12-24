<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product;

use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;
use Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
 */
abstract class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Responser
{
    protected $activeRecordFactory;
    /**
     * @var \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    protected $listingProduct = null;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger
     */
    protected $logger = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator
     */
    protected $configurator = null;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response $responseObject
     */
    protected $responseObject = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData $requestDataObject
     */
    protected $requestDataObject = null;

    // ---------------------------------------

    protected $isSuccess = false;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($walmartFactory, $response, $helperFactory, $modelFactory, $params);

        $listingProductId = $this->params['product']['id'];
        $this->listingProduct = $this->walmartFactory
            ->getObjectLoaded('Listing\Product', $listingProductId);
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

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
        $this->inspectProduct();
    }

    protected function inspectProduct()
    {
        if (!$this->isSuccess && !$this->listingProduct->needSynchRulesCheck()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();
        if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner $runner */
        $runner = $this->modelFactory->getObject('Synchronization_Templates_Synchronization_Runner');
        $runner->setConnectorModel('Walmart_Connector_Product_Dispatcher');
        $runner->setMaxProductsPerStep(100);

        /** @var \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Inspector $inspector */
        $inspector = $this->modelFactory->getObject('Walmart_Synchronization_Templates_Synchronization_Inspector');

        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['request_time']) && $this->listingProduct->needSynchRulesCheck()) {
            $configurator = $this->getConfigurator();
        } else {
            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
        }

        if (empty($responseData['request_time']) && !empty($responseData['start_processing_date'])) {
            $configurator->setParams(['start_processing_date' => $responseData['start_processing_date']]);
        }

        if ($this->inspectStopRequirements($inspector, $runner, $configurator)) {
            return;
        }

        if ($this->inspectReviseRequirements($inspector, $runner, $configurator)) {
            return;
        }

        $this->inspectRelistRequirements($inspector, $runner, $configurator);
    }

    protected function inspectStopRequirements(Inspector $inspector, Runner $runner, Configurator $configurator)
    {
        if (!$this->listingProduct->isListed()) {
            return false;
        }

        if (!$inspector->isMeetStopGeneralRequirements($this->listingProduct)) {
            return false;
        }

        if ($inspector->isMeetStopRequirements($this->listingProduct) ||
            $inspector->isMeetAdvancedStopRequirements($this->listingProduct)) {
            $runner->addProduct(
                $this->listingProduct,
                \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                $configurator
            );

            $runner->execute();
            return true;
        }

        return false;
    }

    protected function inspectReviseRequirements(Inspector $inspector, Runner $runner, Configurator $configurator)
    {
        if (!$this->listingProduct->isListed()) {
            return false;
        }

        $configurator->reset();
        $needRevise = false;

        if ($inspector->isMeetReviseQtyRequirements($this->listingProduct)) {
            $configurator->allowQty();
            $needRevise = true;
        }

        if ($inspector->isMeetRevisePriceRequirements($this->listingProduct)) {
            $configurator->allowPrice();
            $needRevise = true;
        }

        if ($inspector->isMeetRevisePromotionsPriceRequirements($this->listingProduct)) {
            $configurator->allowPromotions();
            $needRevise = true;
        }

        if ($needRevise) {
            $runner->addProduct(
                $this->listingProduct,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                $configurator
            );

            $runner->execute();
            return true;
        }

        return false;
    }

    protected function inspectRelistRequirements(Inspector $inspector, Runner $runner, Configurator $configurator)
    {
        if (!$this->listingProduct->isStopped()) {
            return false;
        }

        if (!$inspector->isMeetRelistRequirements($this->listingProduct)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();

        $configurator->reset();
        $configurator->allowQty();

        if ($walmartListingProduct->getWalmartSynchronizationTemplate()->isReviseUpdatePrice() ||
            ($this->listingProduct->isBlocked() && $walmartListingProduct->isOnlinePriceInvalid())
        ) {
            $configurator->allowPrice();
        }

        if ($walmartListingProduct->getWalmartSynchronizationTemplate()->isReviseUpdatePromotions()) {
            $configurator->allowPromotions();
        }

        if ($walmartListingProduct->getWalmartSynchronizationTemplate()->isRelistAdvancedRulesEnabled()) {
            if ($inspector->isMeetAdvancedRelistRequirements($this->listingProduct)) {
                $runner->addProduct(
                    $this->listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                    $configurator
                );

                $runner->execute();
                return true;
            }
        } else {
            $runner->addProduct(
                $this->listingProduct,
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                $configurator
            );

            $runner->execute();
            return true;
        }

        return false;
    }

    protected function processParentProcessor()
    {
        if (!$this->isSuccess) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();

        $variationManager = $walmartListingProduct->getVariationManager();

        if (!$variationManager->isRelationMode()) {
            return;
        }

        if ($variationManager->isRelationParentType()) {
            $parentListingProduct = $this->listingProduct;
        } else {
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartParentListingProduct */
        $walmartParentListingProduct = $parentListingProduct->getChildObject();

        $parentTypeModel = $walmartParentListingProduct->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();
    }

    // ########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        return isset($responseData['sku']) || isset($responseData['errors']);
    }

    protected function processResponseData()
    {
        $messages = [];

        $responseData = $this->getPreparedResponseData();

        if (isset($responseData['errors'])) {
            foreach ($responseData['errors'] as $messageData) {
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromResponseData($messageData);

                $messages[] = $message;
            }
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
        return $this->getPreparedResponseData();
    }

    //----------------------------------------

    /**
     * @return string
     */
    abstract protected function getSuccessfulMessage();

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger
     */
    protected function getLogger()
    {
        if ($this->logger === null) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Logger');

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
            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            $configurator->setData($this->params['product']['configurator']);

            $this->configurator = $configurator;
        }

        return $this->configurator;
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
     */
    protected function getResponseObject()
    {
        if ($this->responseObject === null) {
            /** @var $response \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Walmart\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($this->listingProduct);
            $response->setConfigurator($this->getConfigurator());
            $response->setRequestData($this->getRequestDataObject());

            $this->responseObject = $response;
        }

        return $this->responseObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        if ($this->requestDataObject === null) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Walmart_Listing_Product_Action_RequestData');

            $requestData->setData($this->params['product']['request']);
            $requestData->setListingProduct($this->listingProduct);

            $this->requestDataObject = $requestData;
        }

        return $this->requestDataObject;
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account', 'account_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
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

    // ########################################

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
                return 'Retire';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    // ########################################

    protected function checkUpdatePriceOrPromotionsFeedsLock(
        Product $listingProduct,
        Configurator $configurator,
        array &$tags,
        $action
    ) {
        if (count($configurator->getAllowedDataTypes()) !== 1) {
            return;
        }

        if (!$configurator->isPriceAllowed() && !$configurator->isPromotionsAllowed()) {
            return;
        }

        if (!$this->isLockedForUpdatePriceOrPromotions($listingProduct)) {
            return;
        }

        if ($configurator->isPriceAllowed()) {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item Price cannot yet be submitted. Walmart allows updating the Price information no sooner than
                24 hours after the relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $configurator->disallowPrice();
            unset($tags['price']);
        } else {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item Promotion Price cannot yet be submitted. Walmart allows updating the Promotion Price
                information no sooner than 24 hours after the relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $configurator->disallowPromotions();
            unset($tags['promotions']);
        }

        $logger = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Logger');
        $logger->setAction($action);
        $logger->setActionId($this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId());
        $logger->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        $logger->logListingProductMessage($listingProduct, $message);
    }

    protected function isLockedForUpdatePriceOrPromotions(Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        if ($walmartListingProduct->getListDate() === null) {
            return false;
        }

        try {
            $borderDate = new \DateTime($walmartListingProduct->getListDate(), new \DateTimeZone('UTC'));
            $borderDate->modify('+24 hours');
        } catch (\Exception $exception) {
            return false;
        }

        if ($borderDate < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        return true;
    }

    // ########################################
}
