<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetSearchAsinMenu extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('ERROR: No Product ID!', false);

            return $this->getResult();
        }

        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        $productSearchMenuBlock = $this->createBlock('Amazon\Listing\Product\Search\Menu');
        $productSearchMenuBlock->setListingProduct($listingProduct);

        $this->setAjaxContent($productSearchMenuBlock);

        return $this->getResult();
    }
}