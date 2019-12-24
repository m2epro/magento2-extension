<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Helper\Component\Walmart\Configuration
 */
class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    const SKU_MODE_DEFAULT          = 1;
    const SKU_MODE_CUSTOM_ATTRIBUTE = 2;
    const SKU_MODE_PRODUCT_ID       = 3;

    const SKU_MODIFICATION_MODE_NONE     = 0;
    const SKU_MODIFICATION_MODE_PREFIX   = 1;
    const SKU_MODIFICATION_MODE_POSTFIX  = 2;
    const SKU_MODIFICATION_MODE_TEMPLATE = 3;

    const GENERATE_SKU_MODE_NO  = 0;
    const GENERATE_SKU_MODE_YES = 1;

    const PRODUCT_ID_OVERRIDE_MODE_NONE              = 0;
    const PRODUCT_ID_OVERRIDE_MODE_ALL               = 1;
    const PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS = 2;
    const PRODUCT_ID_OVERRIDE_CUSTOM_CODE = 'CUSTOM';

    const UPC_MODE_NOT_SET          = 0;
    const UPC_MODE_CUSTOM_ATTRIBUTE = 1;

    const EAN_MODE_NOT_SET          = 0;
    const EAN_MODE_CUSTOM_ATTRIBUTE = 1;

    const GTIN_MODE_NOT_SET          = 0;
    const GTIN_MODE_CUSTOM_ATTRIBUTE = 1;

    const ISBN_MODE_NOT_SET          = 0;
    const ISBN_MODE_CUSTOM_ATTRIBUTE = 1;

    const OPTION_IMAGES_URL_MODE_ORIGINAL = 0;
    const OPTION_IMAGES_URL_MODE_HTTP     = 1;
    const OPTION_IMAGES_URL_MODE_HTTPS    = 2;

    const CONFIG_GROUP = '/walmart/configuration/';

    private $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function setSkuMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'sku_mode', $mode);
    }

    public function getSkuMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'sku_mode');
    }

    public function isSkuModeDefault()
    {
        return $this->getSkuMode() == self::SKU_MODE_DEFAULT;
    }

    public function isSkuModeCustomAttribute()
    {
        return $this->getSkuMode() == self::SKU_MODE_CUSTOM_ATTRIBUTE;
    }

    public function isSkuModeProductId()
    {
        return $this->getSkuMode() == self::SKU_MODE_PRODUCT_ID;
    }

    // ---------------------------------------

    public function setSkuCustomAttribute($attribute)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'sku_custom_attribute',
            $attribute
        );
    }

    public function getSkuCustomAttribute()
    {
        return $this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'sku_custom_attribute');
    }

    // ---------------------------------------

    public function setSkuModificationMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'sku_modification_mode', $mode);
    }

    public function getSkuModificationMode()
    {
        return (int)$this->moduleConfig->getGroupValue(
            self::CONFIG_GROUP,
            'sku_modification_mode'
        );
    }

    public function isSkuModificationModeNone()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_NONE;
    }

    public function isSkuModificationModePrefix()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_PREFIX;
    }

    public function isSkuModificationModePostfix()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_POSTFIX;
    }

    public function isSkuModificationModeTemplate()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_TEMPLATE;
    }

    // ---------------------------------------

    public function setSkuModificationCustomValue($value)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'sku_modification_custom_value',
            $value
        );
    }

    public function getSkuModificationCustomValue()
    {
        return $this->moduleConfig->getGroupValue(
            self::CONFIG_GROUP,
            'sku_modification_custom_value'
        );
    }

    // ---------------------------------------

    public function setGenerateSkuMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'generate_sku_mode', $mode);
    }

    public function getGenerateSkuMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'generate_sku_mode');
    }

    public function isGenerateSkuModeNo()
    {
        return $this->getGenerateSkuMode() == self::GENERATE_SKU_MODE_NO;
    }

    public function isGenerateSkuModeYes()
    {
        return $this->getGenerateSkuMode() == self::GENERATE_SKU_MODE_YES;
    }

    //########################################

    public function setProductIdOverrideMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'product_id_override_mode', $mode);
    }

    public function getProductIdOverrideMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'product_id_override_mode');
    }

    public function isProductIdOverrideModeNode()
    {
        return $this->getProductIdOverrideMode() == self::PRODUCT_ID_OVERRIDE_MODE_NONE;
    }

    public function isProductIdOverrideModeAll()
    {
        return $this->getProductIdOverrideMode() == self::PRODUCT_ID_OVERRIDE_MODE_ALL;
    }

    public function isProductIdOverrideModeSpecificProducts()
    {
        return $this->getProductIdOverrideMode() == self::PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS;
    }

    //########################################

    public function setUpcMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'upc_mode', $mode);
    }

    public function getUpcMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'upc_mode');
    }

    public function isUpcModeNotSet()
    {
        return $this->getUpcMode() == self::UPC_MODE_NOT_SET;
    }

    public function isUpcModeCustomAttribute()
    {
        return $this->getUpcMode() == self::UPC_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    public function setUpcCustomAttribute($attribute)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'upc_custom_attribute',
            $attribute
        );
    }

    public function getUpcCustomAttribute()
    {
        return $this->moduleConfig->getGroupValue(
            self::CONFIG_GROUP,
            'upc_custom_attribute'
        );
    }

    //########################################

    public function setEanMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'ean_mode', $mode);
    }

    public function getEanMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'ean_mode');
    }

    public function isEanModeNotSet()
    {
        return $this->getEanMode() == self::EAN_MODE_NOT_SET;
    }

    public function isEanModeCustomAttribute()
    {
        return $this->getEanMode() == self::EAN_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    public function setEanCustomAttribute($attribute)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'ean_custom_attribute',
            $attribute
        );
    }

    public function getEanCustomAttribute()
    {
        return $this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'ean_custom_attribute');
    }

    //########################################

    public function setGtinMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'gtin_mode', $mode);
    }

    public function getGtinMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'gtin_mode');
    }

    public function isGtinModeNotSet()
    {
        return $this->getGtinMode() == self::GTIN_MODE_NOT_SET;
    }

    public function isGtinModeCustomAttribute()
    {
        return $this->getGtinMode() == self::GTIN_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    public function setGtinCustomAttribute($attribute)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'gtin_custom_attribute',
            $attribute
        );
    }

    public function getGtinCustomAttribute()
    {
        return $this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'gtin_custom_attribute');
    }

    //########################################

    public function setIsbnMode($mode)
    {
        $this->moduleConfig->setGroupValue(self::CONFIG_GROUP, 'isbn_mode', $mode);
    }

    public function getIsbnMode()
    {
        return (int)$this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'isbn_mode');
    }

    public function isIsbnModeNotSet()
    {
        return $this->getIsbnMode() == self::ISBN_MODE_NOT_SET;
    }

    public function isIsbnModeCustomAttribute()
    {
        return $this->getIsbnMode() == self::ISBN_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    public function setIsbnCustomAttribute($attribute)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'isbn_custom_attribute',
            $attribute
        );
    }

    public function getIsbnCustomAttribute()
    {
        return $this->moduleConfig->getGroupValue(self::CONFIG_GROUP, 'isbn_custom_attribute');
    }

    //########################################

    public function isOptionImagesURLOriginalMode()
    {
        return $this->getOptionImagesURLMode() == self::OPTION_IMAGES_URL_MODE_ORIGINAL;
    }

    public function isOptionImagesURLHTTPSMode()
    {
        return $this->getOptionImagesURLMode() == self::OPTION_IMAGES_URL_MODE_HTTPS;
    }

    public function isOptionImagesURLHTTPMode()
    {
        return $this->getOptionImagesURLMode() == self::OPTION_IMAGES_URL_MODE_HTTP;
    }

    // ---------------------------------------

    public function setOptionImagesURLMode($mode)
    {
        $this->moduleConfig->setGroupValue(
            self::CONFIG_GROUP,
            'option_images_url_mode',
            $mode
        );
    }

    public function getOptionImagesURLMode()
    {
        return $this->moduleConfig->getGroupValue(
            self::CONFIG_GROUP,
            'option_images_url_mode'
        );
    }

    //########################################

    public function getConfigValues()
    {
        return [
            'sku_mode'                      => $this->getSkuMode(),
            'sku_custom_attribute'          => $this->getSkuCustomAttribute(),
            'sku_modification_mode'         => $this->getSkuModificationMode(),
            'sku_modification_custom_value' => $this->getSkuModificationCustomValue(),
            'generate_sku_mode'             => $this->getGenerateSkuMode(),
            'product_id_override_mode'      => $this->getProductIdOverrideMode(),
            'upc_mode'                      => $this->getUpcMode(),
            'upc_custom_attribute'          => $this->getUpcCustomAttribute(),
            'ean_mode'                      => $this->getEanMode(),
            'ean_custom_attribute'          => $this->getEanCustomAttribute(),
            'gtin_mode'                     => $this->getGtinMode(),
            'gtin_custom_attribute'         => $this->getGtinCustomAttribute(),
            'isbn_mode'                     => $this->getIsbnMode(),
            'isbn_custom_attribute'         => $this->getIsbnCustomAttribute(),
            'option_images_url_mode'        => $this->getOptionImagesURLMode()
        ];
    }

    public function setConfigValues(array $values)
    {
        if (isset($values['sku_mode'])) {
            $this->setSkuMode($values['sku_mode']);
        }

        if (isset($values['sku_custom_attribute'])) {
            $this->setSkuCustomAttribute($values['sku_custom_attribute']);
        }

        if (isset($values['sku_modification_mode'])) {
            $this->setSkuModificationMode($values['sku_modification_mode']);
        }

        if (isset($values['sku_modification_custom_value'])) {
            $this->setSkuModificationCustomValue($values['sku_modification_custom_value']);
        }

        if (isset($values['generate_sku_mode'])) {
            $this->setGenerateSkuMode($values['generate_sku_mode']);
        }

        if (isset($values['product_id_override_mode'])) {
            $this->setProductIdOverrideMode($values['product_id_override_mode']);
        }

        if (isset($values['upc_mode'])) {
            $this->setUpcMode($values['upc_mode']);
        }

        if (isset($values['upc_custom_attribute'])) {
            $this->setUpcCustomAttribute($values['upc_custom_attribute']);
        }

        if (isset($values['ean_mode'])) {
            $this->setEanMode($values['ean_mode']);
        }

        if (isset($values['ean_custom_attribute'])) {
            $this->setEanCustomAttribute($values['ean_custom_attribute']);
        }

        if (isset($values['gtin_mode'])) {
            $this->setGtinMode($values['gtin_mode']);
        }

        if (isset($values['gtin_custom_attribute'])) {
            $this->setGtinCustomAttribute($values['gtin_custom_attribute']);
        }

        if (isset($values['isbn_mode'])) {
            $this->setIsbnMode($values['isbn_mode']);
        }

        if (isset($values['isbn_custom_attribute'])) {
            $this->setIsbnCustomAttribute($values['isbn_custom_attribute']);
        }

        if (isset($values['option_images_url_mode'])) {
            $this->setOptionImagesURLMode($values['option_images_url_mode']);
        }
    }

    //########################################
}
