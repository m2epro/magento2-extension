<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoModeProductGrid
 */
class StepTwoModeProductGrid extends Settings
{
    //########################################

    public function execute()
    {
        $categoriesData = $this->getSessionValue($this->getSessionDataKey());
        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_Product_Grid');
        $block->setCategoriesData($categoriesData);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}
