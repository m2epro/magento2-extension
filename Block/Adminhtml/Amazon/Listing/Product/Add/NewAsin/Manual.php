<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin;

class Manual extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

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
        $url = $this->getUrl('*/*/resetNewAsin', array(
            '_current' => true
        ));
        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'onclick'   => 'ListingGridHandlerObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ));
        // ---------------------------------------

        $url = $this->getUrl('*/*/index', array('_current' => true, 'step' => 5));
        // ---------------------------------------
        $this->addButton('save_and_go_to_listing_view', array(
            'label'     => $this->__('Continue'),
            'onclick'   => 'ListingGridHandlerObj.checkManualProducts(\''.$url.'\')',
            'class'     => 'action-primary forward'
        ));
        // ---------------------------------------
    }

    public function getGridHtml()
    {
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->createBlock(
            'Listing\View\Header','', ['data' => ['listing' => $listing]]
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
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Listing\Product'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Amazon\Listing\Product\Template\Description')
        );

        $this->jsUrl->add($this->getUrl('*/amazon_listing_product_template_description/viewGrid', [
            'map_to_template_js_fn' => 'selectTemplateDescription'
        ]), 'amazon_listing_product_template_description/viewGrid');

        $this->jsUrl->add(
            $this->getUrl('*/amazon_listing_product_add/checkNewAsinManualProducts', ['_current' => true]),
            'amazon_listing_product_add/checkNewAsinManualProducts'
        );
        // ---------------------------------------

        $this->js->add(
<<<JS
    selectTemplateDescription = function (el, templateId, mapToGeneralId)
    {
        ListingGridHandlerObj.mapToTemplateDescription(el, templateId, mapToGeneralId);
    };

    require([
        'M2ePro/Amazon/Listing/Product/Add/NewAsin/Template/Description/Grid',
    ],function() {
        Common.prototype.scrollPageToTop = function() { return; }

        window.ListingGridHandlerObj = new AmazonListingProductAddNewAsinTemplateDescriptionGrid(
            '{$this->getChildBlock('grid')->getId()}',
            {$this->getRequest()->getParam('id')}
        );

        ListingGridHandlerObj.afterInitPage();

        ListingGridHandlerObj.actionHandler.setOptions(M2ePro);
    });
JS
        );

        return '<div id="search_asin_products_container">' .
                parent::_toHtml() .
            '</div>';
    }

    //########################################
}