<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class DuplicateProducts extends Main
{
    public function execute()
    {
        $listingProductsIds = $this->getRequest()->getParam('ids');
        $listingProductsIds = explode(',',$listingProductsIds);
        $listingProductsIds = array_filter($listingProductsIds);

        if (empty($listingProductsIds)) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Listing Products must be specified.')
            ]);

            return $this->getResult();
        }

        foreach ($listingProductsIds as $listingProductId) {

            /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

            $duplicatedListingProduct = $listingProduct->getListing()->addProduct(
                $listingProduct->getProductId(), \Ess\M2ePro\Helper\Data::INITIATOR_USER, false, false
            );

            $variationManager = $listingProduct->getChildObject()->getVariationManager();
            if (!$variationManager->isVariationProduct()) {
                continue;
            }

            $duplicatedListingProductManager = $duplicatedListingProduct->getChildObject()->getVariationManager();

            if ($variationManager->isIndividualType() && $duplicatedListingProductManager->modeCanBeSwitched()) {
                $duplicatedListingProductManager->switchModeToAnother();
            }
        }

        $this->setJsonContent([
            'type' => 'success',
            'message' => $this->__('The Items were successfully duplicated.')
        ]);

        return $this->getResult();
    }
}