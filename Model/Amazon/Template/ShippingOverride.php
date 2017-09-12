<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ShippingOverride getResource()
 */
class ShippingOverride extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = NULL;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
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

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\ShippingOverride');
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

        return (bool)$this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getCollection()
            ->addFieldToFilter('template_shipping_override_id', $this->getId())
            ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('amazon_template_shippingoverride');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $services = $this->getServices(true);
        foreach ($services as $service) {
            $service->delete();
        }

        $this->marketplaceModel = NULL;

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('amazon_template_shippingoverride');
        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if (is_null($this->marketplaceModel)) {
            $this->marketplaceModel = $this->amazonFactory->getCachedObjectLoaded(
                'Marketplace', $this->getMarketplaceId()
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
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getServices($asObjects = false, array $filters = array())
    {
        $services = $this->getRelatedSimpleItems('Amazon\Template\ShippingOverride\Service',
                                                 'template_shipping_override_id', $asObjects, $filters);

        if ($asObjects) {
            /** @var $service \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service */
            foreach ($services as $service) {
                $service->setShippingOverrideTemplate($this);
            }
        }

        return $services;
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
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
    public function getTrackingAttributes()
    {
        $attributes = array();

        $services = $this->getServices(true);
        foreach ($services as $service) {
            $attributes = array_merge($attributes,$service->getTrackingAttributes());
        }

        return array_unique($attributes);
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        $attributes = array();

        $services = $this->getServices(true);
        foreach ($services as $service) {
            $attributes = array_merge($attributes,$service->getUsedAttributes());
        }

        return array_unique($attributes);
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getDataSnapshot()
    {
        $data = parent::getDataSnapshot();

        $data['services'] = $this->getServices();

        foreach ($data['services'] as &$serviceData) {
            foreach ($serviceData as &$value) {
                !is_null($value) && !is_array($value) && $value = (string)$value;
            }
        }
        unset($value);

        return $data;
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
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('template_shipping_override_id', $this->getId());

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
        $listingsProducts = $this->getAffectedListingsProducts(true, array('id'), true);
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData,$oldData,$listingsProducts);
    }

    //########################################

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