<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Multiple;

use Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;
use Ess\M2ePro\Model\Ebay\Template\Synchronization as SynchronizationPolicy;

abstract class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product[]
     */
    protected $listingsProducts = array();

    /**
     * @var \Ess\M2ePro\Model\Listing\Product[]
     */
    protected $successfulListingProducts = array();

    /**
     * @var \Ess\M2ePro\Model\Listing\Product[]
     */
    protected $skippedListingsProducts = array();

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator[]
     */
    protected $configurators = array();

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response[]
     */
    protected $responsesObjects = array();

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData[]
     */
    protected $requestsDataObjects = array();

    protected $isResponseFailed = false;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        parent::__construct($ebayFactory, $response, $helperFactory, $modelFactory, $params);

        $listingsProductsIds = array_keys($this->params['products']);

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', array('in' => $listingsProductsIds));

        $this->listingsProducts = $listingProductCollection->getItems();
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $messageText,
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        foreach ($this->listingsProducts as $listingProduct) {
            $this->getLogger()->logListingProductMessage(
                $listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (empty($this->params['is_realtime'])) {
            $this->inspectProducts();
        }
    }

    // todo fire event ListingProduct is changed
    protected function inspectProducts()
    {
        $listingsProductsByStatus = array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED  => array(),
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => array(),
            \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN  => array(),
        );

        foreach ($this->successfulListingProducts as $listingProduct) {
            $listingsProductsByStatus[$listingProduct->getStatus()][$listingProduct->getId()] = $listingProduct;
        }

        foreach ($this->skippedListingsProducts as $listingProduct) {
            $listingsProductsByStatus[$listingProduct->getStatus()][$listingProduct->getId()] = $listingProduct;
        }

        /** @var Runner $runner */
        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Ebay\Connector\Item\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        /** @var Inspector $inspector */
        $inspector = $this->modelFactory->getObject('Ebay\Synchronization\Templates\Synchronization\Inspector');

        $products = $listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED];

        $this->inspectStopRequirements($products, $inspector, $runner);
        $this->inspectReviseRequirements($products, $inspector, $runner);

        $products = array_merge($listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED],
                                $listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN]);

        $this->inspectRelistRequirements($products, $inspector, $runner);

        $runner->execute();
    }

    //----------------------------------------

    protected function inspectStopRequirements(array $products, Inspector $inspector, Runner $runner)
    {
        $lpForAdvancedRules = [];

        foreach ($products as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (!$inspector->isMeetStopGeneralRequirements($listingProduct)) {
                continue;
            }

            if ($inspector->isMeetStopRequirements($listingProduct)) {

                $runner->addProduct(
                    $listingProduct,
                    $this->getStopAction($listingProduct),
                    $this->getStopConfigurator($listingProduct)
                );
                continue;
            }

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();
            $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

            if ($ebayTemplate->isStopAdvancedRulesEnabled()) {

                $templateId = $ebayTemplate->getId();
                $storeId    = $listingProduct->getListing()->getStoreId();
                $magentoProductId  = $listingProduct->getProductId();

                $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;
            }
        }

        $affectedListingProducts = $inspector->getMeetAdvancedRequirementsProducts(
            $lpForAdvancedRules, SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX, 'stop'
        );

        foreach ($affectedListingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $runner->addProduct(
                $listingProduct,
                $this->getStopAction($listingProduct),
                $this->getStopConfigurator($listingProduct)
            );
        }
    }

    protected function getStopAction(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $action = \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;

        if ($ebayListingProduct->isOutOfStockControlEnabled()) {
            $action = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
        }

        return $action;
    }

    protected function getStopConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

        if ($ebayListingProduct->isOutOfStockControlEnabled()) {

            $configurator->setParams(
                array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_STOP)
            );

            $configurator->setPartialMode();
            $configurator->allowQty()->allowVariations();
        }

        return $configurator;
    }

    //----------------------------------------

    protected function inspectReviseRequirements(array $products, Inspector $inspector, Runner $runner)
    {
        foreach ($products as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $isExistInRunner = $runner->isExistProductWithAction(
                $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
            );

            if ($isExistInRunner) {
                continue;
            }

            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
            $configurator->setPartialMode();

            $needRevise = false;

            if ($inspector->isMeetReviseQtyRequirements($listingProduct)) {
                $configurator->allowQty();
                $needRevise = true;
            }

            if ($inspector->isMeetRevisePriceRequirements($listingProduct)) {
                $configurator->allowPrice();
                $needRevise = true;
            }

            if (!$needRevise) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            if ($ebayListingProduct->isVariationsReady()) {
                $configurator->allowVariations();
            }

            $runner->addProduct(
                $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
            );
        }
    }

    //----------------------------------------

    protected function inspectRelistRequirements(array $products, Inspector $inspector, Runner $runner)
    {
        $lpForAdvancedRules = [];

        foreach ($products as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (!$inspector->isMeetRelistRequirements($listingProduct)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();
            $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

            if ($ebayTemplate->isRelistAdvancedRulesEnabled()) {

                $templateId = $ebayTemplate->getId();
                $storeId    = $listingProduct->getListing()->getStoreId();
                $magentoProductId = $listingProduct->getProductId();

                $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

            } else {

                $runner->addProduct(
                    $listingProduct,
                    $this->getRelistAction($listingProduct),
                    $this->getRelistConfigurator($listingProduct)
                );
            }
        }

        $affectedListingProducts = $inspector->getMeetAdvancedRequirementsProducts(
            $lpForAdvancedRules, SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX, 'relist'
        );

        foreach ($affectedListingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $runner->addProduct(
                $listingProduct,
                $this->getRelistAction($listingProduct),
                $this->getRelistConfigurator($listingProduct)
            );
        }
    }

    protected function getRelistAction(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $action = \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;

        if ($listingProduct->isHidden()) {
            $action = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
        }

        return $action;
    }

    protected function getRelistConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

        if ($listingProduct->isHidden()) {
            $configurator->setParams(array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST));
        }

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isRelistSendData()) {
            $configurator->setPartialMode();
            $configurator->allowQty()->allowPrice()->allowVariations();
        }

        return $configurator;
    }

    //########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->listingsProducts as $listingProduct) {
            $this->processMessages($listingProduct, $this->getResponse()->getMessages()->getEntities());
        }
    }

    protected function processResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getPreparedResponseData();

        foreach ($this->listingsProducts as $listingProduct) {

            $messagesData = array();
            if (!empty($responseData['result'][$listingProduct->getId()]['messages'])) {
                $messagesData = $responseData['result'][$listingProduct->getId()]['messages'];
            }

            $messages = array();

            foreach ($messagesData as $messageData) {
                $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                $message->initFromResponseData($messageData);

                $messages[] = $message;
            }

            if (!$this->processMessages($listingProduct, $messages)) {
                if (!empty($responseData['result'][$listingProduct->getId()]['is_skipped'])) {
                    $this->skippedListingsProducts[$listingProduct->getId()] = $listingProduct;
                }

                continue;
            }

            $successData = $this->getSuccessfulData($listingProduct);
            $this->processCompleted($listingProduct, $successData);
        }
    }

    protected function processMessages(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $messages)
    {
        $hasError = false;

        foreach ($messages as $message) {

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */

            !$hasError && $hasError = $message->isError();

            $this->getLogger()->logListingProductMessage(
                $listingProduct, $message
            );
        }

        return !$hasError;
    }

    protected function processCompleted(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                        array $data = array(), array $params = array())
    {
        $this->getResponseObject($listingProduct)->processSuccess($data, $params);

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $this->getSuccessfulMessage($listingProduct),
           \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage(
            $listingProduct, $message
        );

        $this->successfulListingProducts[$listingProduct->getId()] = $listingProduct;
    }

    //----------------------------------------

    protected function getSuccessfulData(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $responseData = $this->getPreparedResponseData();
        if (empty($responseData['result'][$listingProduct->getId()])) {
            return array();
        }

        $listingProductResponseData = $responseData['result'][$listingProduct->getId()];
        unset($listingProductResponseData['messages']);

        return $listingProductResponseData;
    }

    //----------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return string
     */
    abstract protected function getSuccessfulMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct);

    //########################################

    protected function getConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (empty($this->configurators[$listingProduct->getId()])) {

            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
            $configurator->setUnserializedData($this->params['products'][$listingProduct->getId()]['configurator']);

            $this->configurators[$listingProduct->getId()] = $configurator;
        }

        return $this->configurators[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
     */
    protected function getResponseObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->responsesObjects[$listingProduct->getId()])) {

            /* @var $response \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Ebay\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($listingProduct);
            $response->setConfigurator($this->getConfigurator($listingProduct));
            $response->setRequestData($this->getRequestDataObject($listingProduct));

            $requestMetaData = !empty($this->params['products'][$listingProduct->getId()]['request_metadata'])
                ? $this->params['products'][$listingProduct->getId()]['request_metadata'] : array();

            $response->setRequestMetaData($requestMetaData);

            $this->responsesObjects[$listingProduct->getId()] = $response;
        }

        return $this->responsesObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->requestsDataObjects[$listingProduct->getId()])) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Ebay\Listing\Product\Action\RequestData');

            $requestData->setData($this->params['products'][$listingProduct->getId()]['request']);
            $requestData->setListingProduct($listingProduct);

            $this->requestsDataObjects[$listingProduct->getId()] = $requestData;
        }

        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    //########################################
}