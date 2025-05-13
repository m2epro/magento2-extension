<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData;

class Unmanaged extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\Lock\Item\ManagerFactory */
    private $lockItemManagerFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockItemManagerFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->lockItemManagerFactory = $lockItemManagerFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingUnmanaged');
        $this->_controller = 'adminhtml_ebay_listing_unmanaged';
        // ---------------------------------------

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->addResetBtn();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function addResetBtn(): void
    {
        $importIsInProgress = false;

        $collection = $this->accountCollectionFactory->createWithEbayChildMode();
        $accounts = $collection->getItemsWithEnablesOtherListingsSynch();
        foreach ($accounts as $account) {
            $lockItemManager = $this->lockItemManagerFactory->create(
                SynchronizeData::LOCK_ITEM_PREFIX . '_' . $account->getId()
            );

            if ($lockItemManager->isExist()) {
                $importIsInProgress = true;
                break;
            }
        }

        $label = $importIsInProgress
            ? __('Products Import Is in Progress')
            : __('Reset Unmanaged Listings');
        $url = $this->getUrl('*/ebay_listing_unmanaged/reset');

        $this->addButton(
            'reset_other_listings',
            [
                'label' => $label,
                'onclick' => "ListingOtherObj.showResetPopup('{$url}');",
                'class' => 'action-primary',
                'disabled' => $importIsInProgress,
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
        $someProductsWereNotMappedMessage = __(
            'Some Items were not linked. Please edit <i>Product Linking Settings</i> under
            <i>Configuration > Account > Unmanaged Listings</i> or try to link manually.'
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Listing\Other'));
        $this->jsUrl->addUrls([
            'mapProductPopupHtml' => $this->getUrl(
                '*/listing_other_mapping/mapProductPopupHtml',
                [
                    'account_id' => $this->getRequest()->getParam('account'),
                    'marketplace_id' => $this->getRequest()->getParam('marketplace'),
                ]
            ),
            'listing_other_mapping/map' => $this->getUrl('*/listing_other_mapping/map'),
            'mapAutoToProduct' => $this->getUrl('*/listing_other_mapping/autoMap'),
            'ebay_listing/view' => $this->getUrl('*/ebay_listing/view'),

            'prepareData' => $this->getUrl('*/listing_other_moving/prepareMoveToListing'),
            'moveToListingGridHtml' => $this->getUrl('*/listing_other_moving/moveToListingGrid'),
            'moveToListing' => $this->getUrl('*/ebay_listing_wizard/createUnmanaged'),
            'categorySettings' => $this->getUrl('*/ebay_listing_wizard/index'),
            'removingProducts' => $this->getUrl('*/ebay_listing_unmanaged/removing'),
            'unmappingProducts' => $this->getUrl('*/listing_other_mapping/unmapping'),
            'createProductAndMap' => $this->getUrl('*/ebay_listing_unmanaged/createProductAndMap'),

        ]);

        $this->jsTranslator->addTranslations([
            'Link Item "%product_title%" with Magento Product' => $this->__(
                'Link Item "%product_title%" with Magento Product'
            ),
            'Product does not exist.' => $this->__('Product does not exist.'),
            'Product(s) was Linked.' => $this->__('Product(s) was Linked.'),
            'Add New Listing' => $this->__('Add New Listing'),
            'failed_mapped' => $someProductsWereNotMappedMessage,
            'Product was Linked.' => $this->__('Product was Linked.'),
            'Linking Product' => $this->__('Linking Product'),
            'product_does_not_exist' => $this->__('Product does not exist.'),
            'select_simple_product' => $this->__(
                'Current eBay version only supports Simple Products in Linking. Please, choose Simple Product.'
            ),
            'automap_progress_title' => $this->__('Link Item(s) to Products'),
            'processing_data_message' => $this->__('Processing %product_title% Product(s).'),
            'popup_title' => $this->__('Moving eBay Items'),
            'Not enough data' => $this->__('Not enough data.'),
            'Product(s) was Unlinked.' => $this->__('Product(s) was Unlinked.'),
            'Product(s) was Removed.' => $this->__('Product(s) was Removed.'),
            'task_completed_message' => $this->__('Task completed. Please wait ...'),
            'sending_data_message' => $this->__('Sending %product_title% Product(s) data on eBay.'),
            'listing_locked_message' => $this->__('The Listing was locked by another process. Please try again later.'),
            'listing_empty_message' => $this->__('Listing is empty.'),

            'select_items_message' => $this->__('Please select the Products you want to perform the Action on.'),
            'select_action_message' => $this->__('Please select Action.'),
        ]);

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;
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
                'lopcl' => 'M2ePro/Listing/Other/CreateProduct',

                'elog' => 'M2ePro/Ebay/Listing/Other/Grid',
                'ebayMoving' => 'M2ePro/Ebay/Listing/Moving'
            ],
            <<<JS

        M2ePro.customData.componentMode = '{$component}';
        M2ePro.customData.gridId = 'ebayListingOtherGrid';

        window.EbayListingOtherGridObj = new EbayListingOtherGrid('ebayListingUnmanagedGrid');
        window.ListingOtherMappingObj = new ListingMapping(EbayListingOtherGridObj,'ebay');
        window.ListingOtherCreateProductObj = new ListingOtherCreateProduct(EbayListingOtherGridObj,'ebay');

        EbayListingOtherGridObj.movingHandler.setProgressBar('listing_other_progress_bar');
        EbayListingOtherGridObj.movingHandler.setGridWrapper('listing_other_content_container');

        EbayListingOtherGridObj.autoMappingHandler.setProgressBar('listing_other_progress_bar');
        EbayListingOtherGridObj.autoMappingHandler.setGridWrapper('listing_other_content_container');

        EbayListingOtherGridObj.createProductHandler.setProgressBar('listing_other_progress_bar');
        EbayListingOtherGridObj.createProductHandler.setGridWrapper('listing_other_content_container');

        jQuery(function() {
            EbayListingOtherGridObj.afterInitPage();
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
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateUnmanagedItemsTab();
        return $tabsBlock->toHtml();
    }

    /**
     * @return string
     */
    private function getResetPopupHtml(): string
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
}
