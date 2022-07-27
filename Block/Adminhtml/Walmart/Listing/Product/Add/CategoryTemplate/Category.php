<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate;

class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
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
        $this->setId('categoryTemplateCategory');
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_controller = 'adminhtml_walmart_listing_product_add_categoryTemplate_category';
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
        $url = $this->getUrl('*/*/resetCategoryTemplate', [
            '_current' => true,
        ]);
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'ListingGridObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add_products_category_template_category_continue', [
            'label'     => $this->__('Continue'),
            'onclick'   => 'ListingGridObj.completeCategoriesDataStep()',
            'class'     => 'action-primary forward'
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
            'templateCategoryPopupTitle' => $this->__('Assign Category Policy'),
            'Add New Category Policy' => $this->__('Add New Category Policy')
        ]);
        // ---------------------------------------

        // URL
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_Add', ['_current' => true])
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Walmart_Listing_Product_Template_Category')
        );

        $this->jsUrl->add($this->getUrl('*/walmart_listing_product_template_category/viewGrid', [
            'map_to_template_js_fn' => 'selectTemplateCategory'
        ]), 'walmart_listing_product_template_category/viewGrid');

        // ---------------------------------------

        $this->js->add(
            <<<JS
    selectTemplateCategory = function (el, templateId)
    {
        ListingGridObj.mapToTemplateCategory(el, templateId);
    };

    require([
        'M2ePro/Walmart/Listing/Product/Add/CategoryTemplate/Grid',
    ],function() {
        Common.prototype.scrollPageToTop = function() { return; }

        window.ListingGridObj = new WalmartListingProductAddCategoryTemplateGrid(
            '{$this->getChildBlock('grid')->getId()}',
            {$this->getRequest()->getParam('id')}
        );

        ListingGridObj.afterInitPage();
    });
JS
        );

        return parent::_toHtml();
    }
}
