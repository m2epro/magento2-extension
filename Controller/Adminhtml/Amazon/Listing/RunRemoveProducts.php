<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class RunRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
{
    public function execute()
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return 'You should select Products';
        }

        $listingsProductsIds = explode(',', $listingsProductsIds);
        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingProducts */
        $listingProducts = $this->amazonFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id', array('in' => $listingsProductsIds));

        /** @var \Ess\M2ePro\Model\Listing\Log $log */
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $actionId = $log->getNextActionId();

        $locked = 0;
        foreach ($listingProducts as $listingProduct) {

            if ($listingProduct->isSetProcessingLock()) {
                $log->addProductMessage($listingProduct->getListingId(),
                    $listingProduct->getProductId(),
                    $listingProduct->getId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                    $actionId,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_LISTING,
                    // M2ePro_TRANSLATIONS
                    // Product cannot be deleted because it has Status "In Progress".
                    'Product cannot be deleted because it has Status "In Progress".',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM);

                $locked++;
                continue;
            }

            if ($listingProduct->isListed()) {
                $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
            }

            /** @var $amazonListingProduct \Ess\M2ePro\Model\Amazon\Listing\Product */
            $amazonListingProduct = $listingProduct->getChildObject();

            if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {

                $amazonChildListingsProducts = $amazonListingProduct->getVariationManager()
                    ->getTypeModel()
                    ->getChildListingsProducts();
                foreach ($amazonChildListingsProducts as $child) {
                    /** @var $child \Ess\M2ePro\Model\Listing\Product */
                    if ($child->isListed()) {
                        $child->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
                        $child->save();
                    }
                }
            }

            $listingProduct->delete();
        }

        $this->setJsonContent(array(
            'result' => $locked > 0 ? 'error' :'success',
            'action_id' => $actionId
        ));

        return $this->getResult();
    }

}