<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Individual\Edit
 */
class Edit extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');
        $variationData = $this->getRequest()->getParam('variation_data');

        if (!$listingProductId || !$variationData) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__(
                    'Listing Product and Variation Data must be specified.'
                )
            ]);

            return $this->getResult();
        }

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $magentoVariations = $listingProduct->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();
        $magentoVariations = $magentoVariations['variations'];
        foreach ($magentoVariations as $key => $magentoVariation) {
            foreach ($magentoVariation as $option) {
                $value = $option['option'];
                $attribute = $option['attribute'];

                if ($variationData[$attribute] != $value) {
                    unset($magentoVariations[$key]);
                }
            }
        }

        if (count($magentoVariations) != 1) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Only 1 Variation must leave.')
            ]);

            return $this->getResult();
        }

        if ($listingProduct->isComponentModeAmazon()) {
            $individualModel = $listingProduct->getChildObject()->getVariationManager()->getTypeModel();
        } else {
            $individualModel = $listingProduct->getChildObject()->getVariationManager();
        }
        $individualModel->setProductVariation(reset($magentoVariations));

        $this->setJsonContent([
            'type' => 'success',
            'message' => $this->__('Variation has been successfully edited.')
        ]);

        return $this->getResult();
    }
}
