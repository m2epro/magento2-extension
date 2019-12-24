<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate\Category
 */
class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

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
            'onclick'   => 'ListingGridHandlerObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('save_and_go_to_listing_view', [
            'id'        => 'save_and_go_to_listing_view',
            'label'     => $this->__('Continue'),
            'onclick'   => 'ListingGridHandlerObj.completeCategoriesDataStep()',
            'class'     => 'action-primary forward'
        ]);
        // ---------------------------------------
    }

    public function getGridHtml()
    {
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->createBlock(
            'Listing_View_Header',
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
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart_Listing_Product'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Add', ['_current' => true])
        );
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Product_Template_Category')
        );

        $this->jsUrl->add($this->getUrl('*/walmart_listing_product_template_category/viewGrid', [
            'map_to_template_js_fn' => 'selectTemplateCategory'
        ]), 'walmart_listing_product_template_category/viewGrid');

        // ---------------------------------------

        $this->js->add(
            <<<JS
    selectTemplateCategory = function (el, templateId)
    {
        ListingGridHandlerObj.mapToTemplateCategory(el, templateId);
    };

    require([
        'M2ePro/Walmart/Listing/Product/Add/CategoryTemplate/Grid',
    ],function() {
        Common.prototype.scrollPageToTop = function() { return; }

        window.ListingGridHandlerObj = new WalmartListingProductAddCategoryTemplateGrid(
            '{$this->getChildBlock('grid')->getId()}',
            {$this->getRequest()->getParam('id')}
        );

        ListingGridHandlerObj.afterInitPage();

        ListingGridHandlerObj.actionHandler.setOptions(M2ePro);
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
