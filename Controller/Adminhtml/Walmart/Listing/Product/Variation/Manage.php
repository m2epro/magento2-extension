<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class Manage extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $productId);
        $listingProduct->getChildObject()->getVariationManager()->getTypeModel()->getProcessor()->process();

        $tabs = $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs::class);
        $tabs->setListingProduct($listingProduct);

        $this->setAjaxContent($tabs);

        return $this->getResult();
    }
}
