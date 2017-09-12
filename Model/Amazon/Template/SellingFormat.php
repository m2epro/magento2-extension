<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\Template\SellingFormat getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat getResource()
 */
class SellingFormat extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    const QTY_MODIFICATION_MODE_OFF = 0;
    const QTY_MODIFICATION_MODE_ON = 1;

    const QTY_MIN_POSTED_DEFAULT_VALUE = 1;
    const QTY_MAX_POSTED_DEFAULT_VALUE = 100;

    const PRICE_VARIATION_MODE_PARENT   = 1;
    const PRICE_VARIATION_MODE_CHILDREN = 2;

    const BUSINESS_DISCOUNTS_MODE_NONE          = 0;
    const BUSINESS_DISCOUNTS_MODE_TIER          = 1;
    const BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE  = 2;

    const DATE_VALUE      = 0;
    const DATE_ATTRIBUTE  = 1;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat');
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

        return (bool)$this->activeRecordFactory->getObject('Amazon\Listing')
            ->getCollection()
            ->addFieldToFilter('template_selling_format_id', $this->getId())
            ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('template_sellingformat');
        return parent::save();
    }

    public function delete()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('template_sellingformat');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListings($asObjects = false, array $filters = array())
    {
        return $this->getRelatedComponentItems('Listing','template_selling_format_id',$asObjects,$filters);
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessDiscounts($asObjects = false, array $filters = array())
    {
        $businessDiscounts = $this->getRelatedSimpleItems(
            'Amazon\Template\SellingFormat\BusinessDiscount',
            'template_selling_format_id', $asObjects, $filters
        );

        if ($asObjects) {
            /** @var $businessDiscount \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount */
            foreach ($businessDiscounts as $businessDiscount) {
                $businessDiscount->setSellingFormatTemplate($this->getParentObject());
            }
        }

        return $businessDiscounts;
    }

    //########################################

    /**
     * @return int
     */
    public function getQtyMode()
    {
        return (int)$this->getData('qty_mode');
    }

    /**
     * @return bool
     */
    public function isQtyModeProduct()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isQtyModeSingle()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_SINGLE;
    }

    /**
     * @return bool
     */
    public function isQtyModeNumber()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER;
    }

    /**
     * @return bool
     */
    public function isQtyModeAttribute()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isQtyModeProductFixed()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED;
    }

    /**
     * @return int
     */
    public function getQtyNumber()
    {
        return (int)$this->getData('qty_custom_value');
    }

    /**
     * @return array
     */
    public function getQtySource()
    {
        return array(
            'mode'      => $this->getQtyMode(),
            'value'     => $this->getQtyNumber(),
            'attribute' => $this->getData('qty_custom_attribute'),
            'qty_modification_mode'     => $this->getQtyModificationMode(),
            'qty_min_posted_value'      => $this->getQtyMinPostedValue(),
            'qty_max_posted_value'      => $this->getQtyMaxPostedValue(),
            'qty_percentage'            => $this->getQtyPercentage()
        );
    }

    /**
     * @return array
     */
    public function getQtyAttributes()
    {
        $attributes = array();
        $src = $this->getQtySource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getQtyPercentage()
    {
        return (int)$this->getData('qty_percentage');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getQtyModificationMode()
    {
        return (int)$this->getData('qty_modification_mode');
    }

    /**
     * @return bool
     */
    public function isQtyModificationModeOn()
    {
        return $this->getQtyModificationMode() == self::QTY_MODIFICATION_MODE_ON;
    }

    /**
     * @return bool
     */
    public function isQtyModificationModeOff()
    {
        return $this->getQtyModificationMode() == self::QTY_MODIFICATION_MODE_OFF;
    }

    /**
     * @return int
     */
    public function getQtyMinPostedValue()
    {
        return (int)$this->getData('qty_min_posted_value');
    }

    /**
     * @return int
     */
    public function getQtyMaxPostedValue()
    {
        return (int)$this->getData('qty_max_posted_value');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isRegularCustomerAllowed()
    {
        return (bool)$this->getData('is_regular_customer_allowed');
    }

    /**
     * @return bool
     */
    public function isBusinessCustomerAllowed()
    {
        return (bool)$this->getData('is_business_customer_allowed');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRegularPriceMode()
    {
        return (int)$this->getData('regular_price_mode');
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeProduct()
    {
        return $this->getRegularPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeSpecial()
    {
        return $this->getRegularPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeAttribute()
    {
        return $this->getRegularPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE;
    }

    public function getRegularPriceCoefficient()
    {
        return $this->getData('regular_price_coefficient');
    }

    /**
     * @return array
     */
    public function getRegularPriceSource()
    {
        return array(
            'mode'        => $this->getRegularPriceMode(),
            'coefficient' => $this->getRegularPriceCoefficient(),
            'attribute'   => $this->getData('regular_price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getRegularPriceAttributes()
    {
        $attributes = array();
        $src = $this->getRegularPriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRegularMapPriceMode()
    {
        return (int)$this->getData('regular_map_price_mode');
    }

    /**
     * @return bool
     */
    public function isMapPriceModeNone()
    {
        return $this->getRegularMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isRegularMapPriceModeProduct()
    {
        return $this->getRegularMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isRegularMapPriceModeSpecial()
    {
        return $this->getRegularMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isRegularMapPriceModeAttribute()
    {
        return $this->getRegularMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getRegularMapPriceSource()
    {
        return array(
            'mode'        => $this->getRegularMapPriceMode(),
            'attribute'   => $this->getData('regular_map_price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getRegularMapPriceAttributes()
    {
        $attributes = array();
        $src = $this->getRegularMapPriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRegularSalePriceMode()
    {
        return (int)$this->getData('regular_sale_price_mode');
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceModeNone()
    {
        return $this->getRegularSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceModeProduct()
    {
        return $this->getRegularSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceModeSpecial()
    {
        return $this->getRegularSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceModeAttribute()
    {
        return $this->getRegularSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE;
    }

    public function getRegularSalePriceCoefficient()
    {
        return $this->getData('regular_sale_price_coefficient');
    }

    /**
     * @return array
     */
    public function getRegularSalePriceSource()
    {
        return array(
            'mode'        => $this->getRegularSalePriceMode(),
            'coefficient' => $this->getRegularSalePriceCoefficient(),
            'attribute'   => $this->getData('regular_sale_price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getRegularSalePriceAttributes()
    {
        $attributes = array();
        $src = $this->getRegularSalePriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRegularSalePriceStartDateMode()
    {
        return (int)$this->getData('regular_sale_price_start_date_mode');
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceStartDateModeValue()
    {
        return $this->getRegularSalePriceStartDateMode() == self::DATE_VALUE;
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceStartDateModeAttribute()
    {
        return $this->getRegularSalePriceStartDateMode() == self::DATE_ATTRIBUTE;
    }

    public function getRegularSalePriceStartDateValue()
    {
        return $this->getData('regular_sale_price_start_date_value');
    }

    /**
     * @return array
     */
    public function getRegularSalePriceStartDateSource()
    {
        return array(
            'mode'        => $this->getRegularSalePriceStartDateMode(),
            'value'       => $this->getRegularSalePriceStartDateValue(),
            'attribute'   => $this->getData('regular_sale_price_start_date_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getRegularSalePriceStartDateAttributes()
    {
        $attributes = array();
        $src = $this->getRegularSalePriceStartDateSource();

        if ($src['mode'] == self::DATE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRegularSalePriceEndDateMode()
    {
        return (int)$this->getData('regular_sale_price_end_date_mode');
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceEndDateModeValue()
    {
        return $this->getRegularSalePriceEndDateMode() == self::DATE_VALUE;
    }

    /**
     * @return bool
     */
    public function isRegularSalePriceEndDateModeAttribute()
    {
        return $this->getRegularSalePriceEndDateMode() == self::DATE_ATTRIBUTE;
    }

    public function getRegularSalePriceEndDateValue()
    {
        return $this->getData('regular_sale_price_end_date_value');
    }

    /**
     * @return array
     */
    public function getRegularSalePriceEndDateSource()
    {
        return array(
            'mode'        => $this->getRegularSalePriceEndDateMode(),
            'value'       => $this->getRegularSalePriceEndDateValue(),
            'attribute'   => $this->getData('regular_sale_price_end_date_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getRegularSalePriceEndDateAttributes()
    {
        $attributes = array();
        $src = $this->getRegularSalePriceEndDateSource();

        if ($src['mode'] == self::DATE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRegularPriceVariationMode()
    {
        return (int)$this->getData('regular_price_variation_mode');
    }

    /**
     * @return bool
     */
    public function isRegularPriceVariationModeParent()
    {
        return $this->getRegularPriceVariationMode() == self::PRICE_VARIATION_MODE_PARENT;
    }

    /**
     * @return bool
     */
    public function isRegularPriceVariationModeChildren()
    {
        return $this->getRegularPriceVariationMode() == self::PRICE_VARIATION_MODE_CHILDREN;
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getRegularPriceVatPercent()
    {
        return (float)$this->getData('regular_price_vat_percent');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getBusinessPriceMode()
    {
        return (int)$this->getData('business_price_mode');
    }

    /**
     * @return bool
     */
    public function isBusinessPriceModeProduct()
    {
        return $this->getBusinessPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isBusinessPriceModeSpecial()
    {
        return $this->getBusinessPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isBusinessPriceModeAttribute()
    {
        return $this->getRegularPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE;
    }

    public function getBusinessPriceCoefficient()
    {
        return $this->getData('business_price_coefficient');
    }

    /**
     * @return array
     */
    public function getBusinessPriceSource()
    {
        return array(
            'mode'        => $this->getBusinessPriceMode(),
            'coefficient' => $this->getBusinessPriceCoefficient(),
            'attribute'   => $this->getData('business_price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getBusinessPriceAttributes()
    {
        $attributes = array();
        $src = $this->getBusinessPriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getBusinessPriceVariationMode()
    {
        return (int)$this->getData('business_price_variation_mode');
    }

    /**
     * @return bool
     */
    public function isBusinessPriceVariationModeParent()
    {
        return $this->getBusinessPriceVariationMode() == self::PRICE_VARIATION_MODE_PARENT;
    }

    /**
     * @return bool
     */
    public function isBusinessPriceVariationModeChildren()
    {
        return $this->getBusinessPriceVariationMode() == self::PRICE_VARIATION_MODE_CHILDREN;
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getBusinessPriceVatPercent()
    {
        return (float)$this->getData('business_price_vat_percent');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getBusinessDiscountsMode()
    {
        return (int)$this->getData('business_discounts_mode');
    }

    /**
     * @return bool
     */
    public function isBusinessDiscountsModeNone()
    {
        return $this->getBusinessDiscountsMode() == self::BUSINESS_DISCOUNTS_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isBusinessDiscountsModeTier()
    {
        return $this->getBusinessDiscountsMode() == self::BUSINESS_DISCOUNTS_MODE_TIER;
    }

    /**
     * @return bool
     */
    public function isBusinessDiscountsModeCustomValue()
    {
        return $this->getBusinessDiscountsMode() == self::BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE;
    }

    // ---------------------------------------

    public function getBusinessDiscountsTierCoefficient()
    {
        return $this->getData('business_discounts_tier_coefficient');
    }

    /**
     * @return int|null
     */
    public function getBusinessDiscountsTierCustomerGroupId()
    {
        return $this->getData('business_discounts_tier_customer_group_id');
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getBusinessDiscountsSource()
    {
        return array(
            'mode'                   => $this->getBusinessDiscountsMode(),
            'tier_customer_group_id' => $this->getBusinessDiscountsTierCustomerGroupId(),
        );
    }

    //########################################

    /**
     * @return bool
     */
    public function usesConvertiblePrices()
    {
        $attributeHelper = $this->getHelper('Magento\Attribute');

        $isPriceConvertEnabled = (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/magento/attribute/', 'price_type_converting'
        );

        if ($this->isRegularCustomerAllowed()) {
            if ($this->isRegularPriceModeProduct() || $this->isRegularPriceModeSpecial()) {
                return true;
            }

            if ($this->isRegularSalePriceModeProduct() || $this->isRegularSalePriceModeSpecial()) {
                return true;
            }

            if ($this->isRegularMapPriceModeProduct() || $this->isRegularMapPriceModeSpecial()) {
                return true;
            }

            if ($isPriceConvertEnabled) {
                if ($this->isRegularPriceModeAttribute() &&
                    $attributeHelper->isAttributeInputTypePrice($this->getData('regular_price_custom_attribute'))) {
                    return true;
                }

                if ($this->isRegularSalePriceModeAttribute() &&
                    $attributeHelper->isAttributeInputTypePrice($this->getData('regular_sale_price_custom_attribute'))
                ) {
                    return true;
                }

                if ($this->isRegularMapPriceModeAttribute() &&
                    $attributeHelper->isAttributeInputTypePrice($this->getData('regular_map_price_custom_attribute'))) {
                    return true;
                }
            }
        }

        if ($this->isBusinessCustomerAllowed()) {
            if ($this->isBusinessPriceModeProduct() || $this->isBusinessPriceModeSpecial()) {
                return true;
            }

            if ($isPriceConvertEnabled) {
                if ($this->isBusinessPriceModeAttribute() &&
                    $attributeHelper->isAttributeInputTypePrice($this->getData('business_price_custom_attribute'))) {
                    return true;
                }
            }

            foreach ($this->getBusinessDiscounts(true) as $businessDiscount) {
                if ($businessDiscount->isModeProduct() || $businessDiscount->isModeSpecial()) {
                    return true;
                }

                if ($isPriceConvertEnabled && $businessDiscount->isModeAttribute() &&
                    $attributeHelper->isAttributeInputTypePrice($businessDiscount->getAttribute())) {
                    return true;
                }
            }
        }

        return false;
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
        $attributes = array_merge(
            $this->getQtyAttributes(),
            $this->getRegularPriceAttributes(),
            $this->getRegularMapPriceAttributes(),
            $this->getRegularSalePriceAttributes(),
            $this->getRegularSalePriceStartDateAttributes(),
            $this->getRegularSalePriceEndDateAttributes(),
            $this->getBusinessPriceAttributes()
        );

        $businessDiscounts = $this->getBusinessDiscounts(true);
        foreach ($businessDiscounts as $businessDiscount) {
            $attributes = array_merge($attributes,$businessDiscount->getUsedAttributes());
        }

        return array_unique($attributes);
    }

    //########################################

    /**
     * @return array
     */
    public function getDataSnapshot()
    {
        $data = parent::getDataSnapshot();

        $data['business_discounts'] = $this->getBusinessDiscounts();

        foreach ($data['business_discounts'] as &$businessDiscount) {
            foreach ($businessDiscount as &$value) {
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
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $listingCollection */
        $listingCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing'
        )->getCollection();
        $listingCollection->addFieldToFilter('template_selling_format_id', $this->getId());
        $listingCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingCollection->getSelect()->columns('id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product'
        )->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id',array('in' => $listingCollection->getSelect()));

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