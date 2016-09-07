<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Stop;

class MultipleRequester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('product','update','entities');
    }

    // ########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
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
              \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_AND_REMOVE_PRODUCT :
              \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function validateAndFilterListingsProducts()
    {
        $parentsForProcessing = array();

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $variationManager = $amazonListingProduct->getVariationManager();

            $parentListingProduct = null;

            if ($variationManager->isRelationChildType()) {
                $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
            }

            $listingProductId = $listingProduct->getId();

            $validator = $this->getValidatorObject($listingProduct);

            $validationResult = $validator->validate();

            if (!$validationResult && $listingProduct->isDeleted()) {
                $this->removeAndUnlockListingProduct($listingProductId);

                if (!is_null($parentListingProduct)) {
                    $parentListingProductId = $parentListingProduct->getId();
                    $parentsForProcessing[$parentListingProductId] = $parentListingProduct->load(
                        $parentListingProductId
                    );
                }

                continue;
            }

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

            $this->removeAndUnlockListingProduct($listingProductId);
        }

        foreach ($parentsForProcessing as $parentListingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonParentListingProduct */
            $amazonParentListingProduct = $parentListingProduct->getChildObject();
            $amazonParentListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }

    // ########################################

    protected function validateAndProcessParentListingsProducts()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product[] $processChildListingsProducts */
        $processChildListingsProducts = array();

        foreach ($this->listingsProducts as $key => $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $childListingsProducts = $amazonListingProduct->getVariationManager()
                ->getTypeModel()
                ->getChildListingsProducts();

            $filteredByStatusChildListingProducts = $this->filterChildListingProductsByStatus($childListingsProducts);
            $filteredByStatusNotLockedChildListingProducts = $this->filterLockedChildListingProducts(
                $filteredByStatusChildListingProducts
            );

            if (empty($this->params['remove'])) {
                if (empty($filteredByStatusNotLockedChildListingProducts)) {
                    $listingProduct->setData('no_child_for_processing', true);
                    continue;
                }

                $processChildListingsProducts = array_merge(
                    $processChildListingsProducts, $filteredByStatusNotLockedChildListingProducts
                );

                unset($this->listingsProducts[$key]);

                continue;
            }

            $notLockedChildListingProducts = $this->filterLockedChildListingProducts($childListingsProducts);

            if (count($childListingsProducts) != count($notLockedChildListingProducts)) {
                $listingProduct->setData('child_locked', true);
                continue;
            }

            $processChildListingsProducts = array_merge(
                $processChildListingsProducts, $filteredByStatusNotLockedChildListingProducts
            );

            $listingProduct->addData(array(
                'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            ));
            $amazonListingProduct->addData(array(
                'general_id'          => null,
                'is_general_id_owner' => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO,
            ));
            $listingProduct->save();

            $amazonListingProduct->getVariationManager()->switchModeToAnother();

            unset($this->listingsProducts[$key]);
            $listingProduct->delete();
        }

        if (empty($processChildListingsProducts)) {
            return;
        }

        $childListingsProductsIds = array();
        foreach ($processChildListingsProducts as $listingProduct) {
            $childListingsProductsIds[] = $listingProduct->getId();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', array('in' => $childListingsProductsIds));

        $processChildListingsProducts = $listingProductCollection->getItems();
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

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = array();

        foreach ($listingProducts as $id => $childListingProduct) {
            if ((!$childListingProduct->isListed() || !$childListingProduct->isStoppable()) &&
                empty($this->params['remove'])
            ) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    protected function filterLockedListingsProducts()
    {
        parent::filterLockedListingsProducts();

        if (empty($this->params['remove'])) {
            return;
        }

        foreach ($this->listingsProducts as $key => $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            if (!$listingProduct->isSetProcessingLock('child_products_in_action')) {
                continue;
            }

            // M2ePro\TRANSLATIONS
            // Another Action is being processed. Try again when the Action is completed.
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Stop and Remove action is not supported if Child Products are in Action.',
               \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            unset($this->listingsProducts[$key]);
        }
    }

    // ########################################
}