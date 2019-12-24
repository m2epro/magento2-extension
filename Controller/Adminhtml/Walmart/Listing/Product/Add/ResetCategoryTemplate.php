<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\ResetCategoryTemplate
 */
class ResetCategoryTemplate extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        $this->setCategoryTemplate($listingProductsIds, null);

        $this->getListing()->setSetting('additional_data', 'adding_category_templates_data', []);
        $this->getListing()->save();

        return $this->_redirect('*/walmart_listing_product_add/index', [
            '_current' => true,
            'step' => 3
        ]);
    }

    //########################################
}
