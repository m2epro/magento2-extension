<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\OtherCategory getResource()
 */
namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\OtherCategory
 */
class OtherCategory extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    /**
     * @var \Ess\M2ePro\Model\Account
     */
    private $accountModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Source[]
     */
    private $otherCategorySourceModels = [];

    protected $ebayFactory;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\OtherCategory');
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

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_othercategory');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->marketplaceModel = null;
        $this->accountModel = null;
        $this->otherCategorySourceModels = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_othercategory');

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->ebayFactory->getCachedObjectLoaded(
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplaceModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $instance
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $instance)
    {
         $this->marketplaceModel = $instance;
    }

    // ---------------------------------------

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
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->otherCategorySourceModels[$productId])) {
            return $this->otherCategorySourceModels[$productId];
        }

        $this->otherCategorySourceModels[$productId] = $this->modelFactory
            ->getObject('Ebay_Template_OtherCategory_Source');
        $this->otherCategorySourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->otherCategorySourceModels[$productId]->setOtherCategoryTemplate($this);

        return $this->otherCategorySourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    /**
     * @return array
     */
    public function getCategorySecondarySource()
    {
        return [
            'mode'      => $this->getData('category_secondary_mode'),
            'value'     => $this->getData('category_secondary_id'),
            'path'      => $this->getData('category_secondary_path'),
            'attribute' => $this->getData('category_secondary_attribute')
        ];
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getStoreCategoryMainSource()
    {
        return [
            'mode'      => $this->getData('store_category_main_mode'),
            'value'     => $this->getData('store_category_main_id'),
            'path'      => $this->getData('store_category_main_path'),
            'attribute' => $this->getData('store_category_main_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getStoreCategorySecondarySource()
    {
        return [
            'mode'      => $this->getData('store_category_secondary_mode'),
            'value'     => $this->getData('store_category_secondary_id'),
            'path'      => $this->getData('store_category_secondary_path'),
            'attribute' => $this->getData('store_category_secondary_attribute')
        ];
    }

    //########################################

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [

            'category_secondary_id'        => 0,
            'category_secondary_path'      => '',
            'category_secondary_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
            'category_secondary_attribute' => '',

            'store_category_main_id'        => 0,
            'store_category_main_path'      => '',
            'store_category_main_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
            'store_category_main_attribute' => '',

            'store_category_secondary_id'        => 0,
            'store_category_secondary_path'      => '',
            'store_category_secondary_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
            'store_category_secondary_attribute' => ''
        ];
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*')
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('template_other_category_id', $this->getId());

        if (is_array($columns) && !empty($columns)) {
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$collection->getData() : (array)$collection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(true, ['id']);
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData, $oldData, $listingsProducts);
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
