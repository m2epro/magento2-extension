<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Delete;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('product','delete','entities');
    }

    // ########################################

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

    // ########################################

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
            if (!is_null($parentListingProduct)) {
                $parentListingProduct->load($parentListingProduct->getId());

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonParentListingProduct */
                $amazonParentListingProduct = $parentListingProduct->getChildObject();
                $amazonParentListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
            }

            return false;
        }

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

    // ########################################

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

            $this->listingProduct->addData(array(
                'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            ));
            $amazonListingProduct->addData(array(
                'general_id'          => null,
                'is_general_id_owner' => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO,
            ));
            $this->listingProduct->save();

            $amazonListingProduct->getVariationManager()->switchModeToAnother();

            $this->listingProduct->delete();
        }

        if (empty($filteredByStatusNotLockedChildListingProducts)) {
            return true;
        }

        $childListingsProductsIds = array();
        foreach ($filteredByStatusNotLockedChildListingProducts as $listingProduct) {
            $childListingsProductsIds[] = $listingProduct->getId();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', array('in' => $childListingsProductsIds));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $processChildListingsProducts */
        $processChildListingsProducts = $listingProductCollection->getItems();
        if (empty($processChildListingsProducts)) {
            return true;
        }

        $dispatcherParams = array_merge($this->params, array('is_parent_action' => true));

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Product\Dispatcher');
        $processStatus = $dispatcherObject->process(
            $this->getActionType(), $processChildListingsProducts, $dispatcherParams
        );

        if ($processStatus == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            $this->getLogger()->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
        }

        return true;
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = array();

        foreach ($listingProducts as $id => $childListingProduct) {
            if ($childListingProduct->isBlocked() && empty($this->params['remove'])) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    protected function isListingProductLocked()
    {
        if (parent::isListingProductLocked()) {
            return true;
        }

        if (empty($this->params['remove'])) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
            return false;
        }

        if (!$this->listingProduct->isSetProcessingLock('child_products_in_action')) {
            return false;
        }

        // M2ePro\TRANSLATIONS
        // Another Action is being processed. Try again when the Action is completed.
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'Delete and Remove action is not supported if Child Products are in Action.',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
        );

        return true;
    }

    // ########################################
}