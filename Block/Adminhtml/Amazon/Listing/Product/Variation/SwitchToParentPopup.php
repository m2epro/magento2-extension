<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\SwitchToParentPopup
 */
class SwitchToParentPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingAddNewAsinManualPopup');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/variation/switch_to_parent_popup.phtml');
    }

    //########################################
}
