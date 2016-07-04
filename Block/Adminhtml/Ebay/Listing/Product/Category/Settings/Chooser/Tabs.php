<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser;

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

        !$hideRecent && $this->addTab(self::TAB_ID_RECENT, array(
            'label'   => $this->__('Recently Used'),
            'title'   => $this->__('Recently Used'),
            'content' => $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser\Tabs\Recent')->toHtml(),
            'active'  => true
        ));
        $this->addTab(self::TAB_ID_BROWSE, array(
            'label'   => $this->__('Browse'),
            'title'   => $this->__('Browse'),
            'content' => $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser\Tabs\Browse')
                              ->toHtml(),
            'active'  => $hideRecent ? true : false
        ));
        $this->addTab(self::TAB_ID_SEARCH, array(
            'label'   => $this->__('Search'),
            'title'   => $this->__('Search'),
            'content' => $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser\Tabs\Search')
                              ->toHtml()
        ));
        $this->addTab(self::TAB_ID_ATTRIBUTE, array(
            'label'   => $this->__('Magento Attribute'),
            'title'   => $this->__('Magento Attribute'),
            'content' => $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser\Tabs\Attribute')
                              ->toHtml()
        ));

        return parent::_prepareLayout();
    }

    //########################################
}