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

        $accountId = $this->getRequest()->getParam('account');
        $marketplaceId = $this->getRequest()->getParam('marketplace');

        $this->addButton('view_logs', [
            'label'   => $this->__('View Log'),
            'onclick' => 'window.open(\''.$this->getUrl('*/amazon_log_listing_other/index', [
                'amazonAccount' => $accountId,
                'amazonMarketplace' => $marketplaceId,
                'listings' => true
            ]) . '\');',
        ]);

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
                <p>The list below shows the 3rd Party Listings imported from a particular Account and Marketplace.
                It contains the functionality of manual and automatic Item Mapping and Moving. After the imported
                Items are Mapped to Magento Products, they can be Moved into an M2E Pro
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

        $mapToProductBlock = $this->createBlock(
            'Listing_Other_Mapping'
        );

        return $viewHeaderBlock->toHtml() . $mapToProductBlock->toHtml() . parent::getGridHtml();
    }

    //########################################

    protected function _toHtml()
    {
        $componentMode = \Ess\M2ePro\Helper\Component\Amazon::NICK;

        $this->jsUrl->addUrls([
            'amazon_log_listing_other/index' => $this->getUrl('*/amazon_log_listing_other/index'),

            'listing_other_mapping/map' => $this->getUrl('*/listing_other_mapping/map'),

            'prepareData' => $this->getUrl('*/listing_other_moving/prepareMoveToListing'),
            'createDefaultListing' => $this->getUrl('*/listing_other_moving/createDefaultListing'),
            'moveToListingGridHtml' => $this->getUrl('*/listing_other_moving/moveToListingGrid'),
            'getFailedProductsHtml' => $this->getUrl('*/listing_other_moving/getFailedProducts'),
            'tryToMoveToListing' => $this->getUrl('*/listing_other_moving/tryToMoveToListing'),
            'moveToListing' => $this->getUrl('*/listing_other_moving/moveToListing'),

            'mapAutoToProduct' => $this->getUrl('*/listing_other_mapping/autoMap'),

            'removingProducts' => $this->getUrl('*/amazon_listing_other/removing'),
            'unmappingProducts' => $this->getUrl('*/listing_other_mapping/unmapping'),

            'amazon_listing/getAFNQtyBySku' => $this->getUrl('*/amazon_listing/getAFNQtyBySku')
        ]);

        $this->jsUrl->add($this->getUrl('*/amazon_listing_product_repricing/getUpdatedPriceBySkus'));

        $someProductsWereNotMappedMessage = 'No matches were found. Please change the Mapping Attributes in <strong>';
        $someProductsWereNotMappedMessage .= 'Configuration > Account > 3rd Party Listings</strong> ';
        $someProductsWereNotMappedMessage .= 'or try to map manually.';
        $someProductsWereNotMappedMessage = $this->__($someProductsWereNotMappedMessage);

        $createListing = $this->__(
            'Listings, which have the same Marketplace and Account were not found.'
        );
        $createListing .= $this->__('Would you like to create one with Default Settings ?');

        $this->jsTranslator->addTranslations([
            'Map Item "%product_title%" with Magento Product' => $this->__(
                'Map Item "%product_title%" with Magento Product'
            ),
            'Product does not exist.' => $this->__('Product does not exist.'),
            'Please enter correct Product ID.' => $this->__('Please enter correct Product ID.'),
            'Product(s) was successfully Mapped.' => $this->__('Product(s) was successfully Mapped.'),
            'Please enter correct Product ID or SKU' => $this->__('Please enter correct Product ID or SKU'),

            'Current version only supports Simple Products. Please, choose Simple Product.' => $this->__(
                'Current version only supports Simple Products. Please, choose Simple Product.'
            ),

            'Item was not Mapped as the chosen %product_id% Simple Product has Custom Options.' => $this->__(
                'Item was not Mapped as the chosen %product_id% Simple Product has Custom Options.'
            ),
            'Add New Listing' => $this->__('Add New Listing'),

            'create_listing' => $createListing,
            'popup_title' => $this->__('Moving Amazon Items'),
            'popup_title_single' => $this->__('Move Item "%product_title%" to the M2E Pro Listing'),
            'failed_products_popup_title' => $this->__('Products failed to move'),
            'confirm' => $this->__('Are you sure?'),
            'successfully_moved' => $this->__('Product(s) was successfully Moved.'),
            'products_were_not_moved' => $this->__(
                'Products were not Moved. <a target="_blank" href="%url%">View Log</a> for details.'
            ),
            'some_products_were_not_moved' => $this->__(
                'Some of the Products were not Moved. <a target="_blank" href="%url%">View Log</a> for details.'
            ),
            'not_enough_data' => $this->__('Not enough data'),
            'successfully_unmapped' => $this->__('Product(s) was successfully Unmapped.'),
            'successfully_removed' => $this->__('Product(s) was successfully Removed.'),

            'select_items_message' => $this->__('Please select the Products you want to perform the Action on.'),
            'select_action_message' => $this->__('Please select Action.'),

            'automap_progress_title' => $this->__('Map Item(s) to Products'),
            'processing_data_message' => $this->__('Processing %product_title% Product(s).'),
            'successfully_mapped' => $this->__('Product was successfully Mapped.'),
            'failed_mapped' => $someProductsWereNotMappedMessage,

            'select_only_mapped_products' => $this->__('Only Mapped Products must be selected.'),
            'select_the_same_type_products' => $this->__(
                'Selected Items must belong to the same Account and Marketplace.'
            ),

            'view_all_product_log_message' => $this->__('View Full Product Log.'),

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
            'lom' => 'M2ePro/Listing/Other/Mapping',
            'loa' => 'M2ePro/Listing/Other/AutoMapping',
            'lor' => 'M2ePro/Listing/Other/Removing',
            'lou' => 'M2ePro/Listing/Other/Unmapping',

            'alog' => 'M2ePro/Amazon/Listing/Other/Grid',
            'aloa' => 'M2ePro/Amazon/Listing/AfnQty',
            'alprp' => 'M2ePro/Amazon/Listing/Product/Repricing/Price'
        ], <<<JS

        M2ePro.customData.componentMode = '{$componentMode}';
        M2ePro.customData.gridId = 'amazonListingOtherGrid';

        window.ListingProgressBarObj = new ProgressBar('listing_other_progress_bar');
        window.GridWrapperObj = new AreaWrapper('listing_other_content_container');

        window.AmazonListingOtherGridObj    = new AmazonListingOtherGrid('amazonListingOtherGrid');
        window.AmazonListingOtherMappingObj = new ListingOtherMapping(AmazonListingOtherGridObj, 'amazon');

        AmazonListingOtherGridObj.movingHandler.setOptions(M2ePro);
        AmazonListingOtherGridObj.autoMappingHandler.setOptions(M2ePro);
        AmazonListingOtherGridObj.removingHandler.setOptions(M2ePro);
        AmazonListingOtherGridObj.unmappingHandler.setOptions(M2ePro);

        window.AmazonListingAfnQtyObj = new AmazonListingAfnQty();
        window.AmazonListingProductRepricingPriceObj = new AmazonListingProductRepricingPrice();

        jQuery(function() {
            AmazonListingOtherGridObj.afterInitPage();
        });
JS
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid::class
        ));

        return '<div id="listing_other_progress_bar"></div>' .
               '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
               '<div id="listing_other_content_container">' .
               parent::_toHtml() .
               '</div>';
    }

    //########################################
}
