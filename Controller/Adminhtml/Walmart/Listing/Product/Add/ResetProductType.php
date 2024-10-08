<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class ResetProductType extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        $this->setProductType($listingProductsIds, null);

        $this->getListing()->setSetting('additional_data', 'adding_product_type_data', []);
        $this->getListing()->save();

        return $this->_redirect('*/walmart_listing_product_add/index', [
            '_current' => true,
            'step' => 3,
        ]);
    }
}
