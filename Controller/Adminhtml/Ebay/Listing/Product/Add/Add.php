<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

use Ess\M2ePro\Model\Listing;

class Add extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listing = $this->getListing();

        $productsIds = $this->getRequest()->getParam('products');
        $productsIds = explode(',', $productsIds);
        $productsIds = array_unique($productsIds);
        $productsIds = array_filter($productsIds);

        $ids = array();
        foreach ($productsIds as $productId) {
            $listingProduct = $listing->addProduct($productId);
            if ($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product) {
                $ids[] = $listingProduct->getId();
            }
        }

        // ---------------------------------------
        $existingIds = $listing->getChildObject()->getAddedListingProductsIds();
        $existingIds = array_values(array_unique(array_merge($existingIds,$ids)));
        $listing->getChildObject()->setData('product_add_ids', json_encode($existingIds))->save();
        // ---------------------------------------

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }

    //########################################
}