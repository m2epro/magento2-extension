<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other\View
 */
class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListing');
        $this->_controller = 'adminhtml_amazon_listing_other_view';
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

        // ---------------------------------------

        if ($this->getRequest()->getParam('back') !== null) {
            $url = $this->getHelper('Data')->getBackUrl();
            $this->buttonList->add('back', [
                'label'   => $this->__('Back'),
                'onclick' => 'CommonObj.backClick(\'' . $url . '\')',
                'class'   => 'back'
            ]);
        }
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
                <p>The list below shows the Unmanaged Listings imported from a particular Account and Marketplace.
                It contains the functionality of manual and automatic Item Linking and Moving. After the imported
                Items are Linked to Magento Products, they can be Moved into an M2E Pro
                Listing for further management.</p><br>

                <p>The list is automatically updated if the import option is enabled in the Account settings.</p>
HTML
            )
        ]);

        return parent::_prepareLayout();
    }

    //########################################

    public function getGridHtml()
    {
        $accountId = $this->getRequest()->getParam('account');
        $marketplaceId = $this->getRequest()->getParam('marketplace');

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock(
            'Listing_Other_View_Header',
            '',
            ['data' => [
                'account' => $this->amazonFactory->getCachedObjectLoaded('Account', $accountId),
                'marketplace' => $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ]]
        );
        // ---------------------------------------

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    //########################################

    protected function _toHtml()
    {
        $componentMode = \Ess\M2ePro\Helper\Component\Amazon::NICK;

        $this->jsUrl->addUrls([
            'mapProductPopupHtml' => $this->getUrl(
                '*/listing_other_mapping/mapProductPopupHtml',
                [
                    'account_id'     => $this->getRequest()->getParam('account'),
                    'marketplace_id' => $this->getRequest()->getParam('marketplace')
                ]
            ),
            'listing_other_mapping/map' => $this->getUrl('*/listing_other_mapping/map'),

            'prepareData' => $this->getUrl('*/listing_other_moving/prepareMoveToListing'),
            'moveToListingGridHtml' => $this->getUrl('*/listing_other_moving/moveToListingGrid'),
            'moveToListing' => $this->getUrl('*/amazon_listing_other/moveToListing'),

            'mapAutoToProduct' => $this->getUrl('*/listing_other_mapping/autoMap'),

            'removingProducts' => $this->getUrl('*/amazon_listing_other/removing'),
            'unmappingProducts' => $this->getUrl('*/listing_other_mapping/unmapping'),

            'amazon_listing/getAFNQtyBySku' => $this->getUrl('*/amazon_listing/getAFNQtyBySku')
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

            'task_completed_message' => $this->__('Task completed. Please wait ...')
        ]);

        $this->js->addRequireJs([
            'jQuery' => 'jquery',

            'p' => 'M2ePro/Plugin/ProgressBar',
            'a' => 'M2ePro/Plugin/AreaWrapper',
            'lm' => 'M2ePro/Listing/Moving',
            'lom' => 'M2ePro/Listing/Mapping',
            'loa' => 'M2ePro/Listing/Other/AutoMapping',
            'lor' => 'M2ePro/Listing/Other/Removing',
            'lou' => 'M2ePro/Listing/Other/Unmapping',

            'alog' => 'M2ePro/Amazon/Listing/Other/Grid',
            'aloa' => 'M2ePro/Amazon/Listing/AfnQty',
            'alprp' => 'M2ePro/Amazon/Listing/Product/Repricing/Price'
        ], <<<JS

        M2ePro.customData.componentMode = '{$componentMode}';
        M2ePro.customData.gridId = 'amazonListingOtherGrid';

        window.AmazonListingOtherGridObj = new AmazonListingOtherGrid('amazonListingOtherGrid');
        window.ListingOtherMappingObj = new ListingMapping(AmazonListingOtherGridObj, 'amazon');

        AmazonListingOtherGridObj.movingHandler.setProgressBar('listing_other_progress_bar');
        AmazonListingOtherGridObj.movingHandler.setGridWrapper('listing_other_content_container');

        AmazonListingOtherGridObj.autoMappingHandler.setProgressBar('listing_other_progress_bar');
        AmazonListingOtherGridObj.autoMappingHandler.setGridWrapper('listing_other_content_container');

        window.AmazonListingAfnQtyObj = new AmazonListingAfnQty();
        window.AmazonListingProductRepricingPriceObj = new AmazonListingProductRepricingPrice();

        jQuery(function() {
            AmazonListingOtherGridObj.afterInitPage();
        });
JS
        );

        return '<div id="listing_other_progress_bar"></div>' .
               '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
               '<div id="listing_other_content_container">' .
               parent::_toHtml() .
               '</div>';
    }

    //########################################
}
