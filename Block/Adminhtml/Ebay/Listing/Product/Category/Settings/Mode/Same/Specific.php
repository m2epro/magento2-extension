<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same;

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

        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/*', array('_current' => true, 'step' => 2)) . '\');'
        ));

        $saveUrl = $this->getUrl('*/*/*', array(
            'step' => 3,
            '_current' => true
        ));

        $this->addButton('save', array(
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => "EbayListingProductCategorySettingsSpecificObj.submitData('{$saveUrl}');"
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

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $listing]
        ]);

        $this->setChild('view_header', $viewHeaderBlock);

        // ---------------------------------------

        // ---------------------------------------
        $categoryMode = $this->getData('category_mode');
        $categoryValue = $this->getData('category_value');
        $internalData = $this->getData('internal_data');
        $specifics = $this->getData('specifics');

        $specificBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Specific');
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
            $this->_selectedCategoryPath = $this->getHelper('Component\Ebay\Category\Ebay')->getPath(
                $categoryValue, $listing['marketplace_id']
            );
        } else {
            $attributeLabel = $this->getHelper('Magento\Attribute')->getAttributeLabel($categoryValue);
            $this->_selectedCategoryPath = $this->__('Magento Attribute') . ' > ' . $attributeLabel;
        }
        // ---------------------------------------
    }

    //########################################
}