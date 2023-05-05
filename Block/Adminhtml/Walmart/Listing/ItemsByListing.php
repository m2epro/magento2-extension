<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

class ItemsByListing extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_walmart_listing_itemsByListing';
    }

    protected function _prepareLayout()
    {
        $url = $this->getUrl('*/walmart_listing_create/index', [
            'step' => '1',
            'clear' => 'yes',
        ]);
        $this->addButton('add', [
            'label' => __('Add Listing'),
            'onclick' => 'setLocation(\'' . $url . '\')',
            'class' => 'action-primary',
        ]);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateItemsByListingTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $tabsBlockHtml . parent::_toHtml();
    }
}
