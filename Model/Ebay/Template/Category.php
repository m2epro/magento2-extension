<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category getResource()
 */
namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category
 */
class Category extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const CATEGORY_MODE_NONE       = 0;
    const CATEGORY_MODE_EBAY       = 1;
    const CATEGORY_MODE_ATTRIBUTE  = 2;

    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category\Source[]
     */
    private $categorySourceModels = [];

    protected $ebayFactory;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category');
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
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_category');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $specifics = $this->getSpecifics(true);
        foreach ($specifics as $specific) {
            $specific->delete();
        }

        $this->marketplaceModel = null;
        $this->categorySourceModels = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_category');

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
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->categorySourceModels[$productId])) {
            return $this->categorySourceModels[$productId];
        }

        $this->categorySourceModels[$productId] = $this->modelFactory->getObject('Ebay_Template_Category_Source');
        $this->categorySourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->categorySourceModels[$productId]->setCategoryTemplate($this);

        return $this->categorySourceModels[$productId];
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Ebay\Template\Category\Specific[]
     */
    public function getSpecifics($asObjects = false, array $filters = [])
    {
        $specifics = $this->getRelatedSimpleItems(
            'Ebay_Template_Category_Specific',
            'template_category_id',
            $asObjects,
            $filters
        );

        if ($asObjects) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Specific $specific */
            foreach ($specifics as $specific) {
                $specific->setCategoryTemplate($this);
            }
        }

        return $specifics;
    }

    //########################################

    /**
     * @return int
     */
    public function getCategoryMainId()
    {
        return (int)$this->getData('category_main_id');
    }

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
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
    public function getCategoryMainSource()
    {
        return [
            'mode'      => $this->getData('category_main_mode'),
            'value'     => $this->getData('category_main_id'),
            'path'      => $this->getData('category_main_path'),
            'attribute' => $this->getData('category_main_attribute')
        ];
    }

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @param bool $withId
     * @return string
     */
    public function getCategoryPath(\Ess\M2ePro\Model\Listing $listing, $withId = true)
    {
        $src = $this->getCategoryMainSource();

        $data = [
            'category_main_id'        => $src['value'],
            'category_main_mode'      => $src['mode'],
            'category_main_path'      => $src['path'],
            'category_main_attribute' => $src['attribute'],
        ];

        $this->getHelper('Component_Ebay_Category')->fillCategoriesPaths($data, $listing);

        $path = $data['category_main_path'];
        if ($withId && $src['mode'] == self::CATEGORY_MODE_EBAY) {
            $path .= ' ('.$src['value'].')';
        }

        return $path;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        $attributes = [];

        foreach ($this->getSpecifics(true) as $specific) {
            $attributes = array_merge($attributes, $specific->getTrackingAttributes());
        }

        return array_unique($attributes);
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        $usedAttributes = [];

        $categoryMainSrc = $this->getCategoryMainSource();

        if ($categoryMainSrc['mode'] == self::CATEGORY_MODE_ATTRIBUTE) {
            $usedAttributes[] = $categoryMainSrc['attribute'];
        }

        foreach ($this->getSpecifics(true) as $specificModel) {
            $usedAttributes = array_merge($usedAttributes, $specificModel->getUsedAttributes());
        }

        return array_values(array_unique($usedAttributes));
    }

    //########################################

    public function getDataSnapshot()
    {
        $data = parent::getDataSnapshot();
        $data['specifics'] = $this->getSpecifics();

        foreach ($data['specifics'] as &$specificData) {
            foreach ($specificData as &$value) {
                $value !== null && !is_array($value) && $value = (string)$value;
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [
            'category_main_id' => 0,
            'category_main_path' => '',
            'category_main_mode' => self::CATEGORY_MODE_EBAY,
            'category_main_attribute' => ''
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
        $collection->addFieldToFilter('template_category_id', $this->getId());

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

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
