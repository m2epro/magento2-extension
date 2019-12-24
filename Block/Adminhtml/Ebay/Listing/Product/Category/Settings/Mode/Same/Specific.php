<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same\Specific
 */
class Specific extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategorySameSpecific');
        // ---------------------------------------

        $this->_headerText = $this->__('eBay Same Categories');

        $this->setTemplate('ebay/listing/product/category/settings/mode/same/specific.phtml');

        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/*', ['_current' => true, 'step' => 2]) . '\');'
        ]);

        $saveUrl = $this->getUrl('*/*/*', [
            'step' => 3,
            '_current' => true
        ]);

        $this->addButton('save', [
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => "EbayListingProductCategorySettingsSpecificObj.submitData('{$saveUrl}');"
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

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $listing]
        ]);

        $this->setChild('view_header', $viewHeaderBlock);

        // ---------------------------------------

        // ---------------------------------------
        $categoryMode = $this->getData('category_mode');
        $categoryValue = $this->getData('category_value');
        $internalData = $this->getData('internal_data');
        $specifics = $this->getData('specifics');

        $specificBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Specific');
        $specificBlock->setMarketplaceId($listing['marketplace_id']);
        $specificBlock->setCategoryMode($categoryMode);
        $specificBlock->setCategoryValue($categoryValue);

        if (!empty($internalData)) {
            $specificBlock->setInternalData($internalData);
        }

        if (!empty($specifics)) {
            $specificBlock->setSelectedSpecifics($specifics);
        }

        $this->setChild('category_specific', $specificBlock);
        // ---------------------------------------

        // ---------------------------------------
        if ($categoryMode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $this->_selectedCategoryPath = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                $categoryValue,
                $listing['marketplace_id']
            );
        } else {
            $attributeLabel = $this->getHelper('Magento\Attribute')->getAttributeLabel($categoryValue);
            $this->_selectedCategoryPath = $this->__('Magento Attribute') . ' > ' . $attributeLabel;
        }
        // ---------------------------------------
    }

    //########################################
}
