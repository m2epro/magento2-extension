<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping getResource()
 */
class Shipping extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    protected $amazonFactory;

    const TEMPLATE_NAME_VALUE     = 1;
    const TEMPLATE_NAME_ATTRIBUTE = 2;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Shipping\Source[]
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
    ) {
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping');
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

        return (bool)$this->activeRecordFactory->getObject('Amazon_Listing')
                ->getCollection()
                ->addFieldToFilter('template_shipping_id', $this->getId())
                ->getSize() ||
                (bool)$this->activeRecordFactory->getObject('Amazon_Listing_Product')
                ->getCollection()
                ->addFieldToFilter('template_shipping_id', $this->getId())
                ->getSize();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $id = $magentoProduct->getProductId();

        if (!empty($this->shippingTemplateSourceModels[$id])) {
            return $this->shippingTemplateSourceModels[$id];
        }

        $this->shippingTemplateSourceModels[$id] = $this->modelFactory->getObject(
            'Amazon_Template_Shipping_Source'
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
        $attributes = [];

        if ($this->isTemplateNameModeAttribute()) {
            $attributes[] = $this->getTemplateNameAttribute();
        }

        return $attributes;
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('amazon_template_shipping');

        return parent::save();
    }

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('amazon_template_shipping');

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
