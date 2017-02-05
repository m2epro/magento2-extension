<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Shipping;

class Service extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const SHIPPING_TYPE_LOCAL         = 0;
    const SHIPPING_TYPE_INTERNATIONAL = 1;

    const COST_MODE_FREE             = 0;
    const COST_MODE_CUSTOM_VALUE     = 1;
    const COST_MODE_CUSTOM_ATTRIBUTE = 2;
    const COST_MODE_CALCULATED       = 3;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Service\Source[]
     */
    private $shippingServiceSourceModels = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Service');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->shippingTemplateModel = NULL;
        $temp && $this->shippingServiceSourceModels = array();
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    public function getShippingTemplate()
    {
        if (is_null($this->shippingTemplateModel)) {
            $this->shippingTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay\Template\Shipping', $this->getTemplateShippingId()
            );
        }

        return $this->shippingTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Shipping $instance
     */
    public function setShippingTemplate(\Ess\M2ePro\Model\Ebay\Template\Shipping $instance)
    {
         $this->shippingTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Service\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->shippingServiceSourceModels[$productId])) {
            return $this->shippingServiceSourceModels[$productId];
        }

        $this->shippingServiceSourceModels[$productId] = $this->modelFactory->getObject(
            'Ebay\Template\Shipping\Service\Source'
        );
        $this->shippingServiceSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->shippingServiceSourceModels[$productId]->setShippingServiceTemplate($this);

        return $this->shippingServiceSourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateShippingId()
    {
        return (int)$this->getData('template_shipping_id');
    }

    /**
     * @return array
     */
    public function getLocations()
    {
        return $this->getHelper('Data')->jsonDecode($this->getData('locations'));
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return (int)$this->getData('priority');
    }

    //########################################

    /**
     * @return int
     */
    public function getShippingType()
    {
        return (int)$this->getData('shipping_type');
    }

    public function getShippingValue()
    {
        return $this->getData('shipping_value');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isShippingTypeLocal()
    {
        return $this->getShippingType() == self::SHIPPING_TYPE_LOCAL;
    }

    /**
     * @return bool
     */
    public function isShippingTypeInternational()
    {
        return $this->getShippingType() == self::SHIPPING_TYPE_INTERNATIONAL;
    }

    //########################################

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

    public function getCostAdditionalValue()
    {
        return $this->getData('cost_additional_value');
    }

    public function getCostSurchargeValue()
    {
        return $this->getData('cost_surcharge_value');
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getCostAttributes()
    {
        $attributes = array();

        if ($this->isCostModeCustomAttribute()) {
            $attributes[] = $this->getCostValue();
        }

        return $attributes;
    }

    /**
     * @return array
     */
    public function getCostAdditionalAttributes()
    {
        $attributes = array();

        if ($this->isCostModeCustomAttribute()) {
            $attributes[] = $this->getCostAdditionalValue();
        }

        return $attributes;
    }

    /**
     * @return array
     */
    public function getCostSurchargeAttributes()
    {
        $attributes = array();

        if ($this->isCostModeCustomAttribute()) {
            $attributes[] = $this->getCostSurchargeValue();
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
        return array_unique(array_merge(
            $this->getCostAttributes(),
            $this->getCostAdditionalAttributes(),
            $this->getCostSurchargeAttributes()
        ));
    }

    //########################################
}