<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser\Tabs\Browse
 */
class Browse extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('EbayListingProductCategorySettingsChooserTabsBrowse');
        // ---------------------------------------

        // Set template
        // ---------------------------------------
        $this->setTemplate('ebay/listing/product/category/settings/chooser/tabs/browse.phtml');
        // ---------------------------------------
    }

    public function isWizardActive()
    {
        return $this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK);
    }

    //########################################
}
