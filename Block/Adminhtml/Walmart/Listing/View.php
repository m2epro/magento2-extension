<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View
 */
class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Switcher $viewModeSwitcher */
        $viewModeSwitcher = $this->createBlock('Walmart_Listing_View_Switcher');

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingView');
        $this->_controller = 'adminhtml_walmart_listing_view_' . $viewModeSwitcher->getSelectedParam();
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');
        $this->css->addFile('walmart/listing/view.css');
        $this->css->addFile('walmart/listing/product/variation/grid.css');

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->appendHelpBlock([
                'content' => $this->__(
                    '<p>M2E Pro Listing is a group of Magento Products sold on a certain Marketplace from a
                    particular Account. M2E Pro has several options to display the content of Listings
                    referring to different data details. Each of the view options contains a unique set of
                    available Actions accessible in the Mass Actions drop-down.</p>'
                )
            ]);

            $this->setPageActionsBlock(
                'Walmart_Listing_View_Switcher',
                'walmart_listing_view_switcher'
            );
        }

        // ---------------------------------------
        $this->addButton('back', [
            'label'   => $this->__('Back'),
            'onclick' => 'setLocation(\''.$this->getUrl('*/walmart_listing/index') . '\');',
            'class'   => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('view_logs', [
            'label'   => $this->__('View Log'),
            'onclick' => 'window.open(\''.$this->getUrl('*/walmart_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId()
            ]) . '\');',
            'class'   => '',
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('edit_settings', [
            'label'   => $this->__('Edit Settings'),
            'onclick' => '',
            'class'   => 'drop_down edit_default_settings_drop_down primary',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->getSettingsButtonDropDownItems()
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add_products', [
            'id'        => 'add_products',
            'label'     => $this->__('Add Products'),
            'class'     => 'add',
            'button_class' => '',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->getAddProductsDropDownItems(),
        ]);
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        return '<div id="listing_view_progress_bar"></div>' .
            '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
            '<div id="listing_view_content_container">' .
            parent::_toHtml() .
            '</div>';
    }

    //########################################

    public function getGridHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::getGridHtml();
        }

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::class
        ));

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Account::class)
        );

        $showAutoAction = $this->getHelper('Data')->jsonEncode((bool)$this->getRequest()->getParam('auto_actions'));

        // ---------------------------------------
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Walmart_Listing_AutoAction',
            ['id' => $this->getRequest()->getParam('id')]
        ));

        $path = 'walmart_listing_autoAction/getCategoryTemplatesList';
        $this->jsUrl->add($this->getUrl('*/' . $path, [
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'is_new_asin_accepted' => 1
        ]), $path);

        $path = 'walmart_log_listing_product/index';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

//        $path = 'walmart_listing/duplicateProducts';
//        $this->jsUrl->add($this->getUrl('*/' . $path), $path);
//
//        $path = 'walmart_listing/getEditSkuPopup';
//        $this->jsUrl->add($this->getUrl('*/' . $path), $path);
//
//        $path = 'walmart_listing/editSku';
//        $this->jsUrl->add($this->getUrl('*/' . $path), $path);
//
//        $path = 'walmart_listing/getEditIdentifiersPopup';
//        $this->jsUrl->add($this->getUrl('*/' . $path), $path);
//
//        $path = 'walmart_listing/editIdentifier';
//        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $this->jsUrl->add($this->getUrl('*/walmart_log_listing_product/index', [
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD => $this->listing['id'],
        ]), 'logViewUrl');

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Listing'));

        $this->jsUrl->addUrls([
            'runListProducts' => $this->getUrl('*/walmart_listing/runListProducts'),
            'runRelistProducts' => $this->getUrl('*/walmart_listing/runRelistProducts'),
            'runReviseProducts' => $this->getUrl('*/walmart_listing/runReviseProducts'),
            'runStopProducts' => $this->getUrl('*/walmart_listing/runStopProducts'),
            'runStopAndRemoveProducts' => $this->getUrl('*/walmart_listing/runStopAndRemoveProducts'),
            'runDeleteAndRemoveProducts' => $this->getUrl('*/walmart_listing/runDeleteAndRemoveProducts'),
            'runResetProducts' => $this->getUrl('*/walmart_listing/runResetProducts'),
        ]);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Template_Category')
        );
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Variation'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Variation_Manage')
        );
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Variation_Vocabulary')
        );
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Variation_Individual')
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/walmart_listing_view_settings_moving/moveToListingGrid',
                ['listing_view' => true]
            ),
            'moveToListingGridHtml'
        );
        $this->jsUrl->add($this->getUrl('*/listing_moving/prepareMoveToListing'), 'prepareData');
        $this->jsUrl->add($this->getUrl('*/listing_moving/getFailedProducts'), 'getFailedProductsHtml');
        $this->jsUrl->add($this->getUrl('*/listing_moving/tryToMoveToListing'), 'tryToMoveToListing');
        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListing'), 'moveToListing');

        $this->jsUrl->add($this->getUrl('*/walmart_marketplace/index'), 'marketplaceSynchUrl');

        $this->jsUrl->add($this->getUrl('*/listing/saveListingAdditionalData', [
            'id' => $this->listing['id']
        ]), 'saveListingAdditionalData');

        // ---------------------------------------

        $component = \Ess\M2ePro\Helper\Component\Walmart::NICK;
        $gridId = $this->getChildBlock('grid')->getId();
        $ignoreListings = $this->getHelper('Data')->jsonEncode([$this->listing['id']]);
        $marketplace = $this->getHelper('Data')->jsonEncode(array_merge(
            $this->listing->getMarketplace()->getData(),
            $this->listing->getMarketplace()->getChildObject()->getData()
        ));

        $temp = $this->getHelper('Data\Session')->getValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $templateCategoryPopupTitle = $this->__('Assign Category Policy');

        $popupTitle = $this->__('Moving Walmart Items');
        $popupTitleSingle = $this->__('Moving Walmart Item');
        $failedProductsPopupTitle = $this->__('Products failed to move');

        $taskCompletedMessage = $this->__('Task completed. Please wait ...');
        $taskCompletedSuccessMessage = $this->__('"%task_title%" Task has successfully submitted to be processed.');
        $taskCompletedWarningMessage = $this->__(
            '"%task_title%" Task has completed with warnings. <a target="_blank" href="%url%">View Log</a> for details.'
        );
        $taskCompletedErrorMessage = $this->__(
            '"%task_title%" Task has completed with errors. <a target="_blank" href="%url%">View Log</a> for details.'
        );

        $lockedObjNoticeMessage = $this->__('Some Walmart request(s) are being processed now.');
        $sendingDataToWalmartMessage = $this->__('Sending %product_title% Product(s) data on Walmart.');
        $viewAllProductLogMessage = $this->__('View Full Product Log');

        $listingLockedMessage = $this->__('The Listing was locked by another process. Please try again later.');
        $listingEmptyMessage = $this->__('Listing is empty.');

        $listingAllItemsMessage = $this->__('Listing All Items On Walmart');
        $listingSelectedItemsMessage = $this->__('Listing Selected Items On Walmart');
        $revisingSelectedItemsMessage = $this->__('Revising Selected Items On Walmart');
        $relistingSelectedItemsMessage = $this->__('Relisting Selected Items On Walmart');
        $stoppingSelectedItemsMessage = $this->__('Stopping Selected Items On Walmart');
        $stoppingAndRemovingSelectedItemsMessage = $this->__(
            'Stopping On Walmart And Removing From Listing Selected Items'
        );
        $deletingAndRemovingSelectedItemsMessage = $this->__('Removing From Walmart And Listing Selected Items');
        $removingSelectedItemsMessage = $this->__('Removing From Listing Selected Items');

        $resetBlockedProductsMessage = $this->__('Reset Inactive (Blocked) Items');

        $successfullyMovedMessage = $this->__('Product(s) was successfully Moved.');
        $productsWereNotMovedMessage = $this->__(
            'Product(s) was not Moved. <a target="_blank" href="%url%">View Log</a> for details.'
        );
        $someProductsWereNotMovedMessage = $this->__(
            'Some Product(s) was not Moved. <a target="_blank" href="%url%">View Log</a> for details.'
        );

        $selectItemsMessage = $this->__('Please select the Products you want to perform the Action on.');
        $selectActionMessage = $this->__('Please select Action.');

        $assignString = $this->__('Assign');

        $noVariationsLeftText = $this->__('All variations are already added.');

        $notSet = $this->__('Not Set');
        $setAttributes = $this->__('Set Attributes');
        $variationManageMatchedAttributesError = $this->__('Please choose valid Attributes.');
        $variationManageMatchedAttributesErrorDuplicateSelection =
            $this->__('You can not choose the same Attribute twice.');

        $variationManageSkuPopUpTitle =
            $this->__('Enter Walmart Parent Product SKU');

        $switchToIndividualModePopUpTitle = $this->__('Change "Manage Variations" Mode');
        $switchToParentModePopUpTitle = $this->__('Change "Manage Variations" Mode');

        $emptySkuError = $this->__('Please enter Walmart Parent Product SKU.');

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Group' => $this->__('Add New Group'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
            'Based on Magento Categories' => $this->__('Based on Magento Categories'),
            'You must select at least 1 Category.' => $this->__('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.'),

            'Add New Shipping Template Policy' => $this->__('Add New Shipping Template Policy'),
            'Add New Shipping Override Policy' => $this->__('Add New Shipping Override Policy'),
            'Add New Product Tax Code Policy'  => $this->__('Add New Product Tax Code Policy'),
            'Add New Listing' => $this->__('Add New Listing'),

            'popup_title' => $popupTitle,
            'popup_title_single' => $popupTitleSingle,
            'failed_products_popup_title' => $failedProductsPopupTitle,

            'task_completed_message' => $taskCompletedMessage,
            'task_completed_success_message' => $taskCompletedSuccessMessage,
            'task_completed_warning_message' => $taskCompletedWarningMessage,
            'task_completed_error_message' => $taskCompletedErrorMessage,

            'locked_obj_notice' => $lockedObjNoticeMessage,
            'sending_data_message' => $sendingDataToWalmartMessage,
            'view_all_product_log_message' => $viewAllProductLogMessage,

            'listing_locked_message' => $listingLockedMessage,
            'listing_empty_message' => $listingEmptyMessage,

            'listing_all_items_message' => $listingAllItemsMessage,
            'listing_selected_items_message' => $listingSelectedItemsMessage,
            'revising_selected_items_message' => $revisingSelectedItemsMessage,
            'relisting_selected_items_message' => $relistingSelectedItemsMessage,
            'stopping_selected_items_message' => $stoppingSelectedItemsMessage,
            'stopping_and_removing_selected_items_message' => $stoppingAndRemovingSelectedItemsMessage,
            'deleting_and_removing_selected_items_message' => $deletingAndRemovingSelectedItemsMessage,
            'removing_selected_items_message' => $removingSelectedItemsMessage,
            'reset_blocked_products_message' => $resetBlockedProductsMessage,

            'successfully_moved' => $successfullyMovedMessage,
            'products_were_not_moved' => $productsWereNotMovedMessage,
            'some_products_were_not_moved' => $someProductsWereNotMovedMessage,

            'select_items_message' => $selectItemsMessage,
            'select_action_message' => $selectActionMessage,

            'templateCategoryPopupTitle' => $templateCategoryPopupTitle,

            'assign' => $assignString,

            'no_variations_left' => $noVariationsLeftText,

            'not_set' => $notSet,
            'set_attributes' => $setAttributes,
            'variation_manage_matched_attributes_error' => $variationManageMatchedAttributesError,
            'variation_manage_matched_attributes_error_duplicate' =>
                $variationManageMatchedAttributesErrorDuplicateSelection,

            'error_changing_product_options' => $this->__('Please Select Product Options.'),

            'variation_manage_matched_sku_popup_title' => $variationManageSkuPopUpTitle,
            'empty_sku_error' => $emptySkuError,

            'switch_to_individual_mode_popup_title' => $switchToIndividualModePopUpTitle,
            'switch_to_parent_mode_popup_title' => $switchToParentModePopUpTitle,

            'Add New Category Policy' => $this->__('Add New Category Policy'),
            'Add New Child Product' => $this->__('Add New Child Product'),

            'Edit SKU'        => $this->__('Edit SKU'),
            'Edit Product ID' => $this->__('Edit Product ID'),

            'Updating SKU has successfully submitted to be processed.' =>
                $this->__('Updating SKU has successfully submitted to be processed.'),
            'Updating GTIN has successfully submitted to be processed.' =>
                $this->__('Updating GTIN has successfully submitted to be processed.'),
            'Updating UPC has successfully submitted to be processed.' =>
                $this->__('Updating UPC has successfully submitted to be processed.'),
            'Updating EAN has successfully submitted to be processed.' =>
                $this->__('Updating EAN has successfully submitted to be processed.'),
            'Updating ISBN has successfully submitted to be processed.' =>
                $this->__('Updating ISBN has successfully submitted to be processed.'),

            'Required at least one identifier' => $this->__('Required at least one identifier'),
            'At least one Variant Attribute must be selected.' =>
                $this->__('At least one Variant Attribute must be selected.'),

            'SKU contains the special characters that are not allowed by Walmart.' => $this->__(
                'Hyphen (-), space ( ), and period (.) are not allowed by Walmart. Please use a correct format.'
            )
        ]);

        $this->js->add(
            <<<JS
    require([
        'jquery',
        'M2ePro/Walmart/Listing/View/Grid',
        'M2ePro/Walmart/Listing/AutoAction',
        'M2ePro/Walmart/Listing/Product/Variation'
    ], function(jQuery){

        M2ePro.productsIdsForList = '{$productsIdsForList}';

        M2ePro.customData.componentMode = '{$component}';
        M2ePro.customData.gridId = '{$gridId}';
        M2ePro.customData.ignoreListings = '{$ignoreListings}';

        M2ePro.customData.marketplace = {$marketplace};

        ListingGridHandlerObj = new WalmartListingViewGrid(
            '{$gridId}',
            {$this->listing['id']}
        );

        ListingGridHandlerObj.movingHandler.setOptions(M2ePro);

        WalmartListingProductVariationObj = new WalmartListingProductVariation(ListingGridHandlerObj);

        jQuery(function() {
            ListingGridHandlerObj.afterInitPage();

            ListingGridHandlerObj.actionHandler.setProgressBar('listing_view_progress_bar');
            ListingGridHandlerObj.actionHandler.setGridWrapper('listing_view_content_container');

            if (M2ePro.productsIdsForList) {
                ListingGridHandlerObj.getGridMassActionObj().checkedString = M2ePro.productsIdsForList;
                ListingGridHandlerObj.actionHandler.listAction();
            }

            window.ListingAutoActionObj = new WalmartListingAutoAction();
            if ({$showAutoAction}) {
                ListingAutoActionObj.loadAutoActionHtml();
            }
        });
    });
JS
        );

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $this->listing]
        ]);
        // ---------------------------------------

        return $viewHeaderBlock->toHtml()
//            . $listingSwitcher->toHtml()
            . parent::getGridHtml();
    }

    protected function getSettingsButtonDropDownItems()
    {
        $items = [];

        $backUrl = $this->getHelper('Data')->makeBackUrlParam('*/walmart_listing/view', [
            'id' => $this->listing['id']
        ]);

        // ---------------------------------------
        $url = $this->getUrl('*/walmart_listing/edit', [
            'id' => $this->listing['id'],
            'back' => $backUrl
        ]);
        $items[] = [
            'label' => $this->__('Listing Settings'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');',
            'default' => true
        ];
        // ---------------------------------------

        // ---------------------------------------
        $items[] = [
            'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
            'label' => $this->__('Auto Add/Remove Rules')
        ];
        // ---------------------------------------

        return $items;
    }

    //########################################

    public function getAddProductsDropDownItems()
    {
        $items = [];

        $backUrl = $this->getHelper('Data')->makeBackUrlParam('*/walmart_listing/view', [
            'id' => $this->listing['id']
        ]);

        // ---------------------------------------
        $url = $this->getUrl('*/walmart_listing_product_add/index', [
            'id' => $this->listing['id'],
            'back' => $backUrl,
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'clear' => 1,
            'step' => 2,
            'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_PRODUCT
        ]);
        $items[] = [
            'label' => $this->__('From Products List'),
            'onclick' => "setLocation('" . $url . "')",
            'default' => true
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/walmart_listing_product_add/index', [
            'id' => $this->listing['id'],
            'back' => $backUrl,
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'clear' => 1,
            'step' => 2,
            'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_CATEGORY
        ]);
        $items[] = [
            'label' => $this->__('From Categories'),
            'onclick' => "setLocation('" . $url . "')"
        ];
        // ---------------------------------------

        return $items;
    }

    //########################################
}
