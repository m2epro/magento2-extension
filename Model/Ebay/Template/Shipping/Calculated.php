<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Shipping;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated
 */
class Calculated extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const MEASUREMENT_SYSTEM_ENGLISH = 1;
    const MEASUREMENT_SYSTEM_METRIC  = 2;

    const PACKAGE_SIZE_CUSTOM_VALUE     = 1;
    const PACKAGE_SIZE_CUSTOM_ATTRIBUTE = 2;

    const DIMENSION_NONE               = 0;
    const DIMENSION_CUSTOM_VALUE       = 1;
    const DIMENSION_CUSTOM_ATTRIBUTE   = 2;

    const WEIGHT_NONE                   = 0;
    const WEIGHT_CUSTOM_VALUE           = 1;
    const WEIGHT_CUSTOM_ATTRIBUTE       = 2;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated\Source[]
     */
    private $shippingCalculatedSourceModels = [];

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Calculated');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_shipping_calculated');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->shippingTemplateModel = null;
        $temp && $this->shippingCalculatedSourceModels = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_shipping_calculated');

        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    public function getShippingTemplate()
    {
        if ($this->shippingTemplateModel === null) {
            $this->shippingTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Shipping',
                $this->getId()
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
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->shippingCalculatedSourceModels[$productId])) {
            return $this->shippingCalculatedSourceModels[$productId];
        }

        $this->shippingCalculatedSourceModels[$productId] = $this->modelFactory->getObject(
            'Ebay_Template_Shipping_Calculated_Source'
        );
        $this->shippingCalculatedSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->shippingCalculatedSourceModels[$productId]->setShippingCalculatedTemplate($this);

        return $this->shippingCalculatedSourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getMeasurementSystem()
    {
        return (int)$this->getData('measurement_system');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMeasurementSystemMetric()
    {
        return $this->getMeasurementSystem() == self::MEASUREMENT_SYSTEM_METRIC;
    }

    /**
     * @return bool
     */
    public function isMeasurementSystemEnglish()
    {
        return $this->getMeasurementSystem() == self::MEASUREMENT_SYSTEM_ENGLISH;
    }

    //########################################

    /**
     * @return array
     */
    public function getPackageSizeSource()
    {
        return [
            'mode'      => (int)$this->getData('package_size_mode'),
            'value'     => $this->getData('package_size_value'),
            'attribute' => $this->getData('package_size_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getPackageSizeAttributes()
    {
        $attributes = [];
        $src = $this->getPackageSizeSource();

        if ($src['mode'] == self::PACKAGE_SIZE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getDimensionSource()
    {
        return [
            'mode' => (int)$this->getData('dimension_mode'),

            'width_value'  => $this->getData('dimension_width_value'),
            'width_attribute'  => $this->getData('dimension_width_attribute'),

            'length_value' => $this->getData('dimension_length_value'),
            'length_attribute' => $this->getData('dimension_length_attribute'),

            'depth_value'  => $this->getData('dimension_depth_value'),
            'depth_attribute'  => $this->getData('dimension_depth_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getDimensionAttributes()
    {
        $attributes = [];
        $src = $this->getDimensionSource();

        if ($src['mode'] == self::DIMENSION_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['width_attribute'];
            $attributes[] = $src['length_attribute'];
            $attributes[] = $src['depth_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getWeightSource()
    {
        return [
            'mode' => (int)$this->getData('weight_mode'),
            'major' => $this->getData('weight_major'),
            'minor' => $this->getData('weight_minor'),
            'attribute' => $this->getData('weight_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getWeightAttributes()
    {
        $attributes = [];
        $src = $this->getWeightSource();

        if ($src['mode'] == self::WEIGHT_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    //########################################

    /**
     * @return float
     */
    public function getLocalHandlingCost()
    {
        return (float)$this->getData('local_handling_cost');
    }

    /**
     * @return float
     */
    public function getInternationalHandlingCost()
    {
        return (float)$this->getData('international_handling_cost');
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
        return array_unique(array_merge(
            $this->getPackageSizeAttributes(),
            $this->getDimensionAttributes(),
            $this->getWeightAttributes()
        ));
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
