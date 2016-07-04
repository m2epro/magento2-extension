<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode;

class Product extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategoryProduct');
        $this->_controller = 'adminhtml_ebay_listing_product_category_settings_mode_product';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------

        $this->_headerText = $this->__('Set eBay Category for Product(s)');
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
        $url = $this->getUrl('*/*/',array('step' => 1, '_current' => true));
        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\''.$url.'\');'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('next', array(
            'class' => 'action-primary forward',
            'label' => $this->__('Continue'),
            'onclick' => 'EbayListingProductCategorySettingsModeProductGridObj.nextStep();'
        ));
        // ---------------------------------------
    }

    public function getGridHtml()
    {
        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $listing]
        ]);
        // ---------------------------------------

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();

        $popupsHtml = $this->getPopupsHtml();

        return <<<HTML
<div id="products_progress_bar"></div>
<div id="products_container">{$parentHtml}</div>
<div style="display: none">{$popupsHtml}</div>
HTML;
    }

    //########################################

    private function getPopupsHtml()
    {
        return $this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Product\WarningPopup')->toHtml();
    }

    //########################################
}