<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    const TAB_ID_RECENT      = 'recent';
    const TAB_ID_BROWSE      = 'browse';
    const TAB_ID_SEARCH      = 'search';
    const TAB_ID_ATTRIBUTE   = 'attribute';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('ebayListingCategoryChooserTabs');
        $this->setDestElementId('chooser_tabs_container');
    }

    //########################################

    protected function _prepareLayout()
    {
        $hideRecent = $this->getHelper('Data\GlobalData')->getValue('category_chooser_hide_recent');

        !$hideRecent && $this->addTab(self::TAB_ID_RECENT, [
            'label'   => $this->__('Recently Used'),
            'title'   => $this->__('Recently Used'),
            'content' => $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Tabs_Recent')->toHtml(),
            'active'  => true
        ]);
        $this->addTab(self::TAB_ID_BROWSE, [
            'label'   => $this->__('Browse'),
            'title'   => $this->__('Browse'),
            'content' => $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Tabs_Browse')
                              ->toHtml(),
            'active'  => $hideRecent ? true : false
        ]);
        $this->addTab(self::TAB_ID_SEARCH, [
            'label'   => $this->__('Search'),
            'title'   => $this->__('Search'),
            'content' => $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Tabs_Search')
                              ->toHtml()
        ]);
        $this->addTab(self::TAB_ID_ATTRIBUTE, [
            'label'   => $this->__('Magento Attribute'),
            'title'   => $this->__('Magento Attribute'),
            'content' => $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Tabs_Attribute')
                              ->toHtml()
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}
