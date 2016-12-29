<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

abstract class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product[]
     */
    protected $listingsProducts = array();

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected $logger = NULL;

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator[]
     */
    protected $validatorsObjects = array();

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request[]
     */
    protected $requestsObjects = array();

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData[]
     */
    protected $requestsDataObjects = array();

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
    )
    {
        if (!isset($params['logs_action_id']) || !isset($params['status_changer'])) {
            throw new \Ess\M2ePro\Model\Exception('Product Connector has not received some params');
        }

        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory, $account, $params);
    }

    // ########################################

    public function setListingsProducts(array $listingsProducts)
    {
        if (empty($listingsProducts)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Product connector receive empty products list.');
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = reset($listingsProducts)->getListing()->getAccount();

        $listingProductIds   = array();
        $actionConfigurators = array();

        foreach ($listingsProducts as $listingProduct) {

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has received invalid Product data type');
            }

            if ($account->getId() != $listingProduct->getListing()->getAccountId()) {
                throw new \Ess\M2ePro\Model\Exception(
                    'Product Connector has received Products from different Accounts'
                );
            }

            $listingProductIds[] = $listingProduct->getId();

            if (!is_null($listingProduct->getActionConfigurator())) {
                $actionConfigurators[$listingProduct->getId()] = $listingProduct->getActionConfigurator();
            } else {
                $actionConfigurators[$listingProduct->getId()] = $this->modelFactory->getObject(
                    'Amazon\Listing\Product\Action\Configurator'
                );
            }
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', array('in' => array_unique($listingProductIds)));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $actualListingsProducts */
        $actualListingsProducts = $listingProductCollection->getItems();

        if (empty($actualListingsProducts)) {
            throw new \Ess\M2ePro\Model\Exception('All products were removed before connector processing');
        }

        foreach ($actualListingsProducts as $actualListingProduct) {
            $actualListingProduct->setActionConfigurator($actionConfigurators[$actualListingProduct->getId()]);
            $this->listingsProducts[$actualListingProduct->getId()] = $actualListingProduct;
        }

        $this->account = $account;

        return $this;
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon\Connector\Product\ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data'        => $this->getRequestData(),
                'listing_product_ids' => array_keys($this->listingsProducts),
                'lock_identifier'     => $this->getLockIdentifier(),
                'action_type'         => $this->getActionType(),
            )
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

            $this->prepareListingsProducts();

            if (empty($this->listingsProducts)) {
                return;
            }

            $this->eventBeforeExecuting();
            $this->getProcessingRunner()->start();

        } catch (\Exception $exception) {
            $this->unlockListingsProducts();
            throw $exception;
        }

        $this->unlockListingsProducts();
    }

    // ########################################

    public function getStatus()
    {
        return $this->getLogger()->getStatus();
    }

    // ########################################

    private function prepareListingsProducts()
    {
        $this->filterLockedListingsProducts();
        $this->validateAndProcessParentListingsProducts();
        $this->lockListingsProducts();
        $this->validateAndFilterListingsProducts();
    }

    // ########################################

    protected function validateAndFilterListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $validator = $this->getValidatorObject($listingProduct);

            $validationResult = $validator->validate();

            foreach ($validator->getMessages() as $messageData) {

                $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                $message->initFromPreparedData($messageData['text'], $messageData['type']);

                $this->getLogger()->logListingProductMessage(
                    $listingProduct,
                    $message,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                );
            }

            if ($validationResult) {
                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    // ########################################

    protected function validateAndProcessParentListingsProducts()
    {
        $processChildListingsProducts = array();

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            if (!$amazonListingProduct->getGeneralId()) {
                continue;
            }

            $childListingsProducts = $amazonListingProduct->getVariationManager()
                ->getTypeModel()
                ->getChildListingsProducts();

            $childListingsProducts = $this->filterChildListingProductsByStatus($childListingsProducts);
            $childListingsProducts = $this->filterLockedChildListingProducts($childListingsProducts);

            if (empty($childListingsProducts)) {
                $listingProduct->setData('no_child_for_processing', true);
                continue;
            }

            $processChildListingsProducts = array_merge(
                $processChildListingsProducts, $childListingsProducts
            );

            unset($this->listingsProducts[$listingProduct->getId()]);
        }

        if (empty($processChildListingsProducts)) {
            return;
        }

        $dispatcherParams = array_merge($this->params, array('is_parent_action' => true));

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Product\Dispatcher');
        $processStatus = $dispatcherObject->process(
            $this->getActionType(), $processChildListingsProducts, $dispatcherParams
        );

        if ($processStatus == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            $this->getLogger()->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
        }
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
        $lockItem = $this->activeRecordFactory->getObject('LockItem');

        $resultListingProducts = array();
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

    protected function filterLockedListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $lockItem = $this->activeRecordFactory->getObject('LockItem');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$listingProduct->getId());

            if ($listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {

                // M2ePro\TRANSLATIONS
                // Another Action is being processed. Try again when the Action is completed.
                $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                $message->initFromPreparedData(
                    'Another Action is being processed. Try again when the Action is completed.',
                   \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
                );

                $this->getLogger()->logListingProductMessage(
                    $listingProduct,
                    $message,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                );

                unset($this->listingsProducts[$listingProduct->getId()]);
            }
        }
    }

    protected function removeAndUnlockListingProduct($listingProductId)
    {
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$listingProductId);
        $lockItem->remove();

        unset($this->listingsProducts[$listingProductId]);
    }

    // ########################################

    protected function lockListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {
            $lockItem = $this->activeRecordFactory->getObject('LockItem');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$listingProduct->getId());

            $lockItem->create();
            $lockItem->makeShutdownFunction();
        }
    }

    protected function unlockListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $lockItem = $this->activeRecordFactory->getObject('LockItem');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Amazon::NICK.'_listing_product_'.$listingProduct->getId());

            $lockItem->remove();
        }
    }

    // ########################################

    protected function getRequestData()
    {
        $data = array(
            'items' => array()
        );

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $requestObject = $this->getRequestObject($listingProduct);
            $requestDataRaw = $requestObject->getRequestData();

            foreach ($requestObject->getWarningMessages() as $messageText) {

                $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                $message->initFromPreparedData(
                    $messageText,
                   \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );

                $this->getLogger()->logListingProductMessage($listingProduct,
                                                             $message,
                                                             \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM);
            }

            $this->buildRequestDataObject($listingProduct,$requestDataRaw);

            $data['items'][$listingProduct->getId()] = $requestDataRaw;
            $data['items'][$listingProduct->getId()]['id'] = $listingProduct->getId();
        }

        return $data;
    }

    protected function getResponserParams()
    {
        $products = array();

        foreach ($this->listingsProducts as $listingProduct) {
            $products[$listingProduct->getId()] = array(
                'request'      => $this->getRequestDataObject($listingProduct)->getData(),
                'configurator' => $listingProduct->getActionConfigurator()->getSerializedData(),
            );
        }

        return array(
            'account_id'      => $this->account->getId(),
            'action_type'     => $this->getActionType(),
            'lock_identifier' => $this->getLockIdentifier(),
            'logs_action'     => $this->getLogsAction(),
            'logs_action_id'  => $this->getLogger()->getActionId(),
            'status_changer'  => $this->params['status_changer'],
            'params'          => $this->params,
            'products'        => $products,
        );
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger
     */
    protected function getLogger()
    {
        if (is_null($this->logger)) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Logger $logger */
            $logger = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Logger');

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
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
     */
    protected function getValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->validatorsObjects[$listingProduct->getId()])) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator */
            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Validator'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->validatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->validatorsObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
     */
    protected function getRequestObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->requestsObjects[$listingProduct->getId()])) {

            /* @var $request \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request */
            $request = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Request'
            );

            $request->setParams($this->params);
            $request->setListingProduct($listingProduct);
            $request->setConfigurator($listingProduct->getActionConfigurator());
            $request->setValidatorsData($this->getValidatorObject($listingProduct)->getData());

            $this->requestsObjects[$listingProduct->getId()] = $request;
        }

        return $this->requestsObjects[$listingProduct->getId()];
    }

    // ----------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param array $data
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function buildRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $data)
    {
        if (!isset($this->requestsDataObjects[$listingProduct->getId()])) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Amazon\Listing\Product\Action\RequestData');

            $requestData->setData($data);
            $requestData->setListingProduct($listingProduct);

            $this->requestsDataObjects[$listingProduct->getId()] = $requestData;
        }

        return $this->requestsDataObjects[$listingProduct->getId()];
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