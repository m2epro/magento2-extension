<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\WarningPopup
 */
class WarningPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCategoryWarningPopup');

        $this->setTemplate('ebay/listing/product/category/settings/mode/warning_popup.phtml');
    }

    //########################################
}
