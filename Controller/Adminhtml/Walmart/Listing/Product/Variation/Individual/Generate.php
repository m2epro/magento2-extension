<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Individual\Generate
 */
class Generate extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');

        if (!$listingProductId) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__(
                    'Listing Product must be specified.'
                )
            ]);

            return $this->getResult();
        }

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $magentoVariations = $listingProduct->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();
        $magentoVariations = $magentoVariations['variations'];

        if (!$this->getRequest()->getParam('unique', false)) {
            $this->setJsonContent([
                'type' => 'success',
                'text' => $magentoVariations
            ]);

            return $this->getResult();
        }

        $listingProducts = $this->walmartFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('listing_id', $listingProduct->getListingId())
            ->addFieldToFilter('product_id', $listingProduct->getProductId())
            ->getItems();

        foreach ($listingProducts as $listingProduct) {
            $variationManager = $listingProduct->getChildObject()->getVariationManager();

            if ($listingProduct->isComponentModeWalmart()) {
                if (!($variationManager->isIndividualType() &&
                    $variationManager->getTypeModel()->isVariationProductMatched())) {
                    continue;
                }
            } else {
                if (!$variationManager->isVariationProductMatched()) {
                    continue;
                }
            }

            $variations = $listingProduct->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $listingProduct->getId()
                    ]
                );
            }

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            $options = $variation->getOptions();
            foreach ($options as &$option) {
                $option = [
                    'product_id' => $option['product_id'],
                    'product_type' => $option['product_type'],
                    'attribute' => $option['attribute'],
                    'option' => $option['option']
                ];
            }
            unset($option);

            foreach ($magentoVariations as $key => $variation) {
                if ($variation != $options) {
                    continue;
                }
                unset($magentoVariations[$key]);
            }
        }

        $this->setJsonContent([
            'type' => 'success',
            'text' => array_values($magentoVariations)
        ]);

        return $this->getResult();
    }
}
