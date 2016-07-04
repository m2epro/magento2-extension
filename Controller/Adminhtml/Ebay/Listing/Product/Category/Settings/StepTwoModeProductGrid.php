<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class StepTwoModeProductGrid extends Settings
{

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_category_settings', $this->getListing());
        // ---------------------------------------

        $this->setAjaxContent($this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Product\Grid'));

        return $this->getResult();
    }

    //########################################
}