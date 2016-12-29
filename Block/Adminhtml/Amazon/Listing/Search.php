<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAmazonSearch');

        $listingType = $this->getRequest()->getParam('listing_type', false);

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            $this->_controller = 'adminhtml_amazon_listing_search_other';
        } else {
            $this->_controller = 'adminhtml_amazon_listing_search_product';
        }

        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/search/grid.css');
        $this->css->addFile('switcher.css');

        $content = $this->__(
            <<<HTML
            <p>This Search tool contains a list of all the Products present in M2E Pro Listings as
            well as 3rd Party Listings.</p><br>
            <p>This functionality allows you to search for Products based common Item details or Attribute values
            more effectively (Product Title, SKU, Stock Availability, etc.).</p><br>

            <p>However, it does not allow managing the settings configured for the Products.
            If you need to add/edit settings, you should click on the arrow sign in the Manage column of
            a grid. The selected Product will be shown in the Listing where you will be able to manage its
            configurations.</p>
HTML
        );

        $this->appendHelpBlock([
            'content' => $content
        ]);

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $marketplaceSwitcherBlock = $this->createBlock('Amazon\Marketplace\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\View\Amazon::NICK,
            'controller_name' => $this->getRequest()->getControllerName()
        ]);

        $accountSwitcherBlock = $this->createBlock('Amazon\Account\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\View\Amazon::NICK,
            'controller_name' => $this->getRequest()->getControllerName()
        ]);

        $listingTypeSwitcherBlock = $this->createBlock('Listing\Search\TypeSwitcher')->setData([
            'controller_name' => $this->getRequest()->getControllerName()
        ]);

        $filterBlockHtml = <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$listingTypeSwitcherBlock->toHtml()}
        {$accountSwitcherBlock->toHtml()}
        {$marketplaceSwitcherBlock->toHtml()}
    </div>
</div>
HTML;

        return $filterBlockHtml . parent::_toHtml();
    }

    //########################################
}