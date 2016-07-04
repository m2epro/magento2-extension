<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Single;

abstract class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Requester
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator $validatorObject */
    protected $validatorObject = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
     */
    protected $requestObject = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected $requestDataObject = NULL;

    protected $activeRecordFactory;

    //########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($marketplace, $account, $helperFactory, $modelFactory, $params);
    }

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!is_null($listingProduct->getActionConfigurator())) {
            $actionConfigurator = $listingProduct->getActionConfigurator();
        } else {
            $actionConfigurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
        }

        $this->listingProduct = $listingProduct->load($listingProduct->getId());
        $this->listingProduct->setActionConfigurator($actionConfigurator);

        $this->marketplace = $this->listingProduct->getMarketplace();
        $this->account     = $this->listingProduct->getAccount();
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay\Connector\Item\Single\ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data'        => $this->getRequestData(),
                'listing_product_id'  => $this->listingProduct->getId(),
                'lock_identifier'     => $this->getLockIdentifier(),
                'action_type'         => $this->getActionType(),
                'request_timeout'     => $this->getRequestTimeout(),
            )
        );
    }

    //########################################

    public function getRequestTimeout()
    {
        $requestDataObject = $this->getRequestDataObject();
        $requestData = $requestDataObject->getData();

        if ($requestData['is_eps_ebay_images_mode'] === false ||
            (is_null($requestData['is_eps_ebay_images_mode']) &&
                $requestData['upload_images_mode'] ==
               \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description::UPLOAD_IMAGES_MODE_SELF)) {
            return parent::getRequestTimeout();
        }

        $imagesTimeout = self::TIMEOUT_INCREMENT_FOR_ONE_IMAGE * $requestDataObject->getTotalImagesCount();
        return parent::getRequestTimeout() + $imagesTimeout;
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
            if (!$this->validateListingProduct()) {
                return;
            }

            parent::process();
        } catch (\Exception $exception) {
            $this->unlockListingProduct();
            throw $exception;
        }

        $this->unlockListingProduct();
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
                $messageText,\Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
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
                \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
            );
        }

        if ($validationResult) {
            return true;
        }

        $this->unlockListingProduct();

        return false;
    }

    //########################################

    protected function isListingProductLocked()
    {
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
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
                \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
            );

            return true;
        }

        return false;
    }

    // ########################################

    protected function lockListingProduct()
    {
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        $lockItem->create();
        $lockItem->makeShutdownFunction();
    }

    protected function unlockListingProduct()
    {
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        $lockItem->remove();
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
        $request->setValidatorsData($this->getValidatorObject($this->listingProduct)->getData());

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

    //########################################
}