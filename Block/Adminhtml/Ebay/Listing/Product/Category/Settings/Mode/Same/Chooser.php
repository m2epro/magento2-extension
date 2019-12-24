<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same\Chooser
 */
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

        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/*', ['_current' => true, 'step' => 1]) . '\');'
        ]);

        $onClick = <<<JS
EbayListingProductCategorySettingsChooserObj.submitData(
    '{$this->getUrl('*/*/*', array('step' => 2,'_current' => true))}'
);
JS;
        $this->addButton('next', [
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => $onClick
        ]);
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
    }

    //########################################

    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();

        // ---------------------------------------
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Ebay_Listing_Product_Category_Settings',
            [
                '_current' => true
            ]
        ));

        $this->jsUrl->add($this->getUrl('*/ebay_listing_product_category_settings', [
            'step' => 3,
            '_current' => true
        ]), 'ebay_listing_product_category_settings');

        $this->jsUrl->add($this->getUrl('*/ebay_listing/review', [
            '_current' => true
        ]), 'ebay_listing/review');
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $listing]
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $internalData = $this->getData('internal_data');

        $chooserBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser');
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
