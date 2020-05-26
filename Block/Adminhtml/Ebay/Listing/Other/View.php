<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View
 */
class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
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

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingOtherView');
        $this->_controller = 'adminhtml_ebay_listing_other_view';
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
                'account' => $this->ebayFactory->getCachedObjectLoaded('Account', $accountId),
                'marketplace' => $this->ebayFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ]]
        );
        // ---------------------------------------

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    //########################################

    protected function _toHtml()
    {
        /** @var $helper \Ess\M2ePro\Helper\Data */
        $helper = $this->getHelper('Data');

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $someProductsWereNotMappedMessage = 'No matches were found. Please change the Mapping Attributes in <strong>';
        $someProductsWereNotMappedMessage .= 'Configuration > Account > 3rd Party Listings</strong> ';
        $someProductsWereNotMappedMessage .= 'or try to map manually.';
        $someProductsWereNotMappedMessage = $helper->escapeJs($this->__($someProductsWereNotMappedMessage));

        $this->jsUrl->addUrls($helper->getControllerActions('Listing\Other'));
        $this->jsUrl->addUrls([
            'listing_other_mapping/map' => $this->getUrl('*/listing_other_mapping/map'),
            'mapAutoToProduct' => $this->getUrl('*/listing_other_mapping/autoMap'),

            'prepareData' => $this->getUrl('*/listing_other_moving/prepareMoveToListing'),
            'moveToListingGridHtml' => $this->getUrl('*/listing_other_moving/moveToListingGrid'),
            'moveToListing' => $this->getUrl('*/ebay_listing_other/moveToListing'),
            'categorySettings' => $this->getUrl('*/ebay_listing_product_category_settings/index'),

            'removingProducts' => $this->getUrl('*/ebay_listing_other/removing'),
            'unmappingProducts' => $this->getUrl('*/listing_other_mapping/unmapping')

        ]);

        $this->jsTranslator->addTranslations([
            'Map Item "%product_title%" with Magento Product' => $this->__(
                'Map Item "%product_title%" with Magento Product'
            ),
            'Product does not exist.' => $this->__('Product does not exist.'),
            'Please enter correct Product ID.' => $this->__('Please enter correct Product ID.'),
            'Product(s) was successfully Mapped.' => $this->__('Product(s) was successfully Mapped.'),
            'Please enter correct Product ID or SKU' => $this->__('Please enter correct Product ID or SKU'),
            'Add New Listing' => $this->__('Add New Listing'),
            'failed_mapped' => $someProductsWereNotMappedMessage,
            'successfully_mapped' => $this->__('Product was successfully Mapped.'),
            'mapping_product_title' => $this->__('Mapping Product'),
            'product_does_not_exist' => $this->__('Product does not exist.'),
            'select_simple_product' => $this->__(
                'Current eBay version only supports Simple Products in Mapping. Please, choose Simple Product.'
            ),
            'invalid_data' => $this->__('Please enter correct Product ID.'),
            'enter_product_or_sku' => $this->__('Please enter correct Product ID or SKU'),
            'automap_progress_title' => $this->__('Map Item(s) to Products'),
            'processing_data_message' => $this->__('Processing %product_title% Product(s).'),
            'popup_title' => $this->__('Moving eBay Items'),
            'not_enough_data' => $this->__('Not enough data.'),
            'successfully_unmapped' => $this->__('Product(s) was successfully Unmapped.'),
            'successfully_removed' => $this->__('Product(s) was successfully Removed.'),
            'task_completed_message' => $this->__('Task completed. Please wait ...'),
            'sending_data_message' => $this->__('Sending %product_title% Product(s) data on eBay.'),
            'listing_locked_message' => $this->__('The Listing was locked by another process. Please try again later.'),
            'listing_empty_message' => $this->__('Listing is empty.'),

            'select_items_message' => $this->__('Please select the Products you want to perform the Action on.'),
            'select_action_message' => $this->__('Please select Action.'),
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

            'elog' => 'M2ePro/Ebay/Listing/Other/Grid'
        ], <<<JS

        M2ePro.customData.componentMode = '{$component}';
        M2ePro.customData.gridId = 'ebayListingOtherGrid';

        window.EbayListingOtherGridObj = new EbayListingOtherGrid('ebayListingOtherViewGrid');
        window.EbayListingOtherMappingObj = new ListingOtherMapping(EbayListingOtherGridObj,'ebay');

        EbayListingOtherGridObj.movingHandler.setProgressBar('listing_other_progress_bar');
        EbayListingOtherGridObj.movingHandler.setGridWrapper('listing_other_content_container');

        EbayListingOtherGridObj.autoMappingHandler.setProgressBar('listing_other_progress_bar');
        EbayListingOtherGridObj.autoMappingHandler.setGridWrapper('listing_other_content_container');

        jQuery(function() {
            EbayListingOtherGridObj.afterInitPage();
        });
JS
        );

        $mapToProductBlock = $this->createBlock('Listing_Other_Mapping');

        return  $mapToProductBlock->toHtml() .
                '<div id="listing_other_progress_bar"></div>' .
                '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
                '<div id="listing_other_content_container">' .
                parent::_toHtml() .
                '</div>';
    }

    //########################################
}
