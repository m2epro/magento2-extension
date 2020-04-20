<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Delete;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\Delete\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    //########################################

    public function getCommand()
    {
        return ['product','delete','entities'];
    }

    //########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE;
    }

    protected function getLockIdentifier()
    {
        $identifier = parent::getLockIdentifier();

        if (!empty($this->params['remove'])) {
            $identifier .= '_and_remove';
        }

        return $identifier;
    }

    protected function getLogsAction()
    {
        return !empty($this->params['remove']) ?
              \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_AND_REMOVE_PRODUCT :
              \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT;
    }

    //########################################

    protected function validateListingProduct()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        $parentListingProduct = null;

        if ($variationManager->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
        }

        $validator = $this->getValidatorObject();

        $validationResult = $validator->validate();

        if (!$validationResult && $this->listingProduct->isDeleted()) {
            if ($parentListingProduct !== null) {
                $parentListingProduct->load($parentListingProduct->getId());

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonParentListingProduct */
                $amazonParentListingProduct = $parentListingProduct->getChildObject();
                $amazonParentListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
            }

            return false;
        }

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        return $validationResult;
    }

    //########################################

    protected function validateAndProcessParentListingProduct()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
            return false;
        }

        $childListingsProducts = $amazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getChildListingsProducts();

        $filteredByStatusChildListingProducts = $this->filterChildListingProductsByStatus($childListingsProducts);
        $filteredByStatusNotLockedChildListingProducts = $this->filterLockedChildListingProducts(
            $filteredByStatusChildListingProducts
        );

        if (empty($this->params['remove']) && empty($filteredByStatusNotLockedChildListingProducts)) {
            $this->listingProduct->setData('no_child_for_processing', true);
            return false;
        }

        $notLockedChildListingProducts = $this->filterLockedChildListingProducts($childListingsProducts);

        if (count($childListingsProducts) != count($notLockedChildListingProducts)) {
            $this->listingProduct->setData('child_locked', true);
            return false;
        }

        if (!empty($this->params['remove'])) {
            $this->listingProduct->addData([
                'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            ]);
            $amazonListingProduct->addData([
                'general_id'          => null,
                'is_general_id_owner' => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO,
            ]);
            $this->listingProduct->save();

            $amazonListingProduct->getVariationManager()->switchModeToAnother();

            $this->getProcessingRunner()->stop();

            $this->listingProduct->delete();
        }

        if (empty($filteredByStatusNotLockedChildListingProducts)) {
            return true;
        }

        $childListingsProductsIds = [];
        foreach ($filteredByStatusNotLockedChildListingProducts as $listingProduct) {
            $childListingsProductsIds[] = $listingProduct->getId();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', ['in' => $childListingsProductsIds]);

        foreach ($listingProductCollection->getItems() as $childListingProduct) {
            // @codingStandardsIgnoreStart
            $processingRunner = $this->modelFactory->getObject('Amazon_Connector_Product_ProcessingRunner');
            $processingRunner->setParams(
                [
                    'listing_product_id' => $childListingProduct->getId(),
                    'configurator'       => $this->listingProduct->getActionConfigurator()->getSerializedData(),
                    'action_type'        => $this->getActionType(),
                    'lock_identifier'    => $this->getLockIdentifier(),
                    'requester_params'   => array_merge($this->params, ['is_parent_action' => true]),
                    'group_hash'         => $this->listingProduct->getProcessingAction()->getGroupHash(),
                ]
            );
            $processingRunner->start();
            // @codingStandardsIgnoreEnd
        }

        return true;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = [];

        foreach ($listingProducts as $id => $childListingProduct) {
            if ($childListingProduct->isBlocked() && empty($this->params['remove'])) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    //########################################
}
