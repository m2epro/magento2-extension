<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other
 */
class Other extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory */
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingOther');
        $this->_controller = 'adminhtml_ebay_listing_other';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $label = 'Reset Unmanaged Listings';
        $disabled = false;

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accounts */
        $accounts = $this->ebayFactory->getObject('Account')->getCollection();
        $accounts->addFieldToFilter('other_listings_synchronization', 1);
        foreach ($accounts->getItems() as $account) {

            /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
            $lockItemManager = $this->modelFactory->getObject(
                'Lock_Item_Manager',
                [
                    'nick' => SynchronizeData::LOCK_ITEM_PREFIX . '_' . $account->getId()
                ]
            );

            if ($lockItemManager->isExist()) {
                $label = 'Products import is in progress';
                $disabled = true;
                break;
            }
        }

        $url = $this->getUrl('*/ebay_listing_other/reset');
        $this->addButton(
            'reset_other_listings',
            [
                'label'    => $this->__($label),
                'onclick'  => "ListingOtherObj.showResetPopup('{$url}');",
                'class'    => 'action-primary',
                'disabled' => $disabled
            ]
        );
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock(
            [
                'content' => $this->__(
                    <<<HTML
                <p>The list below displays groups of Items combined together based on their belonging to a
                specific Marketplace and Account. The number of the Unmanaged Listings available for each of
                the groups is also available.</p><br>

                <p>Unmanaged Listings are the Items which were placed directly on the Channel or by using a tool
                other than M2E Pro. These Items are imported according to Account settings which means the settings
                can be managed for different Accounts separately.</p><br>

                <p>Information in this section can be used to see which Items have not been fully managed via M2E Pro
                yet. It allows linking the imported Channel Products to the Magento Products and further moving
                them into M2E Pro Listings.</p>

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
            'This action will remove all the items from eBay Unmanaged Listings.
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
