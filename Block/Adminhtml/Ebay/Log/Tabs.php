<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs;

class Tabs extends AbstractHorizontalTabs
{
    const TAB_ID_LISTING            = 'listing';
    const TAB_ID_LISTING_OTHER      = 'listing_other';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('ebayLogTabs');
        $this->setDestElementId('tabs_container');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab(self::TAB_ID_LISTING, $this->prepareTabListing());
        $this->addTab(self::TAB_ID_LISTING_OTHER, $this->prepareTabListingOther());

        $this->setActiveTab($this->getData('active_tab'));

        return parent::_beforeToHtml();
    }

    //########################################

    protected function prepareTabListing()
    {
        $tab = array(
            'label' => $this->__('M2E Pro'),
            'title' => $this->__('M2E Pro')
        );

        if (!$this->getHelper('View\Ebay')->is3rdPartyShouldBeShown()) {
            $this->js->add(<<<JS
require(["mage/backend/tabs"], function(){
    jQuery(function() {
        jQuery('#ebayLogTabs').tabs('option', 'disabled', [1]);
    });
});
JS
            );
        }

        if ($this->getData('active_tab') == self::TAB_ID_LISTING) {
            $tab['content'] = $this->createBlock('Ebay\Listing\Log')->toHtml();
        } else {
            $tab['url'] = $this->getUrl('*/ebay_listing_log/index', array('_current' => true));
        }

        return $tab;
    }

    protected function prepareTabListingOther()
    {
        $tab = array(
            'label' => $this->__('3rd Party'),
            'title' => $this->__('3rd Party')
        );

        if ($this->getData('active_tab') == self::TAB_ID_LISTING_OTHER) {
            $tab['content'] = $this->createBlock('Ebay\Listing\Other\Log')->toHtml();
        } else {
            $tab['url'] = $this->getUrl('*/ebay_listing_other_log/index', array('_current' => true));
        }

        return $tab;
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('log/grid.css');

        parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $marketplaceFilterBlock = $this->createBlock('Marketplace\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller_name' => 'ebay_listing_log'
        ]);
        $marketplaceFilterBlock->setUseConfirm(false);

        $accountFilterBlock = $this->createBlock('Account\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller_name' => 'ebay_listing_log'
        ]);
        $accountFilterBlock->setUseConfirm(false);

        $pageActionsHtml = '';
        $marketplaceFilterHtml = $marketplaceFilterBlock->toHtml();
        $accountFilterHtml = $accountFilterBlock->toHtml();
        if (trim($marketplaceFilterHtml) || trim($accountFilterHtml)) {
            $pageActionsHtml = '<div class="page-main-actions"><div class="filter_block">'
                . $marketplaceFilterBlock->toHtml()
                . $accountFilterBlock->toHtml()
                . '</div></div>';
        }

        return $pageActionsHtml . parent::_toHtml() . '<div id="tabs_container"></div>';
    }

    //########################################
}