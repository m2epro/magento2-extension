<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid;

class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    protected \Ess\M2ePro\Model\Listing $listing;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Helper\Data\Session $sessionDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->sessionDataHelper = $sessionDataHelper;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('view_listing');

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Switcher $viewModeSwitcher */
        $viewModeSwitcher = $this->getLayout()
                                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Switcher::class);

        $this->setId('walmartListingView');
        $this->_controller = 'adminhtml_walmart_listing_view_' . $viewModeSwitcher->getSelectedParam();

        $this->removeButton('add');
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');
        $this->css->addFile('walmart/listing/view.css');
        $this->css->addFile('walmart/listing/product/variation/grid.css');

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->appendHelpBlock(
                [
                    'content' => __(
                        '<p>M2E Pro Listing is a group of Magento Products sold on a certain Marketplace from a
                    particular Account. M2E Pro has several options to display the content of Listings
                    referring to different data details. Each of the view options contains a unique set of
                    available Actions accessible in the Mass Actions drop-down.</p>'
                    ),
                ]
            );
        }

        $this->addButton(
            'back',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/walmart_listing/index') . '\');',
                'class' => 'back',
            ]
        );

        $url = $this->getUrl(
            '*/walmart_log_listing_product/index',
            [
                AbstractGrid::LISTING_ID_FIELD => $this->listing->getId(),
            ]
        );
        $onClick = 'window.open(\'' . $url . '\');';
        $this->addButton(
            'view_logs',
            [
                'label' => __('Logs & Events'),
                'onclick' => $onClick,
                'class' => '',
            ]
        );

        $this->addButton(
            'edit_settings',
            [
                'label' => __('Edit Settings'),
                'onclick' => '',
                'class' => 'drop_down edit_default_settings_drop_down primary',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
                'options' => $this->getSettingsButtonDropDownItems(),
            ]
        );

        $this->addButton(
            'add_products',
            [
                'id' => 'add_products',
                'label' => __('Add Products'),
                'class' => 'add',
                'button_class' => '',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
                'options' => $this->getAddProductsDropDownItems(),
            ]
        );

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        return '<div id="listing_view_progress_bar"></div>' .
            '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
            '<div id="listing_view_content_container">' .
            parent::_toHtml() .
            '</div>';
    }

    public function getGridHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::getGridHtml();
        }

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(
                AbstractGrid::class
            )
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Walmart\Account::class)
        );

        $showAutoAction = \Ess\M2ePro\Helper\Json::encode((bool)$this->getRequest()->getParam('auto_actions'));

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                'Walmart_Listing_AutoAction',
                ['listing_id' => $this->getRequest()->getParam('id')]
            )
        );

        $path = 'walmart_listing_autoAction/getProductTypesList';
        $this->jsUrl->add(
            $this->getUrl(
                '*/' . $path,
                [
                    'marketplace_id' => $this->listing->getMarketplaceId(),
                ]
            ),
            $path
        );

        $path = 'walmart_log_listing_product/index';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $this->jsUrl->add(
            $this->getUrl(
                '*/walmart_log_listing_product/index',
                [
                    AbstractGrid::LISTING_ID_FIELD => $this->listing['id'],
                ]
            ),
            'logViewUrl'
        );

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Listing'));

        $this->jsUrl->addUrls(
            [
                'runListProducts' => $this->getUrl('*/walmart_listing/runListProducts'),
                'runRelistProducts' => $this->getUrl('*/walmart_listing/runRelistProducts'),
                'runReviseProducts' => $this->getUrl('*/walmart_listing/runReviseProducts'),
                'runStopProducts' => $this->getUrl('*/walmart_listing/runStopProducts'),
                'runStopAndRemoveProducts' => $this->getUrl('*/walmart_listing/runStopAndRemoveProducts'),
                'runDeleteAndRemoveProducts' => $this->getUrl('*/walmart_listing/runDeleteAndRemoveProducts'),
            ]
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_ProductType')
        );
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Listing_Product_Variation'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_Variation_Manage')
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_Variation_Vocabulary')
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_Variation_Individual')
        );

        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListingGrid'), 'moveToListingGridHtml');
        $this->jsUrl->add($this->getUrl('*/listing_moving/prepareMoveToListing'), 'prepareData');
        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListing'), 'moveToListing');

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_mapping/mapProductPopupHtml',
                [
                    'account_id' => $this->listing->getAccountId(),
                    'marketplace_id' => $this->listing->getMarketplaceId(),
                ]
            ),
            'mapProductPopupHtml'
        );
        $this->jsUrl->add($this->getUrl('*/listing_mapping/remap'), 'listing_mapping/remap');

        $this->jsUrl->add($this->getUrl('*/walmart_marketplace/index'), 'marketplaceSynchUrl');

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing/saveListingAdditionalData',
                [
                    'id' => $this->listing['id'],
                ]
            ),
            'saveListingAdditionalData'
        );

        $component = \Ess\M2ePro\Helper\Component\Walmart::NICK;
        $gridId = $this->getChildBlock('grid')->getId();
        $ignoreListings = \Ess\M2ePro\Helper\Json::encode([$this->listing['id']]);
        $marketplace = \Ess\M2ePro\Helper\Json::encode(
            array_merge(
                $this->listing->getMarketplace()->getData(),
                $this->listing->getMarketplace()->getChildObject()->getData()
            )
        );

        $temp = $this->sessionDataHelper->getValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $popupTitle = __('Moving Walmart Items');

        $taskCompletedMessage = __('Task completed. Please wait ...');
        $taskCompletedSuccessMessage = $this->__('"%task_title%" Task has submitted to be processed.');
        $taskCompletedWarningMessage = __(
            '"%task_title%" Task has completed with warnings. <a target="_blank" href="%url%">View Log</a> for details.'
        );
        $taskCompletedErrorMessage = __(
            '"%task_title%" Task has completed with errors. <a target="_blank" href="%url%">View Log</a> for details.'
        );

        $sendingDataToWalmartMessage = __('Sending %product_title% Product(s) data on Walmart.');
        $viewAllProductLogMessage = __('View Full Product Log');

        $listingLockedMessage = __('The Listing was locked by another process. Please try again later.');
        $listingEmptyMessage = __('Listing is empty.');

        $listingAllItemsMessage = __('Listing All Items On Walmart');
        $listingSelectedItemsMessage = __('Listing Selected Items On Walmart');
        $revisingSelectedItemsMessage = __('Revising Selected Items On Walmart');
        $relistingSelectedItemsMessage = __('Relisting Selected Items On Walmart');
        $stoppingSelectedItemsMessage = __('Stopping Selected Items On Walmart');
        $stoppingAndRemovingSelectedItemsMessage = __(
            'Stopping On Walmart And Removing From Listing Selected Items'
        );
        $deletingAndRemovingSelectedItemsMessage = __('Removing From Walmart And Listing Selected Items');
        $removingSelectedItemsMessage = __('Removing From Listing Selected Items');

        $selectItemsMessage = __('Please select the Products you want to perform the Action on.');
        $selectActionMessage = __('Please select Action.');

        $assignString = __('Assign');

        $noVariationsLeftText = __('All variations are already added.');

        $notSet = __('Not Set');
        $setAttributes = __('Set Attributes');
        $variationManageMatchedAttributesError = __('Please choose valid Attributes.');
        $variationManageMatchedAttributesErrorDuplicateSelection =
            __('You can not choose the same Attribute twice.');

        $variationManageSkuPopUpTitle =
            __('Enter Walmart Parent Product SKU');

        $switchToIndividualModePopUpTitle = __('Change "Manage Variations" Mode');
        $switchToParentModePopUpTitle = __('Change "Manage Variations" Mode');

        $emptySkuError = __('Please enter Walmart Parent Product SKU.');

        $this->jsTranslator->addTranslations(
            [
                'Remove Category' => __('Remove Category'),
                'Add New Rule' => __('Add New Rule'),
                'Add/Edit Categories Rule' => __('Add/Edit Categories Rule'),
                'Auto Add/Remove Rules' => __('Auto Add/Remove Rules'),
                'Based on Magento Categories' => __('Based on Magento Categories'),
                'You must select at least 1 Category.' => __('You must select at least 1 Category.'),
                'Rule with the same Title already exists.' => __('Rule with the same Title already exists.'),

                'Add New Shipping Template Policy' => __('Add New Shipping Template Policy'),
                'Add New Shipping Override Policy' => __('Add New Shipping Override Policy'),
                'Add New Product Tax Code Policy' => __('Add New Product Tax Code Policy'),
                'Add New Listing' => __('Add New Listing'),

                'popup_title' => $popupTitle,

                'task_completed_message' => $taskCompletedMessage,
                'task_completed_success_message' => $taskCompletedSuccessMessage,
                'task_completed_warning_message' => $taskCompletedWarningMessage,
                'task_completed_error_message' => $taskCompletedErrorMessage,

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

                'select_items_message' => $selectItemsMessage,
                'select_action_message' => $selectActionMessage,

                'productTypePopupTitle' => __('Assign Product Type'),

                'assign' => $assignString,

                'no_variations_left' => $noVariationsLeftText,

                'not_set' => $notSet,
                'set_attributes' => $setAttributes,
                'variation_manage_matched_attributes_error' => $variationManageMatchedAttributesError,
                'variation_manage_matched_attributes_error_duplicate' =>
                    $variationManageMatchedAttributesErrorDuplicateSelection,

                'error_changing_product_options' => __('Please Select Product Options.'),

                'variation_manage_matched_sku_popup_title' => $variationManageSkuPopUpTitle,
                'empty_sku_error' => $emptySkuError,

                'switch_to_individual_mode_popup_title' => $switchToIndividualModePopUpTitle,
                'switch_to_parent_mode_popup_title' => $switchToParentModePopUpTitle,

                'Add New Product Type' => __('Add New Product Type'),
                'Add New Child Product' => __('Add New Child Product'),

                'Edit SKU' => __('Edit SKU'),
                'Edit Product ID' => __('Edit Product ID'),
                'Linking Product' => __('Linking Product'),

                'Updating SKU has submitted to be processed.' =>
                    __('Updating SKU has submitted to be processed.'),
                'Updating GTIN has submitted to be processed.' =>
                    __('Updating GTIN has submitted to be processed.'),
                'Updating UPC has submitted to be processed.' =>
                    __('Updating UPC has submitted to be processed.'),
                'Updating EAN has submitted to be processed.' =>
                    __('Updating EAN has submitted to be processed.'),
                'Updating ISBN has submitted to be processed.' =>
                    __('Updating ISBN has submitted to be processed.'),

                'Required at least one identifier' => __('Required at least one identifier'),
                'At least one Variant Attribute must be selected.' =>
                    __('At least one Variant Attribute must be selected.'),

                'The length of SKU must be less than 50 characters.' => __(
                    'The length of SKU must be less than 50 characters.'
                ),

                'Rule not created' => __('Rule not created'),
                'Please select at least one action from the available options' =>
                    __('Please select at least one action from the available options'),
            ]
        );

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

        ListingGridObj = new WalmartListingViewGrid(
            '{$gridId}',
            {$this->listing['id']}
        );

        ListingGridObj.movingHandler.setProgressBar('listing_view_progress_bar');
        ListingGridObj.movingHandler.setGridWrapper('listing_view_content_container');

        WalmartListingProductVariationObj = new WalmartListingProductVariation(ListingGridObj);

        jQuery(function() {
            ListingGridObj.afterInitPage();

            ListingGridObj.actionHandler.setProgressBar('listing_view_progress_bar');
            ListingGridObj.actionHandler.setGridWrapper('listing_view_content_container');

            if (M2ePro.productsIdsForList) {
                ListingGridObj.getGridMassActionObj().checkedString = M2ePro.productsIdsForList;
                ListingGridObj.actionHandler.listAction();
            }

            window.ListingAutoActionObj = new WalmartListingAutoAction();
            if ({$showAutoAction}) {
                ListingAutoActionObj.loadAutoActionHtml();
            }
        });
    });
JS
        );

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            [
                'data' => ['listing' => $this->listing],
            ]
        );

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    protected function getSettingsButtonDropDownItems()
    {
        $items = [];

        $backUrl = $this->dataHelper->makeBackUrlParam(
            '*/walmart_listing/view',
            [
                'id' => $this->listing['id'],
            ]
        );

        $url = $this->getUrl(
            '*/walmart_listing/edit',
            [
                'id' => $this->listing['id'],
                'back' => $backUrl,
            ]
        );
        $items[] = [
            'label' => __('Configuration'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');',
            'default' => true,
        ];

        $items[] = [
            'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
            'label' => __('Auto Add/Remove Rules'),
        ];

        return $items;
    }

    public function getAddProductsDropDownItems()
    {
        $items = [];

        $backUrl = $this->dataHelper->makeBackUrlParam(
            '*/walmart_listing/view',
            [
                'id' => $this->listing['id'],
            ]
        );

        $url = $this->getUrl(
            '*/walmart_listing_product_add/index',
            [
                'id' => $this->listing['id'],
                'back' => $backUrl,
                'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'clear' => 1,
                'step' => 2,
                'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_PRODUCT,
            ]
        );
        $items[] = [
            'id' => 'add_products_mode_product',
            'label' => __('From Products List'),
            'onclick' => "setLocation('" . $url . "')",
            'default' => true,
        ];

        $url = $this->getUrl(
            '*/walmart_listing_product_add/index',
            [
                'id' => $this->listing['id'],
                'back' => $backUrl,
                'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'clear' => 1,
                'step' => 2,
                'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_CATEGORY,
            ]
        );
        $items[] = [
            'id' => 'add_products_mode_category',
            'label' => __('From Categories'),
            'onclick' => "setLocation('" . $url . "')",
        ];

        return $items;
    }
}
