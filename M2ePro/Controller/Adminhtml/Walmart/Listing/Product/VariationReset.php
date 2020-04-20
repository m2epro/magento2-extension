<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\VariationReset
 */
class VariationReset extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');

        if (!$listingProductId) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__(
                    'For changing the Mode of working with Magento Variational Product
                     you have to choose the Specific Product.'
                )
            ]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $listingProduct->getChildObject()->setData('search_settings_status', null);
        $listingProduct->getChildObject()->setData('search_settings_data', null);
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
