<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class Manage extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);
        $listingProduct->getChildObject()->getVariationManager()->getTypeModel()->getProcessor()->process();

        $tabs = $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs');
        $tabs->setListingProduct($listingProduct);

        $this->setAjaxContent($tabs);

        return $this->getResult();
    }
}