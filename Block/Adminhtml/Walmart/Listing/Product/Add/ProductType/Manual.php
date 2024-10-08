<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType;

class Manual extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
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

        // Initialization block
        // ---------------------------------------
        $this->setId('productTypeManual');
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_controller = 'adminhtml_walmart_listing_product_add_productType_manual';
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
        $url = $this->getUrl('*/*/resetProductType', [
            '_current' => true,
        ]);
        $this->addButton('back', [
            'label' => __('Back'),
            'onclick' => 'ListingGridObj.backClick(\'' . $url . '\')',
            'class' => 'back',
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl(
            '*/walmart_listing_product_add/exitToListing',
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

        $this->addButton('add_products_productType_manual_continue', [
            'label' => __('Continue'),
            'onclick' => 'ListingGridObj.completeCategoriesDataStep()',
            'class' => 'action-primary forward',
        ]);
        // ---------------------------------------
    }

    public function getGridHtml()
    {
        $listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    protected function _toHtml()
    {
        // TEXT
        $this->jsTranslator->addTranslations([
            'productTypePopupTitle' => __('Assign Product Type'),
            'setCategoryPolicy' => __('Set Product Type'),
            'Add New Product Type' => __('Add New Product Type'),
        ]);
        // ---------------------------------------

        // URL
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_Add', ['_current' => true])
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_ProductType')
        );

        $this->jsUrl->add(
            $this->getUrl('*/walmart_listing_product_productType/viewGrid', [
                'map_to_template_js_fn' => 'selectProductType',
            ]),
            'walmart_listing_product_productType/viewGrid'
        );
        // ---------------------------------------

        $this->js->add(
            <<<JS
    selectProductType = function (el, templateId) {
        ListingGridObj.mapToProductType(el, templateId);
    };

    require([
        'M2ePro/Walmart/Listing/Product/Add/ProductType/Grid',
    ],function() {
        Common.prototype.scrollPageToTop = function() { return; }

        window.ListingGridObj = new WalmartListingProductAddProductTypeGrid(
            '{$this->getChildBlock('grid')->getId()}',
            {$this->getRequest()->getParam('id')}
        );

        ListingGridObj.afterInitPage();
    });
JS
        );

        return '<div id="search_asin_products_container">' .
            parent::_toHtml() .
            '</div>';
    }
}
