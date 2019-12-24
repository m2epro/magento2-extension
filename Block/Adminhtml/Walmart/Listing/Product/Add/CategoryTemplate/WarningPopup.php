<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate\WarningPopup
 */
class WarningPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingAddCategoryTemplateWarningPopup');
        // ---------------------------------------

        $this->setTemplate('walmart/listing/product/add/category_template/warning_popup.phtml');
    }

    //########################################
}
