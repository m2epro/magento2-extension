<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class ResetNewAsin extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $this->getListing()->setSetting('additional_data', 'adding_new_asin_description_templates_data', array());

        $listingProductsIds = $this->getListing()->getSetting('additional_data','adding_new_asin_listing_products_ids');
        $listingProductsIds = $this->getHelper('Component\Amazon\Variation')->filterLockedProducts($listingProductsIds);

        if (!empty($listingProductsIds)) {
            $this->setDescriptionTemplate($listingProductsIds, NULL);

            $this->_forward('unmapFromAsin', 'amazon_listing_product', null, array(
                'products_ids' => $listingProductsIds
            ));
        }

        return $this->_redirect('*/amazon_listing_product_add/index', array(
            '_current' => true,
            'step' => 4
        ));
    }

    //########################################
}