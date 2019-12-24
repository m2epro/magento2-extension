<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category getResource()
 */

namespace Ess\M2ePro\Model\Walmart\Template;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Category
 */
class Category extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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

        $this->walmartFactory = $walmartFactory;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        $collection = $this->activeRecordFactory->getObject('Walmart\Listing')->getCollection();
        $collection->getSelect()
            ->where("main_table.auto_global_adding_category_template_id = {$this->getId()} OR
                     main_table.auto_website_adding_category_template_id = {$this->getId()}");

        return (bool)$this->activeRecordFactory->getObject('Walmart_Listing_Product')
                                               ->getCollection()
                                               ->addFieldToFilter('template_category_id', $this->getId())
                                               ->getSize() ||
               (bool)$this->activeRecordFactory->getObject('Walmart_Listing_Auto_Category_Group')
                                               ->getCollection()
                                               ->addFieldToFilter('adding_category_template_id', $this->getId())
                                               ->getSize() ||
               (bool)$collection->getSize();
    }

    public function isLockedForCategoryChange()
    {
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('second_table.template_category_id', $this->getId());

        if ($collection->getSize() <= 0) {
            return false;
        }

        // todo check not empty variation_group_id or locked for list

        return false;
    }

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        foreach ($this->getSpecifics(true) as $specific) {
            $specific->delete();
        }

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->walmartFactory->getCachedObjectLoaded(
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

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Walmart\Template\Category\Specific[]
     */
    public function getSpecifics($asObjects = false, array $filters = [])
    {
        $specifics = $this->getRelatedSimpleItems(
            'Walmart_Template_Category_Specific',
            'template_category_id',
            $asObjects,
            $filters
        );
        if ($asObjects) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\Category\Specific $specific */
            foreach ($specifics as $specific) {
                $specific->setDescriptionTemplate($this->getParentObject());
            }
        }

        if (!$asObjects) {
            foreach ($specifics as &$specific) {
                $specific['attributes'] = (array)$this->getHelper('Data')->jsonDecode($specific['attributes']);
            }
        }

        return $specifics;
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    public function getProductDataNick()
    {
        return $this->getData('product_data_nick');
    }

    public function getCategoryPath()
    {
        return $this->getData('category_path');
    }

    public function getBrowsenodeId()
    {
        return $this->getData('browsenode_id');
    }

    //########################################

    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    public function getUsedAttributes()
    {
        $attributes = [];

        foreach ($this->getSpecifics(true) as $specific) {
            $attributes = array_merge($attributes, $specific->getUsedAttributes());
        }

        return array_unique($attributes);
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @param bool $onlyPhysicalUnits
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*', $onlyPhysicalUnits = false)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')
                                                         ->getCollection();
        $listingProductCollection->addFieldToFilter('template_category_id', $this->getId());

        if ($onlyPhysicalUnits) {
            $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        }

        if (is_array($columns) && !empty($columns)) {
            $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingProductCollection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$listingProductCollection->getData() : (array)$listingProductCollection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(true, ['id'], true);
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData, $oldData, $listingsProducts);
    }

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################
}
