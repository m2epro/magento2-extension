<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category;

use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser
 */
class Chooser extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    const MODE_BOTH_CATEGORY   = 'both';
    const MODE_EBAY_CATEGORY   = 'ebay';
    const MODE_EBAY_PRIMARY    = 'ebay_primary';
    const MODE_EBAY_SECONDARY  = 'ebay_secondary';
    const MODE_STORE_CATEGORY  = 'store';

    //########################################

    protected $_marketplaceId;
    protected $_accountId;
    protected $_categoryMode = self::MODE_BOTH_CATEGORY;

    protected $_isEditCategoryAllowed = true;

    protected $_attributes     = [];
    protected $_categoriesData = [];

    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayTemplateCategoryChooser_');
        $this->setTemplate('ebay/template/category/chooser.phtml');

        $this->_attributes = $this->getHelper('Magento\Attribute')->filterByInputTypes(
            $this->getHelper('Magento\Attribute')->getAll(),
            ['text', 'select']
        );
    }

    //########################################

    public function getMarketplaceId()
    {
        return $this->_marketplaceId;
    }

    public function setMarketplaceId($marketplaceId)
    {
        $this->_marketplaceId = $marketplaceId;
        return $this;
    }

    //----------------------------------------

    public function getAccountId()
    {
        return $this->_accountId;
    }

    public function setAccountId($accountId)
    {
        $this->_accountId = $accountId;
        return $this;
    }

    //----------------------------------------

    public function getAttributes()
    {
        return $this->_attributes;
    }

    //----------------------------------------

    public function getCategoriesData()
    {
        return $this->_categoriesData;
    }

    public function setCategoriesData(array $data)
    {
        $this->_categoriesData = $data;
        return $this;
    }

    //----------------------------------------

    public function setCategoryMode($mode)
    {
        $this->_categoryMode = $mode;
        return $this;
    }

    public function getCategoryMode()
    {
        return $this->_categoryMode;
    }

    public function isCategoryModeBoth()
    {
        return $this->getCategoryMode() === self::MODE_BOTH_CATEGORY;
    }

    public function isCategoryModeEbay()
    {
        return $this->getCategoryMode() === self::MODE_EBAY_CATEGORY;
    }

    public function isCategoryModeEbayPrimary()
    {
        return $this->getCategoryMode() === self::MODE_EBAY_PRIMARY;
    }

    public function isCategoryModeEbaySecondary()
    {
        return $this->getCategoryMode() === self::MODE_EBAY_SECONDARY;
    }

    public function isCategoryModeStore()
    {
        return $this->getCategoryMode() === self::MODE_STORE_CATEGORY;
    }

    //----------------------------------------

    public function getIsEditCategoryAllowed()
    {
        return $this->_isEditCategoryAllowed;
    }

    public function setIsEditCategoryAllowed($isEditCategoryAllowed)
    {
        $this->_isEditCategoryAllowed = $isEditCategoryAllowed;
    }

    //----------------------------------------

    public function isItemSpecificsRequired()
    {
        if (!isset($this->_categoriesData[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN]['value'])) {
            return false;
        }

        return $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
            $this->_categoriesData[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN]['value'],
            $this->getMarketplaceId()
        );
    }

    //########################################

    public function hasStoreCatalog()
    {
        if ($this->getAccountId() === null) {
            return false;
        }

        $storeCategories = $this->ebayFactory
            ->getCachedObjectLoaded('Account', (int)$this->getAccountId())
            ->getChildObject()
            ->getEbayStoreCategories();

        return !empty($storeCategories);
    }

    //########################################

    public function getCategoryPathHtml($categoryType)
    {
        if (!isset($this->_categoriesData[$categoryType]['mode']) ||
            $this->_categoriesData[$categoryType]['mode'] == TemplateCategory::CATEGORY_MODE_NONE
        ) {
            return <<<HTML
<span style="font-style: italic; color: grey">{$this->__('Not Selected')}</span>
HTML;
        }

        $category = $this->_categoriesData[$categoryType];
        return $category['mode'] == TemplateCategory::CATEGORY_MODE_EBAY
            ? "{$category['path']} ({$category['value']})"
            : $category['path'];
    }

    //########################################
}
