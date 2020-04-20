<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;
use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\UnmapFromAsin
 */
class UnmapFromAsin extends Main
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        if (empty($productsIds)) {
            /** @var \Ess\M2ePro\Model\Listing $listing */
            $listing = $this->amazonFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('listing_id')
            );
            $productsIds = $listing->getSetting('additional_data', 'adding_new_asin_listing_products_ids');
            $productsIds = $this->getHelper('Component_Amazon_Variation')->filterLockedProducts($productsIds);
        }

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $message = $this->__('ASIN(s)/ISBN(s) was successfully unassigned.');
        $type = 'success';

        foreach ($productsIds as $productId) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$listingProduct->isNotListed() ||
                $listingProduct->isSetProcessingLock('in_action') ||
                ($amazonListingProduct->getVariationManager()->isVariationParent() &&
                    $listingProduct->isSetProcessingLock('child_products_in_action'))) {
                $type = 'error';
                $message = $this->__(
                    'ASIN/ISBN or marker “New ASIN/ISBN” was not unassigned from some Items because those Items
                     have the Status different from “Not Listed” or they are now in the process of Listing.'
                );
                continue;
            }

            $runListingProductProcessor = false;
            if ($amazonListingProduct->getVariationManager()->isLogicalUnit()) {
                /** @var ParentRelation $parentType */
                $parentType = $listingProduct->getChildObject()->getVariationManager()->getTypeModel();

                $parentType->setMatchedAttributes([], false);
                $parentType->setChannelAttributesSets([], false);
                $parentType->setChannelVariations([], false);
                $parentType->setVirtualProductAttributes([], false);
                $parentType->setVirtualChannelAttributes([], false);

                $runListingProductProcessor = true;
            }

            $amazonListingProduct->setData('general_id', null);
            $amazonListingProduct->setData('general_id_search_info', null);
            $amazonListingProduct->setData(
                'is_general_id_owner',
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO
            );
            $amazonListingProduct->setData('search_settings_status', null);
            $amazonListingProduct->setData('search_settings_data', null);

            $amazonListingProduct->save();

            if ($runListingProductProcessor) {
                $parentType->getProcessor()->process();
            }
        }

        $this->setJsonContent([
            'type'    => $type,
            'message' => $message
        ]);

        return $this->getResult();
    }
}
