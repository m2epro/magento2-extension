<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType;

class WarningPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingAddProductTypeWarningPopup');
        // ---------------------------------------

        $this->setTemplate('walmart/listing/product/add/product_type/warning_popup.phtml');
    }
}
