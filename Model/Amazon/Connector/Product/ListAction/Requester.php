<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\ListAction\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    protected $generalValidatorObject = null;

    protected $skuGeneralValidatorObject = null;

    protected $skuSearchValidatorObject = null;

    protected $skuExistenceValidatorObject = null;

    protected $listTypeValidatorObject = null;

    protected $validatorsData = [];

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Connector_Product_ListAction_ProcessingRunner';
    }

    //########################################

    public function getCommand()
    {
        return ['product', 'add', 'entities'];
    }

    //########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_LIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = [];

        foreach ($listingProducts as $listingProduct) {
            if (!$listingProduct->isNotListed() || !$listingProduct->isListable()) {
                continue;
            }

            $resultListingProducts[] = $listingProduct;
        }

        return $resultListingProducts;
    }

    //########################################

    protected function validateListingProduct()
    {
        return $this->validateGeneralRequirements()
            && $this->validateSkuGeneralRequirements()
            && $this->validateSkuSearchRequirements()
            && $this->validateSkuExistenceRequirements()
            && $this->validateListTypeRequirements();
    }

    //########################################

    protected function validateGeneralRequirements()
    {
        $validator = $this->getGeneralValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    protected function validateSkuGeneralRequirements()
    {
        $validator = $this->getSkuGeneralValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    protected function validateSkuSearchRequirements()
    {
        $validator = $this->getSkuSearchValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    protected function validateSkuExistenceRequirements()
    {
        $sku = $this->getValidatorsData('sku');

        try {
            $countTriedTemp = 0;

            do {
                $countTriedTemp != 0 && sleep(3);

                /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
                $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
                $connectorObj = $dispatcherObject->getVirtualConnector(
                    'product',
                    'search',
                    'asinBySkus',
                    [
                        'include_info' => true,
                        'only_realtime' => true,
                        'items' => [$sku]
                    ],
                    'items',
                    $this->account->getId()
                );
                $dispatcherObject->process($connectorObj);
                $response = $connectorObj->getResponseData();

                if ($response === null && $connectorObj->getResponse()->getMessages()->hasErrorEntities()) {
                    throw new \Ess\M2ePro\Model\Exception(
                        $connectorObj->getResponse()->getMessages()->getCombinedErrorsString()
                    );
                }
            } while ($response === null && ++$countTriedTemp <= 3);

            if ($response === null) {
                throw new \Ess\M2ePro\Model\Exception(
                    'Searching of SKU in your inventory on Amazon is not
                    available now. Please repeat the action later.'
                );
            }
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->storeLogMessage($message);

            return false;
        }

        $existenceResult = !empty($response[$sku]) ? $response[$sku] : [];

        $validator = $this->getSkuExistenceValidatorObject();
        $validator->setExistenceResult($existenceResult);

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    protected function validateListTypeRequirements()
    {
        $validator = $this->getListTypeValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    //########################################

    protected function getValidatorsData($key = null)
    {
        if ($key === null) {
            return $this->validatorsData;
        }

        return isset($this->validatorsData[$key]) ? $this->validatorsData[$key] : null;
    }

    protected function addValidatorsData(array $data)
    {
        $this->validatorsData = array_merge($this->validatorsData, $data);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\General
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getGeneralValidatorObject()
    {
        if ($this->generalValidatorObject === null) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\General */
            $validator = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Action_Type_ListAction_Validator_General'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setData($this->getValidatorsData());
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->generalValidatorObject = $validator;
        }

        return $this->generalValidatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSkuGeneralValidatorObject()
    {
        if ($this->skuGeneralValidatorObject === null) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General */
            $validator = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Action_Type_ListAction_Validator_Sku_General'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setData($this->getValidatorsData());
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->skuGeneralValidatorObject = $validator;
        }

        return $this->skuGeneralValidatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Search
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSkuSearchValidatorObject()
    {
        if ($this->skuSearchValidatorObject === null) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Search */
            $validator = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Action_Type_ListAction_Validator_Sku_Search'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setData($this->getValidatorsData());
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->skuSearchValidatorObject = $validator;
        }

        return $this->skuSearchValidatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Existence
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSkuExistenceValidatorObject()
    {
        if ($this->skuExistenceValidatorObject === null) {
            /**
             * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Existence $validator
             */
            $validator = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Action_Type_ListAction_Validator_Sku_Existence'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setData($this->getValidatorsData());
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->skuExistenceValidatorObject = $validator;
        }

        return $this->skuExistenceValidatorObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\ListType
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getListTypeValidatorObject()
    {
        if ($this->listTypeValidatorObject === null) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\ListType */
            $validator = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Action_Type_ListAction_Validator_ListType'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setData($this->getValidatorsData());
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->listTypeValidatorObject = $validator;
        }

        return $this->listTypeValidatorObject;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRequestObject()
    {
        if ($this->requestObject === null) {
            /** @var $request \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request */
            $request = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Action_Type_ListAction_Request'
            );

            $request->setParams($this->params);
            $request->setListingProduct($this->listingProduct);
            $request->setConfigurator($this->listingProduct->getActionConfigurator());
            $request->setCachedData($this->getValidatorsData());

            $this->requestObject = $request;
        }

        return $this->requestObject;
    }

    //########################################
}
