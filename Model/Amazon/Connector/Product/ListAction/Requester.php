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
    private $generalValidatorObject = null;

    private $skuGeneralValidatorObject = null;

    private $skuSearchValidatorObject = null;

    private $skuExistenceValidatorObject = null;

    private $listTypeValidatorObject = null;

    private $validatorsData = [];

    // ########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        parent::setListingProduct($listingProduct);

        $this->listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK);
        $this->listingProduct->setData('synch_reasons', null);

        $additionalData = $listingProduct->getAdditionalData();
        unset($additionalData['synch_template_list_rules_note']);
        $this->listingProduct->setSettings('additional_data', $additionalData);

        $this->listingProduct->save();

        return $this;
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Connector_Product_ListAction_ProcessingRunner';
    }

    // ########################################

    public function getCommand()
    {
        return ['product','add','entities'];
    }

    // ########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_LIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

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

    // ########################################

    protected function validateListingProduct()
    {
        return $this->validateGeneralRequirements()
            && $this->validateSkuGeneralRequirements()
            && $this->validateSkuSearchRequirements()
            && $this->validateSkuExistenceRequirements()
            && $this->validateListTypeRequirements();
    }

    // ########################################

    private function validateGeneralRequirements()
    {
        $validator = $this->getGeneralValidatorObject();

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

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    private function validateSkuGeneralRequirements()
    {
        $validator = $this->getSkuGeneralValidatorObject();

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

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    private function validateSkuSearchRequirements()
    {
        $validator = $this->getSkuSearchValidatorObject();

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

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    private function validateSkuExistenceRequirements()
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
                    ['include_info' => true,
                                                                              'only_realtime' => true,
                                                                              'items' => [$sku]],
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
                throw new \Ess\M2ePro\Model\Exception('Searching of SKU in your inventory on Amazon is not
                                                       available now. Please repeat the action later.');
            }
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            return false;
        }

        $existenceResult = !empty($response[$sku]) ? $response[$sku] : [];

        $validator = $this->getSkuExistenceValidatorObject();
        $validator->setExistenceResult($existenceResult);

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

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    private function validateListTypeRequirements()
    {
        $validator = $this->getListTypeValidatorObject();

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

        if ($validationResult) {
            $this->addValidatorsData($validator->getData());
            return true;
        }

        return false;
    }

    // ########################################

    private function getValidatorsData($key = null)
    {
        if ($key === null) {
            return $this->validatorsData;
        }

        return isset($this->validatorsData[$key]) ? $this->validatorsData[$key] : null;
    }

    private function addValidatorsData(array $data)
    {
        $this->validatorsData = array_merge($this->validatorsData, $data);
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\General
     */
    private function getGeneralValidatorObject()
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
     */
    private function getSkuGeneralValidatorObject()
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
     */
    private function getSkuSearchValidatorObject()
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
     */
    private function getSkuExistenceValidatorObject()
    {
        if ($this->skuExistenceValidatorObject === null) {
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
     */
    private function getListTypeValidatorObject()
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

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request
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
            $request->setValidatorsData($this->getValidatorsData());

            $this->requestObject = $request;
        }

        return $this->requestObject;
    }

    // ########################################
}
