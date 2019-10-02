<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class ResetNewAsin
 * @package Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
 */
class ResetNewAsin extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $this->getListing()->setSetting('additional_data', 'adding_new_asin_description_templates_data', []);

        $listingProductsIds = $this->getListing()
            ->getSetting('additional_data', 'adding_new_asin_listing_products_ids');
        $listingProductsIds = $this->getHelper('Component_Amazon_Variation')->filterLockedProducts($listingProductsIds);

        if (!empty($listingProductsIds)) {
            $this->setDescriptionTemplate($listingProductsIds, null);

            $this->_forward('unmapFromAsin', 'amazon_listing_product', null, [
                'products_ids' => $listingProductsIds
            ]);
        }

        return $this->_redirect('*/amazon_listing_product_add/index', [
            '_current' => true,
            'step' => 4
        ]);
    }

    //########################################
}
