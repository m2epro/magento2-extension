<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\StoreCategory getResource()
 */
namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\StoreCategory
 */
class StoreCategory extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Account
     */
    private $accountModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source[]
     */
    private $storeCategorySourceModels = [];

    protected $ebayFactory;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\StoreCategory');
    }

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }
    //########################################

    public function loadByCategoryValue($value, $mode, $accountId)
    {
        return $this->getResource()->loadByCategoryValue($this, $value, $mode, $accountId);
    }

    //########################################

    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->getSelect()->where(
            'template_store_category_id = ? OR template_store_category_secondary_id = ?',
            $this->getId()
        );

        if ((bool)$collection->getSize()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $this->ebayFactory->getObject('Listing')->getCollection();
        $collection->getSelect()->where(
            'auto_global_adding_template_store_category_id = ? OR
             auto_global_adding_template_store_category_secondary_id = ? OR
             auto_website_adding_template_store_category_id = ? OR
             auto_website_adding_template_store_category_secondary_id = ?',
            $this->getId()
        );

        if ((bool)$collection->getSize()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Listing_Auto_Category_Group')->getCollection();
        $collection->getSelect()->where(
            'adding_template_store_category_id = ? OR adding_template_store_category_secondary_id = ?',
            $this->getId()
        );

        if ((bool)$collection->getSize()) {
            return true;
        }

        return false;
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_storecategory');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->accountModel = null;
        $this->storeCategorySourceModels = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_storecategory');

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if ($this->accountModel === null) {
            $this->accountModel = $this->ebayFactory->getCachedObjectLoaded(
                'Account',
                $this->getAccountId()
            );
        }

        return $this->accountModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $instance
     */
    public function setAccount(\Ess\M2ePro\Model\Account $instance)
    {
         $this->accountModel = $instance;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->storeCategorySourceModels[$productId])) {
            return $this->storeCategorySourceModels[$productId];
        }

        $this->storeCategorySourceModels[$productId] = $this->modelFactory
            ->getObject('Ebay_Template_StoreCategory_Source');
        $this->storeCategorySourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->storeCategorySourceModels[$productId]->setStoreCategoryTemplate($this);

        return $this->storeCategorySourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getCategoryMode()
    {
        return (int)$this->getData('category_mode');
    }

    /**
     * @return double
     */
    public function getCategoryId()
    {
        return $this->getData('category_id');
    }

    /**
     * @return string|null
     */
    public function getCategoryAttribute()
    {
        return $this->getData('category_attribute');
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    public function isCategoryModeNone()
    {
        return $this->getCategoryMode() === \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE;
    }

    public function isCategoryModeEbay()
    {
        return $this->getCategoryMode() === \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY;
    }

    public function isCategoryModeAttribute()
    {
        return $this->getCategoryMode() === \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE;
    }

    /**
     * @return string
     */
    public function getCategoryValue()
    {
        return $this->isCategoryModeEbay() ? $this->getCategoryId() : $this->getCategoryAttribute();
    }

    //########################################

    /**
     * @return array
     */
    public function getCategorySource()
    {
        return [
            'mode'      => $this->getData('category_mode'),
            'value'     => $this->getData('category_id'),
            'path'      => $this->getData('category_path'),
            'attribute' => $this->getData('category_attribute')
        ];
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
