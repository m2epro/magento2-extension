<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs;
use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class ItemsByListing extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingItemsByListing');
        $this->_controller = 'adminhtml_amazon_listing_itemsByListing';
    }

    protected function _prepareLayout()
    {
        $url = $this->getUrl('*/amazon_listing_create/index', ['step' => 1, 'clear' => 1]);
        $this->addButton('add', [
            'label' => __('Add Listing'),
            'onclick' => 'setLocation(\'' . $url . '\')',
            'class' => 'action-primary',
            'button_class' => '',
        ]);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateItemsByListingTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $tabsBlockHtml . parent::_toHtml();
    }
}
