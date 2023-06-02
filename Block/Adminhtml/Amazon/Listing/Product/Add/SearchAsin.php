<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class SearchAsin extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('searchAsinForListingProducts');
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_controller = 'adminhtml_amazon_listing_product_add_searchAsin';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/removeAddedProducts', [
            'id' => $this->listing['id'],
            '_current' => true,
        ]);
        $this->addButton('back', [
            'label' => __('Back'),
            'onclick' => 'ListingGridObj.backClick(\'' . $url . '\')',
            'class' => 'back',
        ]);

        // ---------------------------------------
        $url = $this->getUrl(
            '*/amazon_listing_product_add/exitToListing',
            ['id' => $this->getRequest()->getParam('id')]
        );
        $confirm =
            '<strong>' . __('Are you sure?') . '</strong><br><br>'
            . __('All unsaved changes will be lost and you will be returned to the Listings grid.');
        $this->addButton(
            'exit_to_listing',
            [
                'label' => __('Cancel'),
                'onclick' => "confirmSetLocation('$confirm', '$url');",
                'class' => 'action-primary',
            ]
        );

        $this->addButton('add_products_search_asin_continue', [
            'label' => __('Continue'),
            'onclick' => 'ListingGridObj.checkSearchResults(' . $this->listing['id'] . ')',
            'class' => 'action-primary forward',
        ]);
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('amazon/listing/view.css');

        $this->appendHelpBlock([
            'content' => __(
                <<<HTML
                <p>Since most of the Products already exist in Amazon Catalog, M2E Pro makes it possible
                to find them and to make a link between your Magento Products and existing Amazon Products.</p><br>
                <p>You can use a Manual Search for each added Product by clicking on the icon in the
                "ASIN/ISBN" Column of the Grid. Also you can use Automatic Search for added
                Product(s) by choosing <strong>"Search ASIN/ISBN Automatically"</strong>
                Option in a mass Actions bulk. The Search will be performed based on the Product Identifiers Settings"
                </p>
                <br>
                <p>You can always set or change Settings of the source for ASIN/ISBN and UPC/EAN</p>
                <br>
                <p><strong>Note:</strong> The process of Automatic Search might be time-consuming, depending on
                the number of added Products the Search is applied to.</p>
HTML
            ),
        ]);

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $this->listing]]
        );

        $productSearchBlock = $this->getLayout()
                                   ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search\Main::class);

        return $viewHeaderBlock->toHtml()
            . $productSearchBlock->toHtml()
            . parent::getGridHtml();
    }

    protected function _toHtml()
    {
        $createEmptyListingMessage = __('Are you sure you want to create empty Listing?');

        $taskCompletedMessage = __('Task completed. Please wait ...');
        $taskCompletedSuccessMessage = $this->__('"%task_title%" Task has submitted to be processed.');
        $taskCompletedWarningMessage = $this->__(
            '"%task_title%" Task has completed with warnings. <a target="_blank" href="%url%">View Log</a> for details.'
        );
        $taskCompletedErrorMessage = $this->__(
            '"%task_title%" Task has completed with errors. <a target="_blank" href="%url%">View Log</a> for details.'
        );

        $sendingDataToAmazonMessage = __('Sending %product_title% Product(s) data on Amazon.');

        $selectItemsMessage = __('Please select the Products you want to perform the Action on.');

        $assignString = __('Assign');
        $textConfirm = __('Are you sure?');

        $enterProductSearchQueryMessage = __('Please enter Product Title or ASIN/ISBN/UPC/EAN.');
        $autoMapAsinSearchProducts = __('Search %product_title% Product(s) on Amazon.');
        $autoMapAsinProgressTitle = __('Automatic Assigning ASIN/ISBN to Item(s)');
        $autoMapAsinErrorMessage = __('Server is currently unavailable. Please try again later.');
        $newAsinNotAvailable = $this->__(
            'The new ASIN/ISBN creation functionality is not available in %code% Marketplace yet.'
        );
        $notSynchronizedMarketplace = __(
            'In order to use New ASIN/ISBN functionality, please re-synchronize Marketplace data.'
        ) . ' ' . __('Press "Save And Update" Button after redirect on Marketplace Page.');

        $newAsinPopupTitle = __('New ASIN/ISBN creation');
        $notCompletedPopupTitle = __('Adding of New Products to the Listing was not competed');
        $notCompletedPopupText = __(
            "
            You didn't finish adding Products to the Listing.<br/><br/>
            To add selected Products to the Listing, you need to specify the required information first.
            Once you're done, click <strong>Continue</strong>.<br/><br/>
            If you don't want to add selected Products to the Listing, click <strong>Back</strong> to return
            to the previous step. Or <strong>Cancel</strong> the adding process to return to the Listing.
        "
        );

        $variationManageMatchedAttributesErrorDuplicateSelection = __(
            'You can not choose the same Attribute twice.'
        );

        $this->jsTranslator->addTranslations([
            'select_items_message' => $selectItemsMessage,
            'create_empty_listing_message' => $createEmptyListingMessage,

            'sending_data_message' => $sendingDataToAmazonMessage,

            'new_asin_not_available' => $newAsinNotAvailable,
            'not_synchronized_marketplace' => $notSynchronizedMarketplace,

            'enter_productSearch_query' => $enterProductSearchQueryMessage,
            'automap_asin_search_products' => $autoMapAsinSearchProducts,
            'automap_asin_progress_title' => $autoMapAsinProgressTitle,
            'automap_error_message' => $autoMapAsinErrorMessage,

            'task_completed_message' => $taskCompletedMessage,
            'task_completed_success_message' => $taskCompletedSuccessMessage,
            'task_completed_warning_message' => $taskCompletedWarningMessage,
            'task_completed_error_message' => $taskCompletedErrorMessage,

            'assign' => $assignString,
            'confirm' => $textConfirm,

            'new_asin_popup_title' => $newAsinPopupTitle,
            'not_completed_popup_title' => $notCompletedPopupTitle,
            'not_completed_popup_text' => $notCompletedPopupText,

            'variation_manage_matched_attributes_error_duplicate' =>
                $variationManageMatchedAttributesErrorDuplicateSelection,
            'Clear Search Results' => __('Clear Search Results'),
        ]);

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Listing'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Add', [
                'wizard' => $this->getRequest()->getParam('wizard'),
            ])
        );
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product_Search'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Variation_Vocabulary')
        );

        $this->jsUrl->addUrls([
            'back' => $this->getUrl('*/*/index'),
        ]);

        $this->js->add(
            <<<JS
    require([],function() {
        Common.prototype.scrollPageToTop = function() { return; }
    });
JS
        );

        return
            '<div id="search_asin_progress_bar"></div>' .
            '<div id="search_asin_products_container">' .
            parent::_toHtml() .
            '</div>';
    }
}
