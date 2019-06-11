<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product;

abstract class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Requester
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    protected $listingProduct = null;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger
     */
    protected $logger = null;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    protected $storedLogMessages = array();

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator $validatorObject
     */
    protected $validatorObject = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request $requestObject
     */
    protected $requestObject = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData $requestDataObject
     */
    protected $requestDataObject = null;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
        if (!isset($params['logs_action_id']) || !isset($params['status_changer'])) {
            throw new \Ess\M2ePro\Model\Exception('Product Connector has not received some params');
        }

        parent::__construct($walmartFactory, $helperFactory, $modelFactory, $account, $params);
    }

    // ########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        if (is_null($this->listingProduct->getActionConfigurator())) {
            $this->listingProduct->setActionConfigurator(
                $this->modelFactory->getObject('Walmart\Listing\Product\Action\Configurator')
            );
        }

        if ($this->listingProduct->needSynchRulesCheck()) {
            $this->listingProduct->setData('need_synch_rules_check', 0);
            $this->listingProduct->save();
        }

        $this->account = $this->listingProduct->getAccount();
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart\Connector\Product\ProcessingRunner';
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
                'request_data'       => $this->getRequestData(),
                'listing_product_id' => $this->listingProduct->getId(),
                'lock_identifier'    => $this->getLockIdentifier(),
                'action_type'        => $this->getActionType(),
                'start_date'         => $startDate,
            )
        );
    }

    // ########################################

    protected function getLogsActionId()
    {
        return (int)$this->params['logs_action_id'];
    }

    abstract protected function getLogsAction();

    // ----------------------------------------

    protected function getLockIdentifier()
    {
        if ($this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
            return 'list';
        }

        return strtolower($this->getOrmActionType());
    }

    // ########################################

    public function process()
    {
        try {
            $this->getLogger()->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);

            if ($this->isListingProductLocked()) {
                return;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $this->listingProduct->getChildObject();

            if ($walmartListingProduct->getVariationManager()->isRelationParentType() &&
                $this->validateAndProcessParentListingProduct()) {
                return;
            }

            $this->lockListingProduct();

            if (!$this->validateListingProduct() || !$this->validateConfigurator()) {
                $this->unlockListingProduct();
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

    // ########################################

    public function getStatus()
    {
        return $this->getLogger()->getStatus();
    }

    // ########################################

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

        return $validationResult;
    }

    /**
     * Some data parts can be disallowed from configurator on validateListingProduct() action
     * @return bool
     */
    protected function validateConfigurator()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->listingProduct->getActionConfigurator();
        if (count($configurator->getAllowedDataTypes()) <= 0) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'There was no need for this action. It was skipped.
                Please check the log message above for more detailed information.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );
            return false;
        }

        return true;
    }

    // ########################################

    protected function validateAndProcessParentListingProduct()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();

        if (!$walmartListingProduct->getVariationManager()->isRelationParentType()) {
            return false;
        }

        $childListingsProducts = $walmartListingProduct->getVariationManager()
            ->getTypeModel()
            ->getChildListingsProducts();

        $childListingsProducts = $this->filterChildListingProductsByStatus($childListingsProducts);
        $childListingsProducts = $this->filterLockedChildListingProducts($childListingsProducts);

        if (empty($childListingsProducts)) {
            $this->listingProduct->setData('no_child_for_processing', true);
            return false;
        }

        $dispatcherParams = array_merge($this->params, array('is_parent_action' => true));

        $dispatcherObject = $this->modelFactory->getObject('Walmart\Connector\Product\Dispatcher');
        $processStatus = $dispatcherObject->process(
            $this->getActionType(), $childListingsProducts, $dispatcherParams
        );

        if ($processStatus == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            $this->getLogger()->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
        }

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    abstract protected function filterChildListingProductsByStatus(array $listingProducts);

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterLockedChildListingProducts(array $listingProducts)
    {
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');

        $resultListingProducts = array();
        foreach ($listingProducts as $listingProduct) {
            $lockItem->setNick(
                \Ess\M2ePro\Helper\Component\Walmart::NICK.'_listing_product_'.$listingProduct->getId()
            );

            if ($listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {
                continue;
            }

            $resultListingProducts[] = $listingProduct;
        }

        return $resultListingProducts;
    }

    // ########################################

    protected function isListingProductLocked()
    {
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(
            \Ess\M2ePro\Helper\Component\Walmart::NICK.'_listing_product_'.$this->listingProduct->getId()
        );

        if ($this->listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {

            // M2ePro_TRANSLATIONS
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
        $lockItem->setNick(
            \Ess\M2ePro\Helper\Component\Walmart::NICK.'_listing_product_'.$this->listingProduct->getId()
        );

        $lockItem->create();
        $lockItem->makeShutdownFunction();
    }

    protected function unlockListingProduct()
    {
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(
            \Ess\M2ePro\Helper\Component\Walmart::NICK.'_listing_product_'.$this->listingProduct->getId()
        );

        $lockItem->remove();
    }

    // ########################################

    protected function getRequestData()
    {
        $requestObject = $this->getRequestObject();
        $requestDataRaw = $requestObject->getRequestData();

        foreach ($requestObject->getWarningMessages() as $messageText) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                $messageText,
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );
        }

        $requestDataRaw = array_merge($requestDataRaw, array('id' => $this->listingProduct->getId()));

        $this->buildRequestDataObject($requestDataRaw);

        return $requestDataRaw;
    }

    protected function getResponserParams()
    {
        $product = array(
            'request'      => $this->getRequestDataObject()->getData(),
            'configurator' => $this->listingProduct->getActionConfigurator()->getSerializedData(),
            'id'               => $this->listingProduct->getId(),
        );

        return array(
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

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger
     */
    protected function getLogger()
    {
        if (is_null($this->logger)) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Walmart\Listing\Product\Action\Logger');

            $logger->setActionId($this->getLogsActionId());
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

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator
     */
    protected function getValidatorObject()
    {
        if (is_null($this->validatorObject)) {

            /** @var $validator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator */
            $validator = $this->modelFactory->getObject(
                'Walmart\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Validator'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->validatorObject = $validator;
        }

        return $this->validatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request
     */
    protected function getRequestObject()
    {
        if (is_null($this->requestObject)) {

            /* @var $request \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request */
            $request = $this->modelFactory->getObject(
                'Walmart\Listing\Product\Action\Type\\' . $this->getOrmActionType() . '\Request'
            );

            $request->setParams($this->params);
            $request->setListingProduct($this->listingProduct);
            $request->setConfigurator($this->listingProduct->getActionConfigurator());
            $request->setCachedData($this->getValidatorObject()->getValidatorData());

            $this->requestObject = $request;
        }

        return $this->requestObject;
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        return $this->requestDataObject;
    }

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData
     */
    protected function buildRequestDataObject(array $data)
    {
        if (is_null($this->requestDataObject)) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Walmart\Listing\Product\Action\RequestData');

            $requestData->setData($data);
            $requestData->setListingProduct($this->listingProduct);

            $this->requestDataObject = $requestData;
        }

        return $this->requestDataObject;
    }

    // ########################################

    private function getOrmActionType()
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

    abstract protected function getActionType();

    // ########################################
}