<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class VariationReset extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');

        if (!$listingProductId) {
            return $this->getResponse()->setBody(json_encode(array(
                'type' => 'error',
                'message' => $this->__(
                    'For changing the Mode of working with Magento Variational Product
                     you have to choose the Specific Product.'
                )
            )));
        }

        /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $listingProduct->getChildObject()->setData('search_settings_status', NULL);
        $listingProduct->getChildObject()->setData('search_settings_data', NULL);
        $listingProduct->save();

        $listingProductManager = $listingProduct->getChildObject()->getVariationManager();
        if ($listingProductManager->isIndividualType() && $listingProductManager->modeCanBeSwitched()) {
            $listingProductManager->switchModeToAnother();
        }

        $listingProductManager->getTypeModel()->getProcessor()->process();

        $this->setJsonContent([
            'type' => 'success',
            'message' => $this->__(
                'Mode of working with Magento Variational Product has been switched to work with Parent-Child Product.'
            )
        ]);

        return $this->getResult();
    }
}