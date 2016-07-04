<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

class MultipleRequester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    private $generalValidatorsObjects = array();

    private $skuGeneralValidatorsObjects = array();

    private $skuSearchValidatorsObjects = array();

    private $skuExistenceValidatorsObjects = array();

    private $listTypeValidatorsObjects = array();

    private $validatorsData = array();

    // ########################################

    public function setListingsProducts(array $listingsProducts)
    {
        parent::setListingsProducts($listingsProducts);

        foreach ($this->listingsProducts as $listingProduct) {
            $listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK);
            $listingProduct->setData('synch_reasons', null);

            $additionalData = $listingProduct->getAdditionalData();
            unset($additionalData['synch_template_list_rules_note']);
            $listingProduct->setSettings('additional_data', $additionalData);

            $listingProduct->save();
        }

        return $this;
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon\Connector\Product\ListAction\ProcessingRunner';
    }

    // ########################################

    public function getCommand()
    {
        return array('product','add','entities');
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
        $resultListingProducts = array();

        foreach ($listingProducts as $listingProduct) {
            if (!$listingProduct->isNotListed() || !$listingProduct->isListable()) {
                continue;
            }

            $resultListingProducts[] = $listingProduct;
        }

        return $resultListingProducts;
    }

    // ########################################

    protected function validateAndFilterListingsProducts()
    {
        $this->processGeneralValidateAndFilter();
        $this->processSkuGeneralValidateAndFilter();
        $this->processSkuSearchValidateAndFilter();
        $this->processSkuExistenceValidateAndFilter();
        $this->processListTypeValidateAndFilter();
    }

    // ########################################

    private function processGeneralValidateAndFilter()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $validator = $this->getGeneralValidatorObject($listingProduct);

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
                $this->addValidatorsData($listingProduct, $validator->getData());
                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    private function processSkuGeneralValidateAndFilter()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $validator = $this->getSkuGeneralValidatorObject($listingProduct);

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
                $this->addValidatorsData($listingProduct, $validator->getData());
                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    private function processSkuSearchValidateAndFilter()
    {
        $requestSkus = array();
        $queueOfSkus = $this->getQueueOfSkus();

        foreach ($this->listingsProducts as $listingProduct) {

            $validator = $this->getSkuSearchValidatorObject($listingProduct);
            $validator->setRequestSkus($requestSkus);
            $validator->setQueueOfSkus($queueOfSkus);

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
                $requestSkus[] = $validator->getData('sku');
                $this->addValidatorsData($listingProduct, $validator->getData());
                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    private function processSkuExistenceValidateAndFilter()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product[][] $listingProductsPacks */
        $listingProductsPacks = array_chunk($this->listingsProducts,20,true);

        foreach ($listingProductsPacks as $listingProductsPack) {

            $skus = array();

            foreach ($listingProductsPack as $listingProduct) {
                $skus[] = $this->getValidatorsData($listingProduct, 'sku');
            }

            try {

                $countTriedTemp = 0;

                do {

                    $countTriedTemp != 0 && sleep(3);

                    /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
                    $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
                    $connectorObj = $dispatcherObject->getVirtualConnector('product','search','asinBySkus',
                                                                           array('include_info' => true,
                                                                                 'only_realtime' => true,
                                                                                 'items' => $skus),
                                                                           'items',
                                                                           $this->account->getId());
                    $dispatcherObject->process($connectorObj);
                    $response = $connectorObj->getResponseData();

                    if (is_null($response) && $connectorObj->getResponse()->getMessages()->hasErrorEntities()) {
                        throw new \Ess\M2ePro\Model\Exception(
                            $connectorObj->getResponse()->getMessages()->getCombinedErrorsString()
                        );
                    }

                } while (is_null($response) && ++$countTriedTemp <= 3);

                if (is_null($response)) {
                    throw new \Ess\M2ePro\Model\Exception('Searching of SKU in your inventory on Amazon is not
                        available now. Please repeat the action later.');
                }

            } catch (\Exception $exception) {

                $this->getHelper('Module\Exception')->process($exception);

                foreach ($listingProductsPack as $listingProduct) {

                    $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
                    $message->initFromPreparedData(
                        $this->getHelper('Module\Translation')->__($exception->getMessage()),
                       \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
                    );

                    $this->getLogger()->logListingProductMessage(
                        $listingProduct, $message,
                        \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
                    );

                    $this->removeAndUnlockListingProduct($listingProduct->getId());
                }

                continue;
            }

            foreach ($listingProductsPack as $listingProduct) {
                $sku = $this->getValidatorsData($listingProduct, 'sku');
                $existenceResult = !empty($response[$sku]) ? $response[$sku] : array();

                $validator = $this->getSkuExistenceValidatorObject($listingProduct);
                $validator->setExistenceResult($existenceResult);

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
                    $this->addValidatorsData($listingProduct, $validator->getData());
                    continue;
                }

                $this->removeAndUnlockListingProduct($listingProduct->getId());
            }
        }
    }

    private function processListTypeValidateAndFilter()
    {
        $childGeneralIdsForParent = array();

        foreach ($this->listingsProducts as $listingProduct) {

            $validator = $this->getListTypeValidatorObject($listingProduct);

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $variationManager = $amazonListingProduct->getVariationManager();

            if ($variationManager->isRelationChildType()) {
                $variationParentId = $variationManager->getVariationParentId();

                if (!isset($childGeneralIdsForParent[$variationParentId])) {
                    $childGeneralIdsForParent[$variationParentId] = array();
                }

                $validator->setChildGeneralIdsForParent(
                    $childGeneralIdsForParent[$variationParentId]
                );
            }

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
                $this->addValidatorsData($listingProduct, $validator->getData());

                if ($variationManager->isRelationChildType()) {
                    $variationParentId = $variationManager->getVariationParentId();
                    $childGeneralIdsForParent[$variationParentId][] = $this->getValidatorsData(
                        $listingProduct, 'general_id'
                    );
                }

                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    // ########################################

    private function getValidatorsData(\Ess\M2ePro\Model\Listing\Product $listingProduct, $key = null)
    {
        $listingProductId = (int)$listingProduct->getId();

        if (!isset($this->validatorsData[$listingProductId])) {
            $this->validatorsData[$listingProductId] = array();
        }

        if (is_null($key)) {
            return $this->validatorsData[$listingProductId];
        }

        return isset($this->validatorsData[$listingProductId][$key])
            ? $this->validatorsData[$listingProductId][$key] : null;
    }

    private function addValidatorsData(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $data)
    {
        $listingProductId = (int)$listingProduct->getId();

        if (!isset($this->validatorsData[$listingProductId])) {
            $this->validatorsData[$listingProductId] = array();
        }

        $this->validatorsData[$listingProductId] = array_merge($this->validatorsData[$listingProductId], $data);
    }

    // ########################################

    private function getQueueOfSkus()
    {
        /** @var \Ess\M2ePro\Model\LockItem $lockItem */
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick('amazon_list_skus_queue_' . $this->account->getId());

        if (!$lockItem->isExist()) {
            return array();
        }

        return $lockItem->getContentData();
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\General
     */
    private function getGeneralValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->generalValidatorsObjects[$listingProduct->getId()])) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\General */
            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\ListAction\Validator\General'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setData($this->getValidatorsData($listingProduct));
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->generalValidatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->generalValidatorsObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General
     */
    private function getSkuGeneralValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->skuGeneralValidatorsObjects[$listingProduct->getId()])) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General */
            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setData($this->getValidatorsData($listingProduct));
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->skuGeneralValidatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->skuGeneralValidatorsObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Search
     */
    private function getSkuSearchValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->skuSearchValidatorsObjects[$listingProduct->getId()])) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Search */
            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Search'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setData($this->getValidatorsData($listingProduct));
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->skuSearchValidatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->skuSearchValidatorsObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Existence
     */
    private function getSkuExistenceValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->skuExistenceValidatorsObjects[$listingProduct->getId()])) {

            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\Existence'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setData($this->getValidatorsData($listingProduct));
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->skuExistenceValidatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->skuExistenceValidatorsObjects[$listingProduct->getId()];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\ListType
     */
    private function getListTypeValidatorObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->listTypeValidatorsObjects[$listingProduct->getId()])) {

            /** @var $validator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\ListType */
            $validator = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\ListAction\Validator\ListType'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setData($this->getValidatorsData($listingProduct));
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->listTypeValidatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->listTypeValidatorsObjects[$listingProduct->getId()];
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
     */
    protected function getRequestObject(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!isset($this->requestsObjects[$listingProduct->getId()])) {

            /* @var $request \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request */
            $request = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\Type\ListAction\Request'
            );

            $request->setParams($this->params);
            $request->setListingProduct($listingProduct);
            $request->setConfigurator($listingProduct->getActionConfigurator());
            $request->setValidatorsData($this->getValidatorsData($listingProduct));

            $this->requestsObjects[$listingProduct->getId()] = $request;
        }

        return $this->requestsObjects[$listingProduct->getId()];
    }

    // ########################################
}