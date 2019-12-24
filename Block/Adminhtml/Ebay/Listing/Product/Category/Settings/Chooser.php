<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser
 */
class Chooser extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    protected $_template = 'ebay/listing/product/category/settings/chooser.phtml';

    protected $_marketplaceId = null;

    protected $_accountId = null;

    protected $_attributes = [];

    protected $_internalData = [];

    protected $_divId = null;

    protected $_selectCallback = '';

    protected $_unselectCallback = '';

    protected $_isSingleCategoryMode = false;

    protected $_singleCategoryType = \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN;

    protected $_isShowEditLinks = true;

    public $_isAjax = false;

    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategoryChooser');
        // ---------------------------------------

        $this->_isAjax = $this->getRequest()->isXmlHttpRequest();
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

    public function getAccountId()
    {
        return $this->_accountId;
    }

    public function setAccountId($accountId)
    {
        $this->_accountId = $accountId;
        return $this;
    }

    public function getAttributes()
    {
        if (!empty($this->_attributes)) {
            return $this->_attributes;
        }

        $attributes = $this->getHelper('Magento\Attribute')->getGeneralFromAllAttributeSets();
        if (empty($attributes)) {
            return [];
        }

        $this->_attributes = $this->getHelper('Magento\Attribute')->filterByInputTypes(
            $attributes,
            ['text', 'select']
        );

        return $attributes;
    }

    public function setAttributes($attributes)
    {
        $this->_attributes = $attributes;
        return $this;
    }

    public function getInternalData()
    {
        return $this->_internalData;
    }

    public function setInternalData(array $data)
    {
        $categoryTypePrefixes = [
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN => 'category_main_',
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY => 'category_secondary_',
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN => 'store_category_main_',
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY => 'store_category_secondary_',
        ];

        foreach ($categoryTypePrefixes as $type => $prefix) {
            if (!isset($data[$prefix.'mode'])) {
                continue;
            }

            switch ($data[$prefix.'mode']) {
                case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                    if (!empty($data[$prefix.'path'])) {
                        $path = $data[$prefix.'path'];
                    } else {
                        $path = $this->_prepareEbayCategoryPath($data[$prefix.'id'], $type);
                    }

                    $this->_internalData[$type] = [
                        'mode' => $data[$prefix.'mode'],
                        'value' => $data[$prefix.'id'],
                        'path' => $path . ' (' . $data[$prefix.'id'] . ')'
                    ];

                    break;

                case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                    if (!empty($data[$prefix.'path'])) {
                        $path = $data[$prefix.'path'];
                    } else {
                        $path = $this->_prepareAttributeCategoryPath($data[$prefix.'attribute']);
                    }

                    $this->_internalData[$type] = [
                        'mode' => $data[$prefix.'mode'],
                        'value' => $data[$prefix.'attribute'],
                        'path' => $path
                    ];

                    break;

                case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE:
                    if (!empty($data[$prefix.'message'])) {
                        $this->_internalData[$type] = [
                            'mode' => $data[$prefix.'mode'],
                            'message' => $data[$prefix.'message']
                        ];
                    }

                    break;
            }
        }

        return $this;
    }

    public function setConvertedInternalData(array $data)
    {
        $this->_internalData = $data;
        return $this;
    }

    public function setSingleCategoryData(array $data)
    {
        if (empty($data['path'])) {
            $data['path'] = $this->_preparePath($data['mode'], $data['value'], $this->getSingleCategoryType());
        }

        if ($data['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $data['path'] .= ' (' . $data['value'] . ')';
        }

        $this->_internalData[$this->_singleCategoryType] = $data;
        return $this;
    }

    public function getDivId()
    {
        if ($this->_divId === null) {
            $this->_divId = $this->mathRandom->getUniqueHash('category_chooser_');
        }

        return $this->_divId;
    }

    public function setDivId($divId)
    {
        $this->_divId = $divId;
        return $this;
    }

    public function getSelectCallback()
    {
        return $this->_selectCallback;
    }

    public function setSelectCallback($callback)
    {
        $this->_selectCallback = $callback;
        return $this;
    }

    public function getUnselectCallback()
    {
        return $this->_unselectCallback;
    }

    public function setUnselectCallback($callback)
    {
        $this->_unselectCallback = $callback;
        return $this;
    }

    public function isSingleCategoryMode()
    {
        return $this->_isSingleCategoryMode;
    }

    public function setSingleCategoryMode($mode = true)
    {
        $this->_isSingleCategoryMode = $mode;
        return $this;
    }

    public function getSingleCategoryType()
    {
        return $this->_singleCategoryType;
    }

    public function setSingleCategoryType($type)
    {
        $this->_singleCategoryType = $type;
        return $this;
    }

    public function isShowEditLinks()
    {
        return $this->_isShowEditLinks;
    }

    public function setShowEditLinks($isShow = true)
    {
        $this->_isShowEditLinks = $isShow;
        return $this;
    }

    //########################################

    public function isShowStoreCatalog()
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

    protected function _preparePath($mode, $value, $type)
    {
        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            return $this->_prepareEbayCategoryPath($value, $type);
        } elseif ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return $this->_prepareAttributeCategoryPath($value);
        }

        return '';
    }

    protected function _prepareEbayCategoryPath($value, $type)
    {
        if (in_array($type, $this->getHelper('Component_Ebay_Category')->getEbayCategoryTypes())) {
            return $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                $value,
                $this->getMarketplaceId()
            );
        }

        return $this->getHelper('Component_Ebay_Category_Store')->getPath($value, $this->getAccountId());
    }

    protected function _prepareAttributeCategoryPath($attributeCode)
    {
        $attributeLabel = $this->getHelper('Magento\Attribute')->getAttributeLabel($attributeCode);

        return $this->__('Magento Attribute') . ' > ' . $attributeLabel;
    }

    //########################################

    public function getHelpBlockHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'content' => $this->__(
                '<p>To have new eBay Items listed automatically, eBay Catalog Primary Category must be
                specified along with the M2E Pro Listing Policy settings.</p><br>
                <p>Also, you can select an eBay Store Catalog category if needed.</p><br>
                <p>More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/lAItAQ')
            )
        ]])->toHtml();

        return $helpBlock;
    }

    //########################################
}
