<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Category
 */
class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCategory');
        $this->_controller = 'adminhtml_ebay_listing_product_category_settings_mode_category';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->_headerText = $this->__('Set Category (Based On Magento Categories)');

        $url = $this->getUrl('*/*/', ['step' => 1, '_current' => true]);
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\''.$url.'\');'
        ]);

        $this->addButton('next', [
            'id'        => 'ebay_listing_category_continue_btn',
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => "EbayListingProductCategorySettingsModeCategoryGridObj.completeCategoriesDataStep(1, 1)"
        ]);
    }

    //########################################

    public function getGridHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::getGridHtml();
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );

        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $listing]
        ]);

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    //########################################

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
        return $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_WarningPopup')->toHtml();
    }

    //########################################
}
