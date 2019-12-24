<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ShippingOverride;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service
 */
class Service extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const TYPE_EXCLUSIVE   = 0;
    const TYPE_ADDITIVE    = 1;
    const TYPE_RESTRICTIVE = 2;

    const COST_MODE_FREE             = 0;
    const COST_MODE_CUSTOM_VALUE     = 1;
    const COST_MODE_CUSTOM_ATTRIBUTE = 2;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride
     */
    private $shippingOverrideTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service\Source[]
     */
    private $shippingOverrideServiceSourceModels = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\ShippingOverride\Service');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->shippingOverrideTemplateModel = null;
        $temp && $this->shippingOverrideServiceSourceModels = [];
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingOverride
     */
    public function getShippingOverrideTemplate()
    {
        if ($this->shippingOverrideTemplateModel === null) {
            $this->shippingOverrideTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Amazon_Template_ShippingOverride',
                $this->getTemplateShippingOverrideId(),
                null
            );
        }

        return $this->shippingOverrideTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\ShippingOverride $instance
     */
    public function setShippingOverrideTemplate(\Ess\M2ePro\Model\Amazon\Template\ShippingOverride $instance)
    {
         $this->shippingOverrideTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $id = $magentoProduct->getProductId();

        if (!empty($this->shippingOverrideServiceSourceModels[$id])) {
            return $this->shippingOverrideServiceSourceModels[$id];
        }

        $this->shippingOverrideServiceSourceModels[$id] =
            $this->modelFactory->getObject('Amazon_Template_ShippingOverride_Service_Source');

        $this->shippingOverrideServiceSourceModels[$id]->setMagentoProduct($magentoProduct);
        $this->shippingOverrideServiceSourceModels[$id]->setShippingOverrideServiceTemplate($this);

        return $this->shippingOverrideServiceSourceModels[$id];
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateShippingOverrideId()
    {
        return (int)$this->getData('template_shipping_override_id');
    }

    // ---------------------------------------

    public function getService()
    {
        return $this->getData('service');
    }

    public function getLocation()
    {
        return $this->getData('location');
    }

    public function getOption()
    {
        return $this->getData('option');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getType()
    {
        return (int)$this->getData('type');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isTypeExclusive()
    {
        return $this->getType() == self::TYPE_EXCLUSIVE;
    }

    /**
     * @return bool
     */
    public function isTypeAdditive()
    {
        return $this->getType() == self::TYPE_ADDITIVE;
    }

    /**
     * @return bool
     */
    public function isTypeRestrictive()
    {
        return $this->getType() == self::TYPE_RESTRICTIVE;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getCostMode()
    {
        return (int)$this->getData('cost_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isCostModeFree()
    {
        return $this->getCostMode() == self::COST_MODE_FREE;
    }

    /**
     * @return bool
     */
    public function isCostModeCustomValue()
    {
        return $this->getCostMode() == self::COST_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isCostModeCustomAttribute()
    {
        return $this->getCostMode() == self::COST_MODE_CUSTOM_ATTRIBUTE;
    }

    //########################################

    public function getCostValue()
    {
        return $this->getData('cost_value');
    }

    /**
     * @return array
     */
    public function getCostAttributes()
    {
        $attributes = [];

        if ($this->isCostModeCustomAttribute()) {
            $attributes[] = $this->getCostValue();
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_unique(
            $this->getCostAttributes()
        );
    }

    //########################################
}
