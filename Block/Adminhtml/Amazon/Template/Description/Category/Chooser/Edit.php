<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_template = 'amazon/template/description/category/chooser/edit.phtml';
    //########################################

    protected $_marketplaceId;
    protected $_selectedCategory = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionCategoryChooserEdit');
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
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs $tabsBlock */
        $tabsBlock = $this->createBlock(
            'Amazon\\Template\\Description\\Category\\Chooser\\Tabs'
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
