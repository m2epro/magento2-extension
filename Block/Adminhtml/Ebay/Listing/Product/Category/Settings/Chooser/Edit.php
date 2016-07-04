<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_categoryType = null;

    protected $_selectedCategory = array();

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
        $tabsContainer = $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser\Tabs');
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
        if (is_null($this->_categoryType)) {
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
        $titles = $this->getHelper('Component\Ebay\Category')->getCategoryTitles();

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
