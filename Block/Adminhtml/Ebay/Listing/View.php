<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class View extends AbstractContainer
{
    private \Ess\M2ePro\Model\Listing $listing;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;

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

        $this->listing = $this->globalDataHelper->getValue('view_listing');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher $viewModeSwitcher */
        $viewModeSwitcher = $this->getLayout()
                                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::class);

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingView');
        $this->_controller = 'adminhtml_ebay_listing_view_' . $viewModeSwitcher->getSelectedParam();
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');
        $this->css->addFile('ebay/listing/view.css');

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::class
            )
        );

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->appendHelpBlock(
                [
                    'content' => $this->__(
                        '<p>M2E Pro Listing is a group of Magento Products sold on a certain Marketplace
                    from a particular Account. M2E Pro has several options to display the content of
                    Listings referring to different data details. Each of the view options contains a
                    unique set of available Actions accessible in the Mass Actions drop-down.</p>'
                    ),
                ]
            );
        }

        // ---------------------------------------
        $backUrl = $this->dataHelper->getBackUrl('*/ebay_listing/allItems');

        $this->addButton(
            'back',
            [
                'label' => $this->__('Back'),
                'onclick' => 'setLocation(\'' . $backUrl . '\');',
                'class' => 'back',
            ]
        );
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl(
            '*/ebay_log_listing_product',
            [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId(),
            ]
        );
        $this->addButton(
            'view_log',
            [
                'label' => $this->__('Logs & Events'),
                'onclick' => 'window.open(\'' . $url . '\',\'_blank\')',
            ]
        );
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton(
            'edit_templates',
            [
                'label' => $this->__('Edit Settings'),
                'onclick' => '',
                'class' => 'drop_down edit_default_settings_drop_down primary',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
                'options' => $this->getSettingsButtonDropDownItems(),
            ]
        );
        // ---------------------------------------

        // ---------------------------------------
        //$this->addButton(
        //    'add_products',
        //    [
        //        'id' => 'add_products',
        //        'label' => $this->__('Add Products'),
        //        'class' => 'add',
        //        'button_class' => '',
        //        'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
        //        'options' => $this->getAddProductsDropDownItems(),
        //    ]
        //);

        $newWizardIndexPageUrl = $this->getUrl(
            '*/ebay_listing_wizard/create',
            [
                'listing_id' => $this->listing->getId(),
                'type' => \Ess\M2ePro\Model\Ebay\Listing\Wizard::TYPE_GENERAL,
            ]
        );
        $this->addButton(
            'add_products_new_wizard',
            [
                'id' => 'add_products',
                'label' => $this->__('Add Products'),
                'class' => 'add primary',
                'button_class' => '',
                'onclick' => 'setLocation(\'' . $newWizardIndexPageUrl . '\');',
            ]
        );

        // ---------------------------------------

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

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            [
                'data' => ['listing' => $this->listing],
            ]
        );
        $viewHeaderBlock->setListingViewMode(true);

        /** @var \Ess\M2ePro\Helper\Data $helper */
        $helper = $this->dataHelper;

        $this->jsUrl->addUrls(
            array_merge(
                [],
                $helper->getControllerActions(
                    'Ebay\Listing',
                    ['_current' => true]
                ),
                $helper->getControllerActions(
                    'Ebay_Listing_AutoAction',
                    ['listing_id' => $this->getRequest()->getParam('id')]
                ),
                ['variationProductManage' => $this->getUrl('*/ebay_listing_variation_product_manage/index')]
            )
        );

        $path = 'ebay_listing/transferring/index';
        $this->jsUrl->add(
            $this->getUrl(
                '*/' . $path,
                [
                    'listing_id' => $this->listing->getId(),
                ]
            ),
            $path
        );

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

        $path = 'ebay_listing_transferring/getListings';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $this->jsTranslator->addTranslations(
            [
                'Remove Category' => __('Remove Category'),
                'Add New Rule' => __('Add New Rule'),
                'Add/Edit Categories Rule' => __('Add/Edit Categories Rule'),
                'Auto Add/Remove Rules' => __('Auto Add/Remove Rules'),
                'Based on Magento Categories' => __('Based on Magento Categories'),
                'You must select at least 1 Category.' => __('You must select at least 1 Category.'),
                'Rule with the same Title already exists.' => __('Rule with the same Title already exists.'),
                'Compatibility Attribute' => __('Compatibility Attribute'),
                'Sell on Another Marketplace' => __('Sell on Another Marketplace'),
                'Create new' => __('Create new'),
                'Linking Product' => __('Linking Product'),
                'Rule not created' => __('Rule not created'),
                'Please select at least one action from the available options' =>
                    __('Please select at least one action from the available options'),
            ]
        );

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    define('EbayListingAutoActionInstantiation', [
        'M2ePro/Ebay/Listing/AutoAction',
        'extjs/ext-tree-checkbox'
    ], function(){

        window.ListingAutoActionObj = new EbayListingAutoAction();

    });
JS
            );
        }

        return $viewHeaderBlock->toHtml() .
            parent::getGridHtml();
    }

    protected function getSettingsButtonDropDownItems()
    {
        $items = [];

        $backUrl = $this->dataHelper->makeBackUrlParam(
            '*/ebay_listing/view',
            ['id' => $this->listing->getId()]
        );

        $url = $this->getUrl(
            '*/ebay_listing/edit',
            [
                'id' => $this->listing->getId(),
                'back' => $backUrl,
            ]
        );
        $items[] = [
            'label' => $this->__('Configuration'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');',
            'default' => true,
        ];

        $items[] = [
            'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
            'label' => $this->__('Auto Add/Remove Rules'),
        ];

        return $items;
    }

    public function getAddProductsDropDownItems()
    {
        $items = [];

        $url = $this->getUrl(
            '*/ebay_listing_product_add',
            [
                'source' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT,
                'clear' => true,
                'id' => $this->listing->getId(),
            ]
        );
        $items[] = [
            'id' => 'add_products_mode_product',
            'label' => $this->__('From Products List'),
            'onclick' => "setLocation('" . $url . "')",
            'default' => true,
        ];

        $url = $this->getUrl(
            '*/ebay_listing_product_add',
            [
                'source' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_CATEGORY,
                'clear' => true,
                'id' => $this->listing->getId(),
            ]
        );
        $items[] = [
            'id' => 'add_products_mode_category',
            'label' => $this->__('From Categories'),
            'onclick' => "setLocation('" . $url . "')",
        ];

        return $items;
    }
}
