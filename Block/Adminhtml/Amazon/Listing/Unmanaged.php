<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory\ProcessingRunner;

class Unmanaged extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingUnmanaged');
        $this->_controller = 'adminhtml_amazon_listing_unmanaged';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->addResetBtn();

        $this->isAjax = \Ess\M2ePro\Helper\Json::encode($this->getRequest()->isXmlHttpRequest());
    }

    private function addResetBtn(): void
    {
        $label = 'Reset Unmanaged Listings';
        $disabled = false;

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject(
            'Lock_Item_Manager',
            [
                'nick' => ProcessingRunner::LOCK_ITEM_PREFIX,
            ]
        );

        if ($lockItemManager->isExist()) {
            $label = 'Products import is in progress';
            $disabled = true;
        }

        $url = $this->getUrl('*/amazon_listing_unmanaged/reset');
        $this->addButton(
            'reset_unmanaged_listings',
            [
                'label' => __($label),
                'onclick' => "ListingOtherObj.showResetPopup('{$url}');",
                'class' => 'action-primary',
                'disabled' => $disabled,
            ]
        );
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('switcher.css');
        $this->setPageActionsBlock(Unmanaged\PageActions::BLOCK_PATH);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $componentMode = \Ess\M2ePro\Helper\Component\Amazon::NICK;

        $this->jsUrl->addUrls([
            'mapProductPopupHtml' => $this->getUrl(
                '*/listing_other_mapping/mapProductPopupHtml',
                [
                    'account_id' => $this->getRequest()->getParam('account'),
                    'marketplace_id' => $this->getRequest()->getParam('marketplace'),
                ]
            ),
            'listing_other_mapping/map' => $this->getUrl('*/listing_other_mapping/map'),

            'prepareData' => $this->getUrl('*/listing_other_moving/prepareMoveToListing'),
            'moveToListingGridHtml' => $this->getUrl('*/listing_other_moving/moveToListingGrid'),
            'moveToListing' => $this->getUrl('*/amazon_listing_unmanaged/moveToListing'),

            'mapAutoToProduct' => $this->getUrl('*/listing_other_mapping/autoMap'),

            'removingProducts' => $this->getUrl('*/amazon_listing_unmanaged/removing'),
            'unmappingProducts' => $this->getUrl('*/listing_other_mapping/unmapping'),
        ]);

        $this->jsUrl->add($this->getUrl('*/amazon_listing_product_repricing/getUpdatedPriceBySkus'));

        $someProductsWereNotMappedMessage = $this->__(
            'Some Items were not linked. Please edit <i>Product Linking Settings</i> under
            <i>Configuration > Account > Unmanaged Listings</i> or try to link manually.'
        );

        $createListing = $this->__(
            'Listings, which have the same Marketplace and Account were not found.'
        );
        $createListing .= $this->__('Would you like to create one with Default Settings ?');

        $this->jsTranslator->addTranslations([
            'Link Item "%product_title%" with Magento Product' => $this->__(
                'Link Item "%product_title%" with Magento Product'
            ),
            'Product does not exist.' => $this->__('Product does not exist.'),
            'Product(s) was Linked.' => $this->__('Product(s) was Linked.'),
            'Linking Product' => $this->__('Linking Product'),

            'Current version only supports Simple Products. Please, choose Simple Product.' => $this->__(
                'Current version only supports Simple Products. Please, choose Simple Product.'
            ),

            'Item was not Linked as the chosen %product_id% Simple Product has Custom Options.' => $this->__(
                'Item was not Linked as the chosen %product_id% Simple Product has Custom Options.'
            ),
            'Add New Listing' => $this->__('Add New Listing'),

            'create_listing' => $createListing,
            'popup_title' => $this->__('Moving Amazon Items'),
            'confirm' => $this->__('Are you sure?'),

            'Not enough data' => $this->__('Not enough data'),
            'Product(s) was Unlinked.' => $this->__('Product(s) was Unlinked.'),
            'Product(s) was Removed.' => $this->__('Product(s) was Removed.'),

            'select_items_message' => $this->__('Please select the Products you want to perform the Action on.'),
            'select_action_message' => $this->__('Please select Action.'),

            'automap_progress_title' => $this->__('Link Item(s) to Products'),
            'processing_data_message' => $this->__('Processing %product_title% Product(s).'),
            'Product was Linked.' => $this->__('Product was Linked.'),
            'failed_mapped' => $someProductsWereNotMappedMessage,

            'success_word' => $this->__('Success'),
            'notice_word' => $this->__('Notice'),
            'warning_word' => $this->__('Warning'),
            'error_word' => $this->__('Error'),
            'close_word' => $this->__('Close'),

            'task_completed_message' => $this->__('Task completed. Please wait ...'),
        ]);

        $this->js->addRequireJs(
            [
                'jQuery' => 'jquery',

                'p' => 'M2ePro/Plugin/ProgressBar',
                'a' => 'M2ePro/Plugin/AreaWrapper',
                'lm' => 'M2ePro/Listing/Moving',
                'lom' => 'M2ePro/Listing/Mapping',
                'loa' => 'M2ePro/Listing/Other/AutoMapping',
                'lor' => 'M2ePro/Listing/Other/Removing',
                'lou' => 'M2ePro/Listing/Other/Unmapping',

                'alog' => 'M2ePro/Amazon/Listing/Other/Grid',
                'alprp' => 'M2ePro/Amazon/Listing/Product/Repricing/Price',
            ],
            <<<JS

        M2ePro.customData.componentMode = '{$componentMode}';
        M2ePro.customData.gridId = 'amazonListingUnmanagedGrid';

        window.AmazonListingOtherGridObj = new AmazonListingOtherGrid('amazonListingUnmanagedGrid');
        window.ListingOtherMappingObj = new ListingMapping(AmazonListingOtherGridObj, 'amazon');

        AmazonListingOtherGridObj.movingHandler.setProgressBar('listing_other_progress_bar');
        AmazonListingOtherGridObj.movingHandler.setGridWrapper('listing_other_content_container');

        AmazonListingOtherGridObj.autoMappingHandler.setProgressBar('listing_other_progress_bar');
        AmazonListingOtherGridObj.autoMappingHandler.setGridWrapper('listing_other_content_container');

        window.AmazonListingProductRepricingPriceObj = new AmazonListingProductRepricingPrice();

        jQuery(function() {
            AmazonListingOtherGridObj.afterInitPage();
        });
JS
        );

        $this->js->add(
            <<<JS
    require(['M2ePro/Listing/Other'], function(){

        window.ListingOtherObj = new ListingOther();

    });
JS
        );

        $tabsHtml = $this->getTabsBlockHtml();

        $progressBarHtml =  '<div id="listing_other_progress_bar"></div>' .
            '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
            '<div id="listing_other_content_container">' .
            parent::_toHtml() .
            '</div>';

        return $tabsHtml . $progressBarHtml . $this->getResetPopupHtml();
    }

    private function getResetPopupHtml(): string
    {
        return <<<HTML
<div style="display: none">
    <div id="reset_other_listings_popup_content" class="block_notices m2epro-box-style"
     style="display: none; margin-bottom: 0;">
        <div>
            <h3>{$this->__('Confirm the Unmanaged Listings reset')}</h3>
            <p>{$this->__(
            'This action will remove all the items from Amazon Unmanaged Listings.
             It will take some time to import them again.'
        )}</p>
             <br>
            <p>{$this->__('Do you want to reset the Unmanaged Listings?')}</p>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getTabsBlockHtml(): string
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs::class);
        $tabsBlock->activateUnmanagedItemsTab();

        return $tabsBlock->toHtml();
    }
}
