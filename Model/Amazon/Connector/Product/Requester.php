<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
 */
abstract class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    protected $listingProduct = null;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected $logger = null;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
     */
    protected $validatorObject = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
     */
    protected $requestObject = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected $requestDataObject = null;

    protected $activeRecordFactory;
    protected $amazonFactory;

    // ########################################

    /**
     * Requester constructor.
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\Account|NULL $account
     * @param array $params
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
        if (!isset($params['logs_action_id']) || !isset($params['status_changer'])) {
            throw new \Ess\M2ePro\Model\Exception('Product Connector has not received some params');
        }

        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory, $account, $params);
    }

    // ########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if ($listingProduct->getActionConfigurator() !== null) {
            $actionConfigurator = $listingProduct->getActionConfigurator();
        } else {
            $actionConfigurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
        }

        $this->listingProduct = $listingProduct->load($listingProduct->getId());

        if ($this->listingProduct->needSynchRulesCheck()) {
            $this->listingProduct->setData('need_synch_rules_check', 0);
            $this->listingProduct->save();
        }

        $this->listingProduct->setActionConfigurator($actionConfigurator);

        $this->account = $this->listingProduct->getAccount();
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Connector_Product_ProcessingRunner';
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
            [
                'request_data'       => $this->getRequestData(),
                'listing_product_id' => $this->listingProduct->getId(),
                'lock_identifier'    => $this->getLockIdentifier(),
                'action_type'        => $this->getActionType(),
                'start_date'         => $startDate,
            ]
        );
    }

    // ########################################

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

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $this->listingProduct->getChildObject();

            if ($amazonListingProduct->getVariationManager()->isRelationParentType() &&
                $this->validateAndProcessParentListingProduct()) {
                return;
            }

            $this->lockListingProduct();

            if (!$this->validateListingProduct()) {
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
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );
        }

        return $validationResult;
    }

    // ########################################

    protected function validateAndProcessParentListingProduct()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
            return false;
        }

        if (!$amazonListingProduct->getGeneralId()) {
            return false;
        }

        $childListingsProducts = $amazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getChildListingsProducts();

        $childListingsProducts = $this->filterChildListingProductsByStatus($childListingsProducts);
        $childListingsProducts = $this->filterLockedChildListingProducts($childListingsProducts);

        if (empty($childListingsProducts)) {
            $this->listingProduct->setData('no_child_for_processing', true);
            return false;
        }

        $dispatcherParams = array_merge($this->params, ['is_parent_action' => true]);

        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Product_Dispatcher');
        $processStatus = $dispatcherObject->process(
            $this->getActionType(),
            $childListingsProducts,
            $dispatcherParams
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
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager');

        $resultListingProducts = [];
        foreach ($listingProducts as $listingProduct) {
            $lockItem->setNick(
                \Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$listingProduct->getId()
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
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager');
        $lockItem->setNick(
            \Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$this->listingProduct->getId()
        );

        if ($this->listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {
            // M2ePro_TRANSLATIONS
            // Another Action is being processed. Try again when the Action is completed.
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
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
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager');
        $lockItem->setNick(
            \Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$this->listingProduct->getId()
        );

        $lockItem->create();
        $lockItem->makeShutdownFunction();
    }

    protected function unlockListingProduct()
    {
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager');
        $lockItem->setNick(
            \Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$this->listingProduct->getId()
        );

        $lockItem->remove();
    }

    // ########################################

    protected function getRequestData()
    {
        $requestObject  = $this->getRequestObject();
        $requestDataRaw = $requestObject->getRequestData();

        foreach ($requestObject->getWarningMessages() as $messageText) {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
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

        $this->buildRequestDataObject($requestDataRaw);

        return array_merge($requestDataRaw, ['id' => $this->listingProduct->getId()]);
    }

    protected function getResponserParams()
    {
        $product = [
            'request'      => $this->getRequestDataObject()->getData(),
            'configurator' => $this->listingProduct->getActionConfigurator()->getSerializedData(),
            'id'           => $this->listingProduct->getId(),
        ];

        return [
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

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected function getLogger()
    {
        if ($this->logger === null) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger $logger */
            $logger = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Logger');

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

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
     */
    protected function getValidatorObject()
    {
        if ($this->validatorObject === null) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator */
            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Validator'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->validatorObject = $validator;
        }

        return $this->validatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
     */
    protected function getRequestObject()
    {
        if ($this->requestObject === null) {
            /** @var $request \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request */
            $request = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Request'
            );

            $request->setParams($this->params);
            $request->setListingProduct($this->listingProduct);
            $request->setConfigurator($this->listingProduct->getActionConfigurator());
            $request->setValidatorsData($this->getValidatorObject()->getData());

            $this->requestObject = $request;
        }

        return $this->requestObject;
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        return $this->requestDataObject;
    }

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function buildRequestDataObject(array $data)
    {
        if ($this->requestDataObject === null) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Amazon_Listing_Product_Action_RequestData');

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
