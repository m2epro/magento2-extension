<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View;

use \Ess\M2ePro\Block\Adminhtml\Listing\View\Switcher as AbstractSwitcher;

class Switcher extends AbstractSwitcher
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewModeSwitcher');
        // ---------------------------------------

        $this->setData('component_nick', 'ebay');
        $this->setData('component_label', 'eBay');
    }

    protected function getMenuItems()
    {
        $data = array(
            array(
                'value' => $this->getComponentNick(),
                'label' => $this->__($this->getComponentLabel())
            ),
            array(
                'value' => 'settings',
                'label' => $this->__('Settings')
            ),
            array(
                'value' => 'magento',
                'label' => $this->__('Magento')
            )
        );

        return $data;
    }

    //########################################

    protected function _toHtml()
    {
        return parent::_toHtml() . $this->getTooltipHtml(
            $this->__(<<<HTML
            <p>There are several <strong>View Modes</strong> available to you:</p>
            <ul>
            <li><p><strong>eBay</strong> - displays Product details with respect to eBay Item information. 
            Using this Mode, you can easily filter down the list of Products based on eBay Item details as 
            well as perform Actions to eBay Items in bulk (i.e. List, Revise, Relist, Stop, etc);</p></li>
            <li><p><strong>Settings</strong> - displays information about the Settings set for the Products 
            (i.e. Selling Settings, eBay Categories, etc). Using this Mode, you can easily find Products by
             reference to the Settings they use as well as edit already defined Settings in bulk.</p></li>
            <li><p><strong>Magento</strong> - displays Products information with regard to Magento Catalog.
            Using this Mode, you can easily find Products based on Magento Product information 
            (i.e. Magento QTY, Stock Status, etc);</p></li>
            </ul>
            <p>More detailed information you can find 
            <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/mAItAQ')
            )
        );
    }

    //########################################
}