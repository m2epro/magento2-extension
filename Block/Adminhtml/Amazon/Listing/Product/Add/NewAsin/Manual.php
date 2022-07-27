<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin;

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
        $this->setId('newAsinDescriptionTemplateManual');
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_controller = 'adminhtml_amazon_listing_product_add_newAsin_manual';
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
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'ListingGridObj.stepNewAsinBack()',
            'class'     => 'back'
        ]);
        // ---------------------------------------

        $url = $this->getUrl('*/*/index', ['_current' => true, 'step' => 5]);
        // ---------------------------------------
        $this->addButton('add_products_new_asin_manual_continue', [
            'label'   => $this->__('Continue'),
            'onclick' => 'ListingGridObj.checkManualProducts(\''.$url.'\')',
            'class'   => 'action-primary forward'
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
            'templateDescriptionPopupTitle' => $this->__('Assign Description Policy'),
            'setDescriptionPolicy' => $this->__('Set Description Policy'),
            'Add New Description Policy' => $this->__('Add New Description Policy')
        ]);
        // ---------------------------------------

        // URL
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Template_Description')
        );

        $this->jsUrl->add($this->getUrl('*/amazon_listing_product_template_description/viewGrid', [
            'map_to_template_js_fn' => 'selectTemplateDescription'
        ]), 'amazon_listing_product_template_description/viewGrid');

        $this->jsUrl->add(
            $this->getUrl('*/amazon_listing_product_add/checkNewAsinManualProducts', ['_current' => true]),
            'amazon_listing_product_add/checkNewAsinManualProducts'
        );

        $this->jsUrl->add(
            $this->getUrl('*/amazon_listing_product_add/resetDescriptionTemplate', ['_current' => true]),
            'amazon_listing_product_add/resetDescriptionTemplate'
        );
        // ---------------------------------------

        $this->js->add(
            <<<JS
    selectTemplateDescription = function (el, templateId, mapToGeneralId)
    {
        ListingGridObj.mapToTemplateDescription(el, templateId, mapToGeneralId);
    };

    require([
        'M2ePro/Amazon/Listing/Product/Add/NewAsin/Template/Description/Grid',
    ],function() {
        Common.prototype.scrollPageToTop = function() { return; }

        window.ListingGridObj = new AmazonListingProductAddNewAsinTemplateDescriptionGrid(
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

    //########################################
}
