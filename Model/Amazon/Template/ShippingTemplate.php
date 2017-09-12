<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ShippingTemplate getResource()
 */
class ShippingTemplate extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    protected $amazonFactory;

    const TEMPLATE_NAME_VALUE     = 1;
    const TEMPLATE_NAME_ATTRIBUTE = 2;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate\Source[]
     */
    private $shippingTemplateSourceModels = [];

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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\ShippingTemplate');
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
            ->addFieldToFilter('template_shipping_template_id', $this->getId())
            ->getSize();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $id = $magentoProduct->getProductId();

        if (!empty($this->shippingTemplateSourceModels[$id])) {
            return $this->shippingTemplateSourceModels[$id];
        }

        $this->shippingTemplateSourceModels[$id] = $this->modelFactory->getObject(
            'Amazon\Template\ShippingTemplate\Source'
        );

        $this->shippingTemplateSourceModels[$id]->setMagentoProduct($magentoProduct);
        $this->shippingTemplateSourceModels[$id]->setShippingTemplate($this);

        return $this->shippingTemplateSourceModels[$id];
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    public function getTemplateNameMode()
    {
        return (int)$this->getData('template_name_mode');
    }

    public function isTemplateNameModeValue()
    {
        return $this->getTemplateNameMode() == self::TEMPLATE_NAME_VALUE;
    }

    public function isTemplateNameModeAttribute()
    {
        return $this->getTemplateNameMode() == self::TEMPLATE_NAME_ATTRIBUTE;
    }

    // ---------------------------------------

    public function getTemplateNameValue()
    {
        return $this->getData('template_name_value');
    }

    public function getTemplateNameAttribute()
    {
        return $this->getData('template_name_attribute');
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

    public function getTemplateNameAttributes()
    {
        $attributes = array();

        if ($this->isTemplateNameModeAttribute()) {
            $attributes[] = $this->getTemplateNameAttribute();
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_unique(
            $this->getTemplateNameAttributes()
        );
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
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('template_shipping_template_id', $this->getId());

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

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('amazon_template_shippingtemplate');

        return parent::save();
    }

    public function delete()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('amazon_template_shippingtemplate');

        return parent::delete();
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