<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs;

class Tabs extends AbstractHorizontalTabs
{
    const CHANNEL_ID_ALL        = 'all';
    const CHANNEL_ID_AMAZON     = 'amazon';
    const CHANNEL_ID_BUY        = 'buy';

    // ---------------------------------------

    const TAB_ID_LISTING            = 'listing';
    const TAB_ID_LISTING_OTHER      = 'listing_other';

    //########################################

    protected $logType;

    /**
     * @param string $logType
     */
    public function setLogType($logType)
    {
        $this->logType = $logType;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonLogTabs');
        $this->setDestElementId('tabs_container');
    }

    //########################################

    protected function _beforeToHtml()
    {
        if (!$this->isListingOtherTabShouldBeShown() && $this->getData('active_tab') == self::TAB_ID_LISTING_OTHER) {
            $this->setData('active_tab', self::TAB_ID_LISTING);
        }

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

        if ($this->getData('active_tab') == self::TAB_ID_LISTING) {
            $tab['content'] = $this->createBlock('Amazon\Listing\Log')->toHtml();
        } else {
            $tab['url'] = $this->getUrl('*/amazon_listing_log/index', [
                '_current' => true
            ]);
        }

        return $tab;
    }

    protected function prepareTabListingOther()
    {
        $tab = array(
            'label' => $this->__('3rd Party'),
            'title' => $this->__('3rd Party')
        );

        if (!$this->isListingOtherTabShouldBeShown()) {
            $this->js->add(<<<JS
require(["mage/backend/tabs"], function(){
    jQuery(function() {
        jQuery('#amazonLogTabs').tabs('option', 'disabled', [1]);
    });
});
JS
);
        }

        if ($this->getData('active_tab') == self::TAB_ID_LISTING_OTHER) {
            $tab['content'] = $this->createBlock('Amazon\Listing\Other\Log')->toHtml();
        } else {
            $tab['url'] = $this->getUrl('*/amazon_listing_other_log/index', [
                '_current' => true
            ]);
        }

        return $tab;
    }

    //########################################

    protected function isListingOtherTabShouldBeShown()
    {
        if ($this->getHelper('View\Amazon')->is3rdPartyShouldBeShown()) {
            return true;
        }

        return false;
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('log/grid.css');
        parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $marketplaceFilterBlock = $this->createBlock('Marketplace\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_listing_log'
        ]);
        $marketplaceFilterBlock->setUseConfirm(false);

        $accountFilterBlock = $this->createBlock('Account\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_listing_log'
        ]);
        $accountFilterBlock->setUseConfirm(false);

        $pageActionsHtml = '';
        $marketplaceFilterHtml = $marketplaceFilterBlock->toHtml();
        $accountFilterHtml = $accountFilterBlock->toHtml();
        if (trim($marketplaceFilterHtml) || trim($accountFilterHtml)) {
            $pageActionsHtml = '<div class="page-main-actions"><div class="filter_block">'
                . $marketplaceFilterHtml
                . $accountFilterHtml
                . '</div></div>';
        }

        return $pageActionsHtml . parent::_toHtml(). '<div id="tabs_container"></div>';
    }

    //########################################
}