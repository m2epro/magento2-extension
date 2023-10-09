<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\Template\SellingFormat getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat getResource()
 */
class SellingFormat extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public const QTY_MODIFICATION_MODE_OFF = 0;
    public const QTY_MODIFICATION_MODE_ON = 1;

    public const QTY_MIN_POSTED_DEFAULT_VALUE = 1;
    public const QTY_MAX_POSTED_DEFAULT_VALUE = 100;

    public const PRICE_VARIATION_MODE_PARENT = 1;
    public const PRICE_VARIATION_MODE_CHILDREN = 2;

    public const BUSINESS_DISCOUNTS_MODE_NONE = 0;
    public const BUSINESS_DISCOUNTS_MODE_TIER = 1;
    public const BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE = 2;

    public const DATE_VALUE = 0;
    public const DATE_ATTRIBUTE = 1;

    public const PRICE_TYPE_REGULAR = 'regular_price';
    public const PRICE_TYPE_REGULAR_SALE = 'regular_sale_price';
    public const PRICE_TYPE_BUSINESS = 'business_price';
    public const PRICE_TYPE_BUSINESS_DISCOUNTS_TIER = 'business_discounts_tier';

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
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
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->moduleConfiguration = $moduleConfiguration;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat::class);
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
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_sellingformat');

        return parent::save();
    }

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_sellingformat');

        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     *
     * @return array|\Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessDiscounts($asObjects = false, array $filters = [])
    {
        $businessDiscounts = $this->getRelatedSimpleItems(
            'Amazon_Template_SellingFormat_BusinessDiscount',
            'template_selling_format_id',
            $asObjects,
            $filters
        );

        if ($asObjects) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount $businessDiscount */
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
        return [
            'mode' => $this->getQtyMode(),
            'value' => $this->getQtyNumber(),
            'attribute' => $this->getData('qty_custom_attribute'),
            'qty_modification_mode' => $this->getQtyModificationMode(),
            'qty_min_posted_value' => $this->getQtyMinPostedValue(),
            'qty_max_posted_value' => $this->getQtyMaxPostedValue(),
            'qty_percentage' => $this->getQtyPercentage(),
        ];
    }

    /**
     * @return array
     */
    public function getQtyAttributes()
    {
        $attributes = [];
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

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularPriceModifier(): array
    {
        $value = $this->getData('regular_price_modifier');
        if (empty($value)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($value) ?: [];
    }

    public function getPriceRoundOption(): int
    {
        return (int)$this->getData('price_rounding_option');
    }

    /**
     * @return array
     */
    public function getRegularPriceSource(): array
    {
        return [
            'mode' => $this->getRegularPriceMode(),
            'attribute' => $this->getData('regular_price_custom_attribute'),
        ];
    }

    /**
     * @return array{mode: int, attribute: string}
     */
    public function getRegularListPriceSource(): array
    {
        return [
            'mode' => $this->getRegularListPriceMode(),
            'attribute' => $this->getRegularListPriceCustomAttribute(),
        ];
    }

    public function getRegularListPriceMode(): int
    {
        return (int)$this->getData('regular_list_price_mode');
    }

    public function getRegularListPriceCustomAttribute(): string
    {
        return (string)$this->getData('regular_list_price_custom_attribute');
    }

    /**
     * @return array
     */
    public function getRegularPriceAttributes()
    {
        $attributes = [];
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
        return [
            'mode' => $this->getRegularMapPriceMode(),
            'attribute' => $this->getData('regular_map_price_custom_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getRegularMapPriceAttributes()
    {
        $attributes = [];
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

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularSalePriceModifier(): array
    {
        $value = $this->getData('regular_sale_price_modifier');
        if (empty($value)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($value) ?: [];
    }

    /**
     * @return array
     */
    public function getRegularSalePriceSource(): array
    {
        return [
            'mode' => $this->getRegularSalePriceMode(),
            'attribute' => $this->getData('regular_sale_price_custom_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getRegularSalePriceAttributes()
    {
        $attributes = [];
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
        return [
            'mode' => $this->getRegularSalePriceStartDateMode(),
            'value' => $this->getRegularSalePriceStartDateValue(),
            'attribute' => $this->getData('regular_sale_price_start_date_custom_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getRegularSalePriceStartDateAttributes()
    {
        $attributes = [];
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
        return [
            'mode' => $this->getRegularSalePriceEndDateMode(),
            'value' => $this->getRegularSalePriceEndDateValue(),
            'attribute' => $this->getData('regular_sale_price_end_date_custom_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getRegularSalePriceEndDateAttributes()
    {
        $attributes = [];
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

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessPriceModifier(): array
    {
        $value = $this->getData('business_price_modifier');
        if (empty($value)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($value) ?: [];
    }

    /**
     * @return array
     */
    public function getBusinessPriceSource(): array
    {
        return [
            'mode' => $this->getBusinessPriceMode(),
            'attribute' => $this->getData('business_price_custom_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getBusinessPriceAttributes()
    {
        $attributes = [];
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

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessDiscountsTierModifier(): array
    {
        $value = $this->getData('business_discounts_tier_modifier');
        if (empty($value)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($value) ?: [];
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
        return [
            'mode' => $this->getBusinessDiscountsMode(),
            'tier_customer_group_id' => $this->getBusinessDiscountsTierCustomerGroupId(),
        ];
    }

    //########################################

    /**
     * @return bool
     */
    public function usesConvertiblePrices()
    {
        $attributeHelper = $this->getHelper('Magento\Attribute');

        $isPriceConvertEnabled = $this->moduleConfiguration->isEnableMagentoAttributePriceTypeConvertingMode();

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
                if (
                    $this->isRegularPriceModeAttribute()
                    && $attributeHelper->isAttributeInputTypePrice($this->getData('regular_price_custom_attribute'))
                ) {
                    return true;
                }

                if (
                    $this->isRegularSalePriceModeAttribute()
                    && $attributeHelper->isAttributeInputTypePrice(
                        $this->getData('regular_sale_price_custom_attribute')
                    )
                ) {
                    return true;
                }

                if (
                    $this->isRegularMapPriceModeAttribute()
                    && $attributeHelper->isAttributeInputTypePrice($this->getData('regular_map_price_custom_attribute'))
                ) {
                    return true;
                }
            }
        }

        if ($this->isBusinessCustomerAllowed()) {
            if ($this->isBusinessPriceModeProduct() || $this->isBusinessPriceModeSpecial()) {
                return true;
            }

            if ($isPriceConvertEnabled) {
                if (
                    $this->isBusinessPriceModeAttribute()
                    && $attributeHelper->isAttributeInputTypePrice($this->getData('business_price_custom_attribute'))
                ) {
                    return true;
                }
            }

            foreach ($this->getBusinessDiscounts(true) as $businessDiscount) {
                if ($businessDiscount->isModeProduct() || $businessDiscount->isModeSpecial()) {
                    return true;
                }

                if (
                    $isPriceConvertEnabled
                    && $businessDiscount->isModeAttribute()
                    && $attributeHelper->isAttributeInputTypePrice($businessDiscount->getAttribute())
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    /**
     * @return string[]
     */
    public static function getPriceTypes(): array
    {
        return [
            self::PRICE_TYPE_REGULAR,
            self::PRICE_TYPE_REGULAR_SALE,
            self::PRICE_TYPE_BUSINESS,
            self::PRICE_TYPE_BUSINESS_DISCOUNTS_TIER,
        ];
    }
}
