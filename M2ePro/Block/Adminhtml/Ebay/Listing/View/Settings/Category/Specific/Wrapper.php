<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Category\Specific;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Category\Specific\Wrapper
 */
class Wrapper extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewSettingsCategorySpecificWrapper');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/view/settings/category/specific/wrapper.phtml');
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $data = [
            'id'      => 'done_button',
            'class'   => 'save done primary',
            'label'   => $this->__('Save'),
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('done', $buttonBlock);
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Ebay_Listing_View_Settings_Category_Breadcrumb');
        $breadcrumb->setSelectedStep(2);

        return $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}
