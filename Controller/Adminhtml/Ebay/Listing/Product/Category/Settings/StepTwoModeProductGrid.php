<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class StepTwoModeProductGrid
 * @package Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings
 */
class StepTwoModeProductGrid extends Settings
{

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_category_settings', $this->getListing());
        // ---------------------------------------

        $this->setAjaxContent($this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_Product_Grid'));

        return $this->getResult();
    }

    //########################################
}
