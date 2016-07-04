<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Category\Specific;

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
        $data = array(
            'id'      => 'done_button',
            'class'   => 'save done primary',
            'label'   => $this->__('Save'),
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('done', $buttonBlock);
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Ebay\Listing\View\Settings\Category\Breadcrumb');
        $breadcrumb->setSelectedStep(2);

        return $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}