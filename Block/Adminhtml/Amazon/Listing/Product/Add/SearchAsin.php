<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class SearchAsin extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

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
        $url = $this->getUrl('*/*/removeAddedProducts', array(
            'id' => $this->listing['id'],
            '_current' => true
        ));
        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'onclick'   => 'ListingGridHandlerObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ));

        // ---------------------------------------
        $this->addButton('auto_action', array(
            'label'     => $this->__('Edit Search Settings'),
            'class'     => 'action-primary next',
            'onclick'   => 'ListingGridHandlerObj.editSearchSettings(\'' .
                $this->__('Listing Search Settings') . '\' ,' .
                $this->listing['id'] .
            ');'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('save_and_go_to_listing_view', array(
            'label'     => $this->__('Continue'),
            'onclick'   => 'ListingGridHandlerObj.checkSearchResults('.$this->listing['id'].')',
            'class'     => 'action-primary forward'
        ));
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('amazon/listing/view.css');

        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
                <p>Since most of the Products already exist in Amazon Catalog, M2E Pro makes it possible
                to find them and to make a link between your Magento Products and existing Amazon Products.</p><br>
                <p>You can use a Manual Search for each added Product by clicking on the icon in the
                "ASIN/ISBN" Column of the Grid. Also you can use Automatic Search for added
                Product(s) by choosing <strong>"Search ASIN/ISBN Automatically"</strong>
                Option in a mass Actions bulk. The Search will be performed according to the values which set in
                Search Settings.</p><br>
                <p>You can always set or change Settings of the source for ASIN/ISBN and UPC/EAN by clicking
                <strong>Edit Search Settings</strong> button in the right top corner.</p><br>
                <p><strong>Note:</strong> The process of Automatic Search might be time-consuming, depending on
                the number of added Products the Search is applied to.</p>
HTML
            )
        ]);

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $viewHeaderBlock = $this->createBlock(
            'Listing\View\Header','', ['data' => ['listing' => $this->listing]]
        );

        $productSearchBlock = $this->createBlock('Amazon\Listing\Product\Search\Main');

        return $viewHeaderBlock->toHtml()
               . $productSearchBlock->toHtml()
               . parent::getGridHtml();
    }

    protected function _toHtml()
    {
        $createEmptyListingMessage = $this->__('Are you sure you want to create empty Listing?');

        $taskCompletedMessage = $this->__('Task completed. Please wait ...');
        $taskCompletedSuccessMessage = $this->__('"%task_title%" Task has successfully submitted to be processed.');
        $taskCompletedWarningMessage = $this->__(
            '"%task_title%" Task has completed with warnings. <a target="_blank" href="%url%">View Log</a> for details.'
        );
        $taskCompletedErrorMessage = $this->__(
            '"%task_title%" Task has completed with errors. <a target="_blank" href="%url%">View Log</a> for details.'
        );

        $sendingDataToAmazonMessage = $this->__('Sending %product_title% Product(s) data on Amazon.');

        $selectItemsMessage = $this->__('Please select the Products you want to perform the Action on.');

        $assignString = $this->__('Assign');
        $textConfirm = $this->__('Are you sure?');

        $enterProductSearchQueryMessage = $this->__('Please enter Product Title or ASIN/ISBN/UPC/EAN.');
        $autoMapAsinSearchProducts = $this->__('Search %product_title% Product(s) on Amazon.');
        $autoMapAsinProgressTitle = $this->__('Automatic Assigning ASIN/ISBN to Item(s)');
        $autoMapAsinErrorMessage = $this->__('Server is currently unavailable. Please try again later.');
        $newAsinNotAvailable = $this->__(
            'The new ASIN/ISBN creation functionality is not available in %code% Marketplace yet.'
        );
        $notSynchronizedMarketplace = $this->__(
            'In order to use New ASIN/ISBN functionality, please re-synchronize Marketplace data.'
        ) .' '. $this->__('Press "Save And Update" Button after redirect on Marketplace Page.');

        $newAsinPopupTitle = $this->__('New ASIN/ISBN creation');
        $notCompletedPopupTitle = $this->__('Adding of New Products to the Listing was not competed');
        $notCompletedPopupText = $this->__('
            The Process of Adding new Products was not ended for this Listing.<br/><br/>
            To work with Products in Listing it is necessary to follow all the Steps of Adding Products.
            You should specify all Required Data to complete
            Adding Process and then press <strong>Continue</strong> Button.<br/><br/>
            In case you do not want to Add selected Products to the Listing,
            you can press <strong>Back</strong> Button and you will be able to manage your Listing.
        ');

        $variationManageMatchedAttributesErrorDuplicateSelection = $this->__(
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
            'Clear Search Results' => $this->__('Clear Search Results')
        ]);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Listing'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Listing\Product'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Listing\Product\Add', [
            'wizard' => $this->getRequest()->getParam('wizard')
        ]));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Listing\Product\Search'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Amazon\Listing\Product\Variation\Vocabulary')
        );

        $this->jsUrl->addUrls([
            'back' => $this->getUrl('*/*/index'),
        ]);

        $this->js->add(<<<JS
    require([
        'M2ePro/Plugin/ProgressBar',
        'M2ePro/Plugin/AreaWrapper'
    ],function() {
        Common.prototype.scrollPageToTop = function() { return; }

        ListingProgressBarObj = new ProgressBar('search_asin_progress_bar');
        GridWrapperObj = new AreaWrapper('search_asin_products_container');
    });
JS
        );

        return
            '<div id="search_asin_progress_bar"></div>' .
                '<div id="search_asin_products_container">' .
                parent::_toHtml() .
            '</div>';
    }

    //########################################
}