<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same;

class Chooser extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategorySameChooser');
        // ---------------------------------------

        $this->_headerText = $this->__('eBay Same Categories');

        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/*', array('_current' => true, 'step' => 1)) . '\');'
        ));

        $onClick = <<<JS
EbayListingProductCategorySettingsChooserObj.submitData(
    '{$this->getUrl('*/*/*', array('step' => 2,'_current' => true))}'
);
JS;
        $this->addButton('next', array(
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => $onClick
        ));
    }

    //########################################

    public function getHeaderWidth()
    {
        return 'width:50%;';
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

//
//        // ---------------------------------------
//        $data = array(
//            'label' => $this->__('Yes'),
//            'id'    => 'existing_templates_confirm_button'
//        );
//        $this->setChild(
//            'existing_templates_confirm_button',
//            $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data)
//        );
//        // ---------------------------------------
//        $data = array(
//            'label' => $this->__('No'),
//            'id'    => 'existing_templates_cancel_button'
//        );
//        $this->setChild(
//            'existing_templates_cancel_button',
//            $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data)
//        );
//        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();

        // ---------------------------------------
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            '\Ebay\Listing\Product\Category\Settings',
            array(
                '_current' => true
            )
        ));

        $this->jsUrl->add($this->getUrl('*/ebay_listing_product_category_settings', array(
            'step' => 3,
            '_current' => true
        )), 'ebay_listing_product_category_settings');

        $this->jsUrl->add($this->getUrl('*/ebay_listing/review', array(
            '_current' => true
        )), 'ebay_listing/review');
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $listing]
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $internalData = $this->getData('internal_data');

        $chooserBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser');
        $chooserBlock->setMarketplaceId($listing['marketplace_id']);
        $chooserBlock->setAccountId($listing['account_id']);

        if (!empty($internalData)) {
            $chooserBlock->setInternalData($internalData);
        }
        // ---------------------------------------

        return <<<HTML
{$viewHeaderBlock->toHtml()}
{$chooserBlock->toHtml()}
{$parentHtml}
HTML;
    }

    //########################################
}