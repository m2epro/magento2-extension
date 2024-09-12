<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Model\Cron\Task\Walmart\Listing\SynchronizeInventory\ProcessingRunner;

class Unmanaged extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingUnmanaged');
        $this->_controller = 'adminhtml_walmart_listing_unmanaged';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        if ($this->getRequest()->getParam('back') !== null) {
            $url = $this->dataHelper->getBackUrl();
            $this->buttonList->add('back', [
                'label' => __('Back'),
                'onclick' => 'CommonObj.backClick(\'' . $url . '\')',
                'class' => 'back',
            ]);
        }

        $this->addResetBtn();
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
            $label = 'Products Import Is in Progress';
            $disabled = true;
        }

        $url = $this->getUrl('*/walmart_listing_unmanaged/reset');
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
        $componentMode = \Ess\M2ePro\Helper\Component\Walmart::NICK;

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
            'moveToListing' => $this->getUrl('*/walmart_listing_unmanaged/moveToListing'),
            'categorySettings' => $this->getUrl('*/walmart_listing_product_add/index', ['step' => 3]),

            'mapAutoToProduct' => $this->getUrl('*/listing_other_mapping/autoMap'),

            'removingProducts' => $this->getUrl('*/walmart_listing_unmanaged/removing'),
            'unmappingProducts' => $this->getUrl('*/listing_other_mapping/unmapping'),
        ]);

        $someProductsWereNotMappedMessage = __(
            'Some Items were not linked. Please edit <i>Product Linking Settings</i> under
            <i>Configuration > Account > Unmanaged Listings</i> or try to link manually.'
        );

        $createListing = __(
            'Listings, which have the same Marketplace and Account were not found.'
        );
        $createListing .= __('Would you like to create one with Default Settings ?');

        $this->jsTranslator->addTranslations([
            'Link Item "%product_title%" with Magento Product' => __(
                'Link Item "%product_title%" with Magento Product'
            ),
            'Product does not exist.' => __('Product does not exist.'),
            'Product(s) was Linked.' => __('Product(s) was Linked.'),
            'Linking Product' => __('Linking Product'),

            'Current version only supports Simple Products. Please, choose Simple Product.' => __(
                'Current version only supports Simple Products. Please, choose Simple Product.'
            ),

            'Item was not Linked as the chosen %product_id% Simple Product has Custom Options.' => __(
                'Item was not Linked as the chosen %product_id% Simple Product has Custom Options.'
            ),
            'Add New Listing' => __('Add New Listing'),

            'create_listing' => $createListing,
            'popup_title' => __('Moving Walmart Items'),
            'confirm' => __('Are you sure?'),

            'Not enough data' => __('Not enough data'),
            'Product(s) was Unlinked.' => __('Product(s) was Unlinked.'),
            'Product(s) was Removed.' => __('Product(s) was Removed.'),

            'select_items_message' => __('Please select the Products you want to perform the Action on.'),
            'select_action_message' => __('Please select Action.'),

            'automap_progress_title' => __('Link Item(s) to Products'),
            'processing_data_message' => __('Processing %product_title% Product(s).'),
            'Product was Linked.' => __('Product was Linked.'),
            'failed_mapped' => $someProductsWereNotMappedMessage,

            'success_word' => __('Success'),
            'notice_word' => __('Notice'),
            'warning_word' => __('Warning'),
            'error_word' => __('Error'),
            'close_word' => __('Close'),

            'task_completed_message' => __('Task completed. Please wait ...'),
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

                'alog' => 'M2ePro/Walmart/Listing/Other/Grid',
            ],
            <<<JS

        M2ePro.customData.componentMode = '{$componentMode}';
        M2ePro.customData.gridId = 'walmartListingUnmanagedGrid';

        window.WalmartListingOtherGridObj = new WalmartListingOtherGrid('walmartListingUnmanagedGrid');
        window.ListingOtherMappingObj = new ListingMapping(WalmartListingOtherGridObj, 'walmart');

        WalmartListingOtherGridObj.movingHandler.setProgressBar('listing_other_progress_bar');
        WalmartListingOtherGridObj.movingHandler.setGridWrapper('listing_other_content_container');

        WalmartListingOtherGridObj.autoMappingHandler.setProgressBar('listing_other_progress_bar');
        WalmartListingOtherGridObj.autoMappingHandler.setGridWrapper('listing_other_content_container');

        jQuery(function() {
            WalmartListingOtherGridObj.afterInitPage();
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

        $progressBarHtml = '<div id="listing_other_progress_bar"></div>' .
            '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
            '<div id="listing_other_content_container">' .
            parent::_toHtml() .
            '</div>';

        $tabsHtml = $this->getTabsBlockHtml();
        $resetPopupHtml = $this->getResetPopupHtml();

        return $tabsHtml . $progressBarHtml . $resetPopupHtml;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getTabsBlockHtml(): string
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateUnmanagedItemsTab();

        return $tabsBlock->toHtml();
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
}
