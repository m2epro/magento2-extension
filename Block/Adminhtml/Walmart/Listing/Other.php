<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Model\Cron\Task\Walmart\Listing\SynchronizeInventory\ProcessingRunner;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Other
 */
class Other extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingOther');
        $this->_controller = 'adminhtml_walmart_listing_other';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $label = 'Reset Unmanaged Listings';
        $disabled = false;

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject(
            'Lock_Item_Manager',
            [
                'nick' => ProcessingRunner::LOCK_ITEM_PREFIX
            ]
        );

        if ($lockItemManager->isExist()) {
            $label = 'Products import is in progress';
            $disabled = true;
        }

        $url = $this->getUrl('*/walmart_listing_other/reset');
        $this->addButton(
            'reset_other_listings',
            [
                'label'    => $this->__($label),
                'onclick'  => "ListingOtherObj.showResetPopup('{$url}');",
                'class'    => 'action-primary',
                'disabled' => $disabled
            ]
        );

        $this->isAjax = $this->getHelper('Data')->jsonEncode($this->getRequest()->isXmlHttpRequest());
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock(
            [
                'content' => $this->__(
                    <<<HTML
    On this page, you can review the Unmanaged Listings imported by M2E Pro from your Channel Account
    associated with particular Marketplace. In the grid below,
    click the Unmanaged Listing line to manage the Items.<br><br>

    <strong>Note:</strong> To import the Unmanaged Listings, enable the related option in your Account
    Configuration under <i>Walmart Integration > Configuration > Accounts > Edit Account > Unmanaged Listings</i>.
HTML
                )
            ]
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $this->js->add(
            <<<JS
    require(['M2ePro/Listing/Other'], function(){

        window.ListingOtherObj = new ListingOther();

    });
JS
        );

        return parent::_toHtml() . $this->getResetPopupHtml();
    }

    protected function getResetPopupHtml()
    {
        return <<<HTML
<div style="display: none">
    <div id="reset_other_listings_popup_content" class="block_notices m2epro-box-style"
     style="display: none; margin-bottom: 0;">
        <div>
            <h3>{$this->__('Confirm the Unmanaged Listings reset')}</h3>
            <p>{$this->__(
            'This action will remove all the items from Walmart Unmanaged Listings.
             It will take some time to import them again.'
        )}</p>
             <br>
            <p>{$this->__('Do you want to reset the Unmanaged Listings?')}</p>
        </div>
    </div>
</div>
HTML;
    }

    //########################################
}
