<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

use Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;
use Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator;

abstract class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    protected $listingProduct = NULL;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected $logger = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    protected $configurator = NULL;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
     */
    protected $responseObject = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected $requestDataObject = NULL;

    // ---------------------------------------

    protected $isSuccess = false;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);

        $this->listingProduct = $this->amazonFactory->getObjectLoaded(
            'Listing\Product', $this->params['product']['id']
        );
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
            return;
        }

        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Amazon\Connector\Product\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        /** @var \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\Inspector $inspector */
        $inspector = $this->modelFactory->getObject('Amazon\Synchronization\Templates\Synchronization\Inspector');

        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['request_time']) && $this->listingProduct->needSynchRulesCheck()) {
            $configurator = $this->getConfigurator();
        } else {
            $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
        }

        if (empty($responseData['request_time']) && !empty($responseData['start_processing_date'])) {
            $configurator->setParams(array('start_processing_date' => $responseData['start_processing_date']));
        }

        $result = $this->inspectStopRequirements($inspector, $runner, $configurator);
        !$result && $result = $this->inspectReviseRequirements($inspector, $runner, $configurator);
        !$result && $result = $this->inspectRelistRequirements($inspector, $runner, $configurator);
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
                $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP, $configurator
            );

            $runner->execute();
            return true;
        }

        return false;
    }

    protected function inspectReviseRequirements(Inspector $inspector, Runner $runner, Configurator $configurator)
    {
        if (!$this->listingProduct->isListed() && !$this->listingProduct->isUnknown()) {
            return false;
        }

        $configurator->reset();
        $needRevise = false;

        if (!$this->listingProduct->isUnknown() && $inspector->isMeetReviseQtyRequirements($this->listingProduct)) {
            $configurator->allowQty();
            $needRevise = true;
        }

        if ($inspector->isMeetReviseRegularPriceRequirements($this->listingProduct)) {
            $configurator->allowRegularPrice();
            $needRevise = true;
        }

        if ($inspector->isMeetReviseBusinessPriceRequirements($this->listingProduct)) {
            $configurator->allowBusinessPrice();
            $needRevise = true;
        }

        if ($needRevise) {
            $runner->addProduct(
                $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if (!$amazonListingProduct->getAmazonSynchronizationTemplate()->isRelistSendData()) {
            $configurator->reset();
            $configurator->allowQty();
        }

        if ($amazonListingProduct->getAmazonSynchronizationTemplate()->isRelistAdvancedRulesEnabled()) {

            if ($inspector->isMeetAdvancedRelistRequirements($this->listingProduct)) {

                $runner->addProduct(
                    $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

                $runner->execute();
                return true;
            }

        } else {

            $runner->addProduct(
                $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
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

    // ########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        return isset($responseData['messages']) && is_array($responseData['messages']);
    }

    protected function processResponseData()
    {
        $responseMessages = array();

        $responseData = $this->getPreparedResponseData();

        foreach ($responseData['messages'] as $messageData) {
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromResponseData($messageData);

            $responseMessages[] = $message;
        }

        if (!$this->processMessages($responseMessages)) {
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
                $this->listingProduct, $message
            );
        }

        return !$hasError;
    }

    protected function processSuccess(array $params = array())
    {
        $this->getResponseObject()->processSuccess($params);

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $this->getSuccessfulMessage(),
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct, $message
        );

        $this->isSuccess = true;
    }

    //----------------------------------------

    protected function getSuccessfulParams()
    {
        return array();
    }

    //----------------------------------------

    /**
     * @return string
     */
    abstract protected function getSuccessfulMessage();

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected function getLogger()
    {
        if (is_null($this->logger)) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Logger');

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
        if (is_null($this->configurator)) {

            $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
            $configurator->setUnserializedData($this->params['product']['configurator']);

            $this->configurator = $configurator;
        }

        return $this->configurator;
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
     */
    protected function getResponseObject()
    {
        if (is_null($this->responseObject)) {

            /* @var $response \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Response'
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
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        if (is_null($this->requestDataObject)) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Amazon\Listing\Product\Action\RequestData');

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
        return $this->getObjectByParam('Account','account_id');
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
                return 'Delete';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    // ########################################
}