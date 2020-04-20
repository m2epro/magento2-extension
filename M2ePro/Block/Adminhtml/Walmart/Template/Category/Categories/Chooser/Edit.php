<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_template = 'walmart/template/category/categories/chooser/edit.phtml';
    //########################################

    protected $_marketplaceId;
    protected $_selectedCategory = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryCategoriesChooserEdit');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs $tabsBlock */
        $tabsBlock = $this->createBlock(
            'Walmart_Template_Category_Categories_Chooser_Tabs'
        );

        return parent::_toHtml() .
               $tabsBlock->toHtml() .
               '<div id="chooser_tabs_container"></div>';
    }

    //########################################

    public function getSelectedCategory()
    {
        return $this->_selectedCategory;
    }

    public function setSelectedCategory(array $selectedCategory)
    {
        $this->_selectedCategory = $selectedCategory;
        return $this;
    }

    // ---------------------------------------

    public function setMarketplaceId($value)
    {
        $this->_marketplaceId = $value;
        return $this;
    }

    //########################################
}
