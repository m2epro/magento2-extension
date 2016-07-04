<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
    private $listingsProducts = array();

    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Amazon\Processing\Action $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Amazon\Processing\Action');
        $processingAction->setData(array(
            'processing_id' => $this->getProcessingObject()->getId(),
            'account_id'    => $params['account_id'],
            'type'          => $this->getProcessingActionType(),
        ));

        $processingAction->save();

        foreach ($params['request_data']['items'] as $listingProductId => $productData) {
            /** @var \Ess\M2ePro\Model\Amazon\Processing\Action\Item $processingActionItem */
            $processingActionItem = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item');
            $processingActionItem->setData(array(
                'action_id'  => $processingAction->getId(),
                'related_id' => $listingProductId,
                'input_data' => json_encode($productData),
            ));

            $processingActionItem->save();
        }
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $alreadyLockedListings = array();
        $alreadyLockedParents  = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $listingProduct->addProcessingLock(NULL, $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock('in_action', $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock(
                $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
            );

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $variationManager = $amazonListingProduct->getVariationManager();

            if ($variationManager->isRelationChildType()) {
                $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
                if (isset($alreadyLockedParents[$parentListingProduct->getId()])) {
                    continue;
                }

                $parentListingProduct->addProcessingLock(NULL, $this->getProcessingObject()->getId());
                $parentListingProduct->addProcessingLock(
                    'child_products_in_action', $this->getProcessingObject()->getId()
                );

                $alreadyLockedParents[$parentListingProduct->getId()] = true;
            }

            if (isset($alreadyLockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());

            $alreadyLockedListings[$listingProduct->getListingId()] = true;
        }
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        $alreadyUnlockedListings = array();
        $alreadyUnlockedParents  = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            $listingProduct->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks(
                $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
            );

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $variationManager = $amazonListingProduct->getVariationManager();

            if ($variationManager->isRelationChildType()) {
                $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
                if (isset($alreadyUnlockedParents[$parentListingProduct->getId()])) {
                    continue;
                }

                $parentListingProduct->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
                $parentListingProduct->deleteProcessingLocks(
                    'child_products_in_action', $this->getProcessingObject()->getId()
                );

                $alreadyUnlockedParents[$parentListingProduct->getId()] = true;
            }

            if (isset($alreadyUnlockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());

            $alreadyUnlockedListings[$listingProduct->getListingId()] = true;
        }
    }

    // ########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_PRODUCT_ADD;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_PRODUCT_UPDATE;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_PRODUCT_DELETE;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type.');
        }
    }

    protected function getListingsProducts()
    {
        if (!empty($this->listingsProducts)) {
            return $this->listingsProducts;
        }

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product'
        )->getCollection();
        $collection->addFieldToFilter('id', array('in' => $params['listing_product_ids']));

        return $this->listingsProducts = $collection->getItems();
    }

    // ########################################
}