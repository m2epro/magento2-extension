<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

use Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;
use Ess\M2ePro\Model\Amazon\Template\Synchronization as SynchronizationPolicy;

abstract class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
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

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected $logger = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator[]
     */
    protected $configurators = array();

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response[]
     */
    protected $responsesObjects = array();

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData[]
     */
    protected $requestsDataObjects = array();

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

        $listingsProductsIds = array_keys($this->params['products']);

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', array('in' => $listingsProductsIds));

        $this->listingsProducts = $listingProductCollection->getItems();
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

        $this->processParentProcessors();
        $this->inspectProducts();
    }

    // ########################################

    // todo fire event ListingProduct is changed
    protected function inspectProducts()
    {
        $listingsProductsByStatus = array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED  => array(),
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => array(),
            \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => array(),
        );

        foreach ($this->successfulListingProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $listingsProductsByStatus[$listingProduct->getStatus()][$listingProduct->getId()] = $listingProduct;
        }

        foreach ($this->skippedListingsProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $listingsProductsByStatus[$listingProduct->getStatus()][$listingProduct->getId()] = $listingProduct;
        }

        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Amazon\Connector\Product\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        $inspector = $this->modelFactory->getObject('Amazon\Synchronization\Templates\Synchronization\Inspector');

        $products = $listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED];
        $this->inspectStopRequirements($products, $inspector, $runner);

        $products = array_merge($listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED],
                                $listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN]);

        $this->inspectReviseRequirements($products, $inspector, $runner);

        $products = $listingsProductsByStatus[\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED];
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
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                    $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator')
                );
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

            if ($amazonTemplate->isStopAdvancedRulesEnabled()) {

                $templateId = $amazonTemplate->getId();
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
                \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator')
            );
        }
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

            $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
            $configurator->setPartialMode();

            $needRevise = false;

            if (!$listingProduct->isUnknown() && $inspector->isMeetReviseQtyRequirements($listingProduct)) {
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

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

            if ($amazonTemplate->isRelistAdvancedRulesEnabled()) {

                $templateId = $amazonTemplate->getId();
                $storeId    = $listingProduct->getListing()->getStoreId();
                $magentoProductId = $listingProduct->getProductId();

                $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

            } else {

                $runner->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
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
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                $this->getRelistConfigurator($listingProduct)
            );
        }
    }

    protected function getRelistConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

        if (!$amazonListingProduct->getAmazonSynchronizationTemplate()->isRelistSendData()) {
            $configurator->setPartialMode();
            $configurator->allowQty();
        }

        return $configurator;
    }

    // ########################################

    protected function processParentProcessors()
    {
        $processedParentListingProducts = array();

        foreach ($this->successfulListingProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $variationManager = $amazonListingProduct->getVariationManager();

            if (!$variationManager->isRelationMode()) {
                continue;
            }

            if ($variationManager->isRelationParentType()) {
                $parentListingProduct = $listingProduct;
            } else {
                $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
            }

            if (isset($processedParentListingProducts[$parentListingProduct->getId()])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonParentListingProduct */
            $amazonParentListingProduct = $parentListingProduct->getChildObject();

            $parentTypeModel = $amazonParentListingProduct->getVariationManager()->getTypeModel();
            $parentTypeModel->getProcessor()->process();

            $processedParentListingProducts[$parentListingProduct->getId()] = true;
        }
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

        foreach ($responseData['messages'] as $listingProductId => $messagesData) {
            $messages = array();

            foreach ($messagesData as $messageData) {
                $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                $message->initFromResponseData($messageData);

                $messages[] = $message;
            }

            $responseMessages[$listingProductId] = $messages;
        }

        foreach ($this->listingsProducts as $listingProduct) {

            $messages = array();
            if (!empty($responseMessages[$listingProduct->getId()])) {
                $messages = $responseMessages[$listingProduct->getId()];
            }

            if (!$this->processMessages($listingProduct, $messages)) {
                if (!empty($responseData[$listingProduct->getId()]['is_skipped'])) {
                    $this->skippedListingsProducts[$listingProduct->getId()] = $listingProduct;
                }

                continue;
            }

            $successParams = $this->getSuccessfulParams($listingProduct);
            $this->processSuccess($listingProduct, $successParams);
        }
    }

    //----------------------------------------

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

    protected function processSuccess(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $params = array())
    {
        $this->getResponseObject($listingProduct)->processSuccess($params);

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

    protected function getSuccessfulParams(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        return array();
    }

    //----------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return string
     */
    abstract protected function getSuccessfulMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct);

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

    protected function getConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (empty($this->configurators[$listingProduct->getId()])) {

            $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
            $configurator->setUnserializedData($this->params['products'][$listingProduct->getId()]['configurator']);

            $this->configurators[$listingProduct->getId()] = $configurator;
        }

        return $this->configurators[$listingProduct->getId()];
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
     */
    protected function getResponseObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->responsesObjects[$listingProduct->getId()])) {

            /* @var $response \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($listingProduct);
            $response->setConfigurator($this->getConfigurator($listingProduct));
            $response->setRequestData($this->getRequestDataObject($listingProduct));

            $this->responsesObjects[$listingProduct->getId()] = $response;
        }

        return $this->responsesObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->requestsDataObjects[$listingProduct->getId()])) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Amazon\Listing\Product\Action\RequestData');

            $requestData->setData($this->params['products'][$listingProduct->getId()]['request']);
            $requestData->setListingProduct($listingProduct);

            $this->requestsDataObjects[$listingProduct->getId()] = $requestData;
        }

        return $this->requestsDataObjects[$listingProduct->getId()];
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