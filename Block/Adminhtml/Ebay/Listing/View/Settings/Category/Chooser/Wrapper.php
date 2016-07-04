<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Category\Chooser;

class Wrapper extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewSettingsCategoryChooserWrapper');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/view/settings/category/chooser/wrapper.phtml');
    }

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Ebay\Listing\View\Settings\Category\Breadcrumb');
        $breadcrumb->setSelectedStep(1);

        return $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}