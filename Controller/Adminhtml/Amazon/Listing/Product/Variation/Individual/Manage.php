<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

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

        /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($listingProduct->isComponentModeAmazon()) {
            $isVariationProductMatched = (
                $variationManager->isIndividualType() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            );
        } else {
            $isVariationProductMatched = $variationManager->isVariationProductMatched();
        }

        if ($isVariationProductMatched) {
            $listingProduct = $this->duplicateListingProduct($listingProduct);
        } else {

            $listingProduct->setData('search_settings_status', NULL);
            $listingProduct->setData('search_settings_data', NULL);
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

            if ($listingProduct->isComponentModeAmazon()) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $listingProductManager */
                $listingProductManager = $listingProduct->getChildObject()->getVariationManager();

                if ($listingProductManager->isRelationParentType() && $listingProductManager->modeCanBeSwitched()) {
                    $listingProductManager->switchModeToAnother();
                }
                $individualModel = $listingProductManager->getTypeModel();
            } else {
                $individualModel = $listingProduct->getChildObject()->getVariationManager();
            }
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
            $listingProduct->getProductId(), \Ess\M2ePro\Helper\Data::INITIATOR_USER, false,false
        );

        $variationManager = $listingProduct->getChildObject()->getVariationManager();
        if (!$variationManager->isVariationProduct()) {
            return $duplicatedListingProduct;
        }

        if ($listingProduct->isComponentModeAmazon()) {
            $duplicatedListingProductManager = $duplicatedListingProduct->getChildObject()->getVariationManager();

            if ($variationManager->isIndividualType() && $duplicatedListingProductManager->modeCanBeSwitched()) {
                $duplicatedListingProductManager->switchModeToAnother();
            }
        }

        return $duplicatedListingProduct;
    }

    //########################################
}