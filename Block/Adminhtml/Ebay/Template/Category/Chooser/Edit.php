<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser;

use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_categoryType = null;
    protected $_selectedCategory = [];

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->componentEbayCategory = $componentEbayCategory;
    }
    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayTemplateCategoryChooserEdit');
        $this->setTemplate('ebay/template/category/chooser/edit.phtml');

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    //########################################

    protected function _toHtml()
    {
        $tabsContainer = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Tabs::class,
            '',
            ['category_type' => $this->getCategoryType()]
        );
        $tabsContainer->setDestElementId('chooser_tabs_container');

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
        $titles = $this->componentEbayCategory->getCategoryTitles();

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

    public function getSelectedCategoryPathHtml()
    {
        if (!isset($this->_selectedCategory['mode']) ||
            $this->_selectedCategory['mode'] == TemplateCategory::CATEGORY_MODE_NONE
        ) {
            return <<<HTML
<span style="font-style: italic; color: grey">{$this->__('Not Selected')}</span>
HTML;
        }

        return $this->_selectedCategory['mode'] == TemplateCategory::CATEGORY_MODE_EBAY
            ? "{$this->_selectedCategory['path']} ({$this->_selectedCategory['value']})"
            : $this->_selectedCategory['path'];
    }

    //########################################
}
