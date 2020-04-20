<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_categoryType = null;

    protected $_selectedCategory = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategoryChooserEdit');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
//        $this->removeButton('back');
//        $this->removeButton('reset');
//        $this->removeButton('delete');
//        $this->removeButton('add');
//        $this->removeButton('save');
//        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $this->setTemplate('ebay/listing/product/category/settings/chooser/edit.phtml');
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        // ---------------------------------------
        $tabsContainer = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Tabs');
        $tabsContainer->setDestElementId('chooser_tabs_container');
        // ---------------------------------------

        return '<div id="chooser_container">'.
                parent::_toHtml() .
                $tabsContainer->toHtml() .
                '<div id="chooser_tabs_container"></div></div>';
    }

    //########################################

    public function getCategoryType()
    {
        if ($this->_categoryType === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Category type is not set.');
        }

        return $this->_categoryType;
    }

    public function setCategoryType($categoryType)
    {
        $this->_categoryType = $categoryType;
        return $this;
    }

    public function getCategoryTitle()
    {
        $titles = $this->getHelper('Component_Ebay_Category')->getCategoryTitles();

        return isset($titles[$this->_categoryType]) ? $titles[$this->_categoryType] : '';
    }

    public function getSelectedCategory()
    {
        return $this->_selectedCategory;
    }

    public function setSelectedCategory(array $selectedCategory)
    {
        $this->_selectedCategory = $selectedCategory;
        return $this;
    }

    //########################################
}
