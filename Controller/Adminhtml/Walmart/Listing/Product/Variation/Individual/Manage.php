<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Individual\Manage
 */
class Manage extends Main
{
    //########################################

    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');
        $variationsData = $this->getRequest()->getParam('variation_data');

        if (!$listingProductId || !$variationsData) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__(
                    'Listing Product and Variation Data must be specified.'
                )
            ]);

            return $this->getResult();
        }

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        $isVariationProductMatched = (
            $variationManager->isIndividualType() &&
            $variationManager->getTypeModel()->isVariationProductMatched()
        );

        if ($isVariationProductMatched) {
            $listingProduct = $this->duplicateListingProduct($listingProduct);
        } else {
            $listingProduct->setData('search_settings_status', null);
            $listingProduct->setData('search_settings_data', null);
            $listingProduct->save();
        }

        $magentoVariations = $listingProduct->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();
        $magentoVariations = $magentoVariations['variations'];

        $isFirst = true;
        foreach ($variationsData as $variationData) {
            !$isFirst && $listingProduct = $this->duplicateListingProduct($listingProduct);
            $isFirst = false;

            $tempMagentoVariations = $magentoVariations;

            foreach ($tempMagentoVariations as $key => $magentoVariation) {
                foreach ($magentoVariation as $option) {
                    $value = $option['option'];
                    $attribute = $option['attribute'];

                    if ($variationData[$attribute] != $value) {
                        unset($tempMagentoVariations[$key]);
                    }
                }
            }

            if (count($tempMagentoVariations) != 1) {
                $this->setJsonContent([
                    'type' => 'error',
                    'message' => $this->__('Only 1 Variation must leave.')
                ]);

                return $this->getResult();
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $listingProductManager */
            $listingProductManager = $listingProduct->getChildObject()->getVariationManager();

            if ($listingProductManager->isRelationParentType() && $listingProductManager->modeCanBeSwitched()) {
                $listingProductManager->switchModeToAnother();
            }

            $individualModel = $listingProductManager->getTypeModel();
            $individualModel->setProductVariation(reset($tempMagentoVariations));
        }

        $this->setJsonContent([
            'type' => 'success',
            'message' => $this->__('Variation(s) has been successfully saved.')
        ]);

        return $this->getResult();
    }

    //########################################

    private function duplicateListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $duplicatedListingProduct = $listingProduct->getListing()->addProduct(
            $listingProduct->getProductId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            false,
            false
        );

        $duplicatedListingProduct->setData(
            'template_category_id',
            $listingProduct->getChildObject()->getTemplateCategoryId()
        );
        $duplicatedListingProduct->save();

        $variationManager = $listingProduct->getChildObject()->getVariationManager();
        if (!$variationManager->isVariationProduct()) {
            return $duplicatedListingProduct;
        }

        $duplicatedListingProductManager = $duplicatedListingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isIndividualType() && $duplicatedListingProductManager->modeCanBeSwitched()) {
            $duplicatedListingProductManager->switchModeToAnother();
        }

        return $duplicatedListingProduct;
    }

    //########################################
}
