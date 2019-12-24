<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion
 */
class Promotion extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const START_DATE_MODE_VALUE = 1;
    const START_DATE_MODE_ATTRIBUTE = 2;

    const END_DATE_MODE_VALUE = 1;
    const END_DATE_MODE_ATTRIBUTE = 2;

    const PRICE_MODE_PRODUCT = 1;
    const PRICE_MODE_SPECIAL = 2;
    const PRICE_MODE_ATTRIBUTE = 3;

    const COMPARISON_PRICE_MODE_PRODUCT = 1;
    const COMPARISON_PRICE_MODE_SPECIAL = 2;
    const COMPARISON_PRICE_MODE_ATTRIBUTE = 3;

    const TYPE_REDUCED = 'reduced';
    const TYPE_CLEARANCE = 'clearance';

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion\Source[]
     */
    private $sellingFormatPromotionSourceModels = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->sellingFormatTemplateModel = null;
        $temp && $this->sellingFormatPromotionSourceModels = [];
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
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $id = $magentoProduct->getProductId();

        if (!empty($this->sellingFormatPromotionSourceModels[$id])) {
            return $this->sellingFormatPromotionSourceModels[$id];
        }

        $this->sellingFormatPromotionSourceModels[$id] = $this->modelFactory->getObject(
            'Walmart_Template_SellingFormat_Promotion_Source'
        );

        $this->sellingFormatPromotionSourceModels[$id]->setMagentoProduct($magentoProduct);
        $this->sellingFormatPromotionSourceModels[$id]->setSellingFormatPromotion($this);

        return $this->sellingFormatPromotionSourceModels[$id];
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

    public function getStartDateMode()
    {
        return (int)$this->getData('start_date_mode');
    }

    public function isStartDateModeValue()
    {
        return $this->getStartDateMode() == self::START_DATE_MODE_VALUE;
    }

    public function isStartDateModeAttribute()
    {
        return $this->getStartDateMode() == self::START_DATE_MODE_ATTRIBUTE;
    }

    // ---------------------------------------

    public function getStartDateValue()
    {
        return $this->getData('start_date_value');
    }

    public function getStartDateAttribute()
    {
        return $this->getData('start_date_attribute');
    }

    // ---------------------------------------

    public function getStartDateAttributes()
    {
        $attributes = [];

        if ($this->isStartDateModeAttribute()) {
            $attributes[] = $this->getStartDateAttribute();
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getEndDateMode()
    {
        return (int)$this->getData('end_date_mode');
    }

    public function isEndDateModeValue()
    {
        return $this->getEndDateMode() == self::END_DATE_MODE_VALUE;
    }

    public function isEndDateModeAttribute()
    {
        return $this->getEndDateMode() == self::END_DATE_MODE_ATTRIBUTE;
    }

    // ---------------------------------------

    public function getEndDateValue()
    {
        return $this->getData('end_date_value');
    }

    public function getEndDateAttribute()
    {
        return $this->getData('end_date_attribute');
    }

    // ---------------------------------------

    public function getEndDateAttributes()
    {
        $attributes = [];

        if ($this->isEndDateModeAttribute()) {
            $attributes[] = $this->getEndDateAttribute();
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getPriceMode()
    {
        return (int)$this->getData('price_mode');
    }

    public function isPriceModeProduct()
    {
        return $this->getPriceMode() == self::PRICE_MODE_PRODUCT;
    }

    public function isPriceModeSpecial()
    {
        return $this->getPriceMode() == self::PRICE_MODE_SPECIAL;
    }

    public function isPriceModeAttribute()
    {
        return $this->getPriceMode() == self::PRICE_MODE_ATTRIBUTE;
    }

    // ---------------------------------------

    public function getPriceAttribute()
    {
        return $this->getData('price_attribute');
    }

    public function getPriceCoefficient()
    {
        return $this->getData('price_coefficient');
    }

    // ---------------------------------------

    public function getPriceAttributes()
    {
        $attributes = [];

        if ($this->isPriceModeAttribute()) {
            $attributes[] = $this->getPriceAttribute();
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getPriceSource()
    {
        return [
            'mode'        => $this->getPriceMode(),
            'coefficient' => $this->getPriceCoefficient(),
            'attribute'   => $this->getPriceAttribute(),
        ];
    }

    // ---------------------------------------

    public function getComparisonPriceMode()
    {
        return (int)$this->getData('comparison_price_mode');
    }

    public function isComparisonPriceModeProduct()
    {
        return $this->getComparisonPriceMode() == self::COMPARISON_PRICE_MODE_PRODUCT;
    }

    public function isComparisonPriceModeSpecial()
    {
        return $this->getComparisonPriceMode() == self::COMPARISON_PRICE_MODE_SPECIAL;
    }

    public function isComparisonPriceModeAttribute()
    {
        return $this->getComparisonPriceMode() == self::COMPARISON_PRICE_MODE_ATTRIBUTE;
    }

    // ---------------------------------------

    public function getComparisonPriceAttribute()
    {
        return $this->getData('comparison_price_attribute');
    }

    public function getComparisonPriceCoefficient()
    {
        return $this->getData('comparison_price_coefficient');
    }

    // ---------------------------------------

    public function getComparisonPriceSource()
    {
        return [
            'mode'        => $this->getComparisonPriceMode(),
            'coefficient' => $this->getComparisonPriceCoefficient(),
            'attribute'   => $this->getComparisonPriceAttribute(),
        ];
    }

    // ---------------------------------------

    public function getComparisonPriceAttributes()
    {
        $attributes = [];

        if ($this->isComparisonPriceModeAttribute()) {
            $attributes[] = $this->getComparisonPriceAttribute();
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getType()
    {
        return $this->getData('type');
    }

    public function isTypeReduced()
    {
        return $this->getType() == self::TYPE_REDUCED;
    }

    public function isTypeClearance()
    {
        return $this->getType() == self::TYPE_CLEARANCE;
    }

    //########################################

    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    public function getUsedAttributes()
    {
        return array_merge(
            $this->getPriceAttributes(),
            $this->getComparisonPriceAttributes(),
            $this->getStartDateAttributes(),
            $this->getEndDateAttributes()
        );
    }

    //########################################
}
