<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Multiple;

abstract class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Requester
{
    /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
    protected $listingsProducts = array();

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator[] $validatorsObjects */
    protected $validatorsObjects = array();

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request[]
     */
    protected $requestsObjects = array();

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData[]
     */
    protected $requestsDataObjects = array();

    protected $activeRecordFactory;
    protected $ebayFactory;

    //########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($marketplace, $account, $helperFactory, $modelFactory, $params);
    }

    //########################################

    public function setListingsProducts(array $listingsProducts)
    {
        if (count($listingsProducts) > $this->getMaxProductsCount()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Maximum products count is exceeded');
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = reset($listingsProducts)->getAccount();
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = reset($listingsProducts)->getMarketplace();

        $listingProductIds   = array();
        $actionConfigurators = array();

        foreach($listingsProducts as $listingProduct) {

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                throw new \Ess\M2ePro\Model\Exception('Multiple Item Connector has received invalid Product data type');
            }

            if ($account->getId() != $listingProduct->getListing()->getAccountId()) {
                throw new \Ess\M2ePro\Model\Exception(
                    'Multiple Item Connector has received Products from different Accounts'
                );
            }

            if ($marketplace->getId() != $listingProduct->getListing()->getMarketplaceId()) {
                throw new \Ess\M2ePro\Model\Exception(
                    'Multiple Item Connector has received Products from different Marketplaces'
                );
            }

            $listingProductIds[] = $listingProduct->getId();

            if (!is_null($listingProduct->getActionConfigurator())) {
                $actionConfigurators[$listingProduct->getId()] = $listingProduct->getActionConfigurator();
            } else {
                $actionConfigurators[$listingProduct->getId()] = $this->modelFactory->getObject(
                    'Ebay\Listing\Product\Action\Configurator'
                );
            }
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
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

        $this->marketplace = $marketplace;
        $this->account     = $account;

        return $this;
    }

    abstract public function getMaxProductsCount();

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay\Connector\Item\Multiple\ProcessingRunner';
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
                'request_timeout'     => $this->getRequestTimeOut(),
            )
        );
    }

    //########################################

    public function process()
    {
        try {
            $this->getLogger()->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);

            $this->filterLockedListingsProducts();
            $this->lockListingsProducts();
            $this->validateAndFilterListingsProducts();

            if (empty($this->listingsProducts)) {
                return;
            }

            parent::process();
        } catch (\Exception $exception) {
            $this->unlockListingsProducts();
            throw $exception;
        }

        $this->unlockListingsProducts();
    }

    protected function processResponser()
    {
        $this->unlockListingsProducts();
        parent::processResponser();
    }

    //########################################

    protected function getRequestData()
    {
        $data = array(
            'products' => array()
        );

        foreach ($this->listingsProducts as $listingProduct) {

            $tempData = $this->getRequestObject($listingProduct)->getRequestData();

            foreach ($this->getRequestObject($listingProduct)->getWarningMessages() as $messageText) {

                $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                $message->initFromPreparedData(
                    $messageText,\Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );

                $this->getLogger()->logListingProductMessage(
                    $listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
                );
            }

            $data['products'][$listingProduct->getId()] = $this->buildRequestDataObject(
                $listingProduct,$tempData
            )->getData();
        }

        return $data;
    }

    //########################################

    protected function getResponserParams()
    {
        $products = array();

        foreach ($this->listingsProducts as $listingProduct) {
            $products[$listingProduct->getId()] = array(
                'request'          => $this->getRequestDataObject($listingProduct)->getData(),
                'request_metadata' => $this->getRequestObject($listingProduct)->getMetaData(),
                'configurator'     => $listingProduct->getActionConfigurator()->getSerializedData(),
            );
        }

        return array(
            'is_realtime'     => $this->isRealTime(),
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

    //########################################

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
                    \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
                );
            }

            if ($validationResult) {
                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    //########################################

    protected function filterLockedListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $lockItem = $this->activeRecordFactory->getObject('LockItem');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$listingProduct->getId());

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
                    \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
                );

                unset($this->listingsProducts[$listingProduct->getId()]);
            }
        }
    }

    protected function removeAndUnlockListingProduct($listingProductId)
    {
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$listingProductId);
        $lockItem->remove();

        unset($this->listingsProducts[$listingProductId]);
    }

    // ########################################

    protected function lockListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {
            $lockItem = $this->activeRecordFactory->getObject('LockItem');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$listingProduct->getId());

            $lockItem->create();
            $lockItem->makeShutdownFunction();
        }
    }

    protected function unlockListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $lockItem = $this->activeRecordFactory->getObject('LockItem');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$listingProduct->getId());

            $lockItem->remove();
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
     */
    protected function getValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->validatorsObjects[$listingProduct->getId()])) {

            /** @var $validator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator */
            $validator = $this->modelFactory->getObject(
                'Ebay\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Validator'
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
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
     */
    protected function getRequestObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->requestsObjects[$listingProduct->getId()])) {
            $this->requestsObjects[$listingProduct->getId()] = $this->makeRequestObject($listingProduct);
        }
        return $this->requestsObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
     */
    protected function makeRequestObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request $request */

        $request = $this->modelFactory->getObject(
            'Ebay\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Request'
        );

        $request->setParams($this->params);
        $request->setListingProduct($listingProduct);
        $request->setConfigurator($listingProduct->getActionConfigurator());

        return $request;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param array $data
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function buildRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $data)
    {
        if (!isset($this->requestsDataObjects[$listingProduct->getId()])) {
            $this->requestsDataObjects[$listingProduct->getId()] = $this->makeRequestDataObject($listingProduct,$data);
        }
        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param array $data
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function makeRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $data)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $requestData */

        $requestData = $this->modelFactory->getObject('Ebay\Listing\Product\Action\RequestData');

        $requestData->setData($data);
        $requestData->setListingProduct($listingProduct);

        return $requestData;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    //########################################
}