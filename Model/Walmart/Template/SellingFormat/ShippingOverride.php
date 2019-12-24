<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride
 */
class ShippingOverride extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const COST_MODE_FREE = 0;
    const COST_MODE_CUSTOM_VALUE = 1;
    const COST_MODE_CUSTOM_ATTRIBUTE = 2;

    const IS_SHIPPING_ALLOWED_REMOVE = 0;
    const IS_SHIPPING_ALLOWED_ADD_OR_OVERRIDE = 1;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride\Source[]
     */
    private $sellingFormatShippingOverrideSourceModels = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\ShippingOverride');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->sellingFormatTemplateModel = null;
        $temp && $this->sellingFormatShippingOverrideSourceModels = [];
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if ($this->sellingFormatTemplateModel === null) {
            $this->sellingFormatTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Walmart_Template_SellingFormat',
                $this->getTemplateSellingFormatId()
            );
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Walmart\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Walmart\Template\SellingFormat $instance)
    {
        $this->sellingFormatTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $id = $magentoProduct->getProductId();

        if (!empty($this->sellingFormatShippingOverrideSourceModels[$id])) {
            return $this->sellingFormatShippingOverrideSourceModels[$id];
        }

        $this->sellingFormatShippingOverrideSourceModels[$id] = $this->modelFactory->getObject(
            'Walmart_Template_SellingFormat_ShippingOverride_Source'
        );

        $this->sellingFormatShippingOverrideSourceModels[$id]->setMagentoProduct($magentoProduct);
        $this->sellingFormatShippingOverrideSourceModels[$id]->setSellingFormatShipingOverrideTemplate($this);

        return $this->sellingFormatShippingOverrideSourceModels[$id];
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateSellingFormatId()
    {
        return (int)$this->getData('template_shipping_override_id');
    }

    // ---------------------------------------

    public function getRegion()
    {
        return $this->getData('region');
    }

    public function getMethod()
    {
        return $this->getData('method');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getIsShippingAllowed()
    {
        return (int)$this->getData('is_shipping_allowed');
    }

    /**
     * @return bool
     */
    public function isShippingAllowedAddOrOverride()
    {
        return $this->getIsShippingAllowed() === self::IS_SHIPPING_ALLOWED_ADD_OR_OVERRIDE;
    }

    /**
     * @return bool
     */
    public function isShippingAllowedRemove()
    {
        return $this->getIsShippingAllowed() === self::IS_SHIPPING_ALLOWED_REMOVE;
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
        return $this->getCostMode() === self::COST_MODE_FREE;
    }

    /**
     * @return bool
     */
    public function isCostModeCustomValue()
    {
        return $this->getCostMode() === self::COST_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isCostModeCustomAttribute()
    {
        return $this->getCostMode() === self::COST_MODE_CUSTOM_ATTRIBUTE;
    }

    //########################################

    public function getCostValue()
    {
        return $this->getData('cost_value');
    }

    public function getCostAttribute()
    {
        return $this->getData('cost_attribute');
    }

    public function getCostAttributes()
    {
        $attributes = [];

        if ($this->isCostModeCustomAttribute()) {
            $attributes[] = $this->getCostAttribute();
        }

        return $attributes;
    }

    //########################################

    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    public function getUsedAttributes()
    {
        return $this->getCostAttributes();
    }

    //########################################
}
