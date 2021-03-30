<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Other\Product\Grid
 */
class OtherCategoriesGrid extends Settings
{
    //########################################

    public function execute()
    {
        $categoriesData = $this->getSessionValue($this->getSessionDataKey());
        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Other_Product_Grid');
        $block->setCategoriesData($categoriesData);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}
