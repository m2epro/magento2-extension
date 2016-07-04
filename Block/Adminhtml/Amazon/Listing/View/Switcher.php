<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View;

use \Ess\M2ePro\Block\Adminhtml\Listing\View\Switcher as AbstractSwitcher;

class Switcher extends AbstractSwitcher
{
    protected $amazonactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $amazonactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    )
    {
        $this->amazonactory = $amazonactory;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingViewModeSwitcher');
        // ---------------------------------------

        $this->setData('component_nick', 'amazon');
        $this->setData('component_label', 'Amazon');
    }

    protected function getMenuItems()
    {
        return array(
            array(
                'value' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View::VIEW_MODE_AMAZON,
                'label' => $this->__($this->getComponentLabel())
            ),
            array(
                'value' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View::VIEW_MODE_SETTINGS,
                'label' => $this->__('Settings')
            ),
            array(
                'value' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View::VIEW_MODE_SELLERCENTRAL,
                'label' => $this->__('Seller Сentral')
            ),
            array(
                'value' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View::VIEW_MODE_MAGENTO,
                'label' => $this->__('Magento')
            )
        );
    }

    //########################################

    protected function _toHtml()
    {
        return parent::_toHtml() . $this->getTooltipHtml(
            $this->__(<<<HTML
            <p>There are several <strong>View Modes</strong> available to you:</p>
            <ul>
            <li><p><strong>Amazon</strong> - displays Product details with respect to Amazon Item
            information. Using this Mode, you can easily filter down the list of Products based on 
            Amazon Item details as well as perform Actions to Amazon Products in bulk 
            (i.e. List, Revise, Relist, Stop, etc);</p></li>
            <li><p><strong>Settings</strong> - displays information about the Settings set for the Products 
            (i.e. Selling Settings, eBay Categories, etc). Using this Mode, you can easily find Products by 
            reference to the Settings they use as well as edit already defined Settings in bulk.</p></li>
            <li><p><strong>Seller Central</strong> - displays Products the way they are shown in Amazon Seller Central
            (each Product is shown individually). Using this Mode, you can also run actions to update products on
            the channel (i.e. List, Revise, etc.) or switch them to AFN/MFN.</p></li>
            <li><p><strong>Magento</strong> - displays Products information with regard to Magento Catalog.
            Using this Mode, you can easily find Products based on Magento Product information 
            (i.e. Magento QTY, Stock Status, etc);</p></li>
            </ul>
            <p>More detailed information you can find <a href="%url%" target="_blank">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/nAYtAQ')
            )
        );
    }

    //########################################
}