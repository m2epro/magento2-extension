<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

class Configuration
{
    public const SKU_MODE_DEFAULT          = 1;
    public const SKU_MODE_CUSTOM_ATTRIBUTE = 2;
    public const SKU_MODE_PRODUCT_ID       = 3;

    public const SKU_MODIFICATION_MODE_NONE     = 0;
    public const SKU_MODIFICATION_MODE_PREFIX   = 1;
    public const SKU_MODIFICATION_MODE_POSTFIX  = 2;
    public const SKU_MODIFICATION_MODE_TEMPLATE = 3;

    public const PRODUCT_ID_OVERRIDE_MODE_NONE              = 0;
    public const PRODUCT_ID_OVERRIDE_MODE_ALL               = 1;
    public const PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS = 2;
    public const PRODUCT_ID_OVERRIDE_CUSTOM_CODE            = 'CUSTOM';

    public const UPC_MODE_NOT_SET          = 0;
    public const UPC_MODE_CUSTOM_ATTRIBUTE = 1;

    public const EAN_MODE_NOT_SET          = 0;
    public const EAN_MODE_CUSTOM_ATTRIBUTE = 1;

    public const GTIN_MODE_NOT_SET          = 0;
    public const GTIN_MODE_CUSTOM_ATTRIBUTE = 1;

    public const ISBN_MODE_NOT_SET          = 0;
    public const ISBN_MODE_CUSTOM_ATTRIBUTE = 1;

    public const OPTION_IMAGES_URL_MODE_ORIGINAL = 0;
    public const OPTION_IMAGES_URL_MODE_HTTP     = 1;
    public const OPTION_IMAGES_URL_MODE_HTTPS    = 2;

    public const CONFIG_GROUP = '/walmart/configuration/';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->config = $config;
    }

    // ----------------------------------------

    public function getSkuMode()
    {
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, 'sku_mode');
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

    //---------------------------------------

    public function getSkuCustomAttribute()
    {
        return $this->config->getGroupValue(self::CONFIG_GROUP, 'sku_custom_attribute');
    }

    //---------------------------------------

    public function getSkuModificationMode()
    {
        return (int)$this->config->getGroupValue(
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

    //---------------------------------------

    public function getSkuModificationCustomValue()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'sku_modification_custom_value'
        );
    }

    //---------------------------------------

    public function getGenerateSkuMode()
    {
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, 'generate_sku_mode');
    }

    public function isGenerateSkuModeYes()
    {
        return $this->getGenerateSkuMode() == 1;
    }

    //########################################

    public function getProductIdOverrideMode()
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'product_id_override_mode'
        );
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

    public function getUpcMode()
    {
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, 'upc_mode');
    }

    public function isUpcModeNotSet()
    {
        return $this->getUpcMode() == self::UPC_MODE_NOT_SET;
    }

    public function isUpcModeCustomAttribute()
    {
        return $this->getUpcMode() == self::UPC_MODE_CUSTOM_ATTRIBUTE;
    }

    //---------------------------------------

    public function getUpcCustomAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'upc_custom_attribute'
        );
    }

    //########################################

    public function getEanMode()
    {
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, 'ean_mode');
    }

    public function isEanModeNotSet()
    {
        return $this->getEanMode() == self::EAN_MODE_NOT_SET;
    }

    public function isEanModeCustomAttribute()
    {
        return $this->getEanMode() == self::EAN_MODE_CUSTOM_ATTRIBUTE;
    }

    //---------------------------------------

    public function getEanCustomAttribute()
    {
        return $this->config->getGroupValue(self::CONFIG_GROUP, 'ean_custom_attribute');
    }

    //########################################

    public function getGtinMode()
    {
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, 'gtin_mode');
    }

    public function isGtinModeNotSet()
    {
        return $this->getGtinMode() == self::GTIN_MODE_NOT_SET;
    }

    public function isGtinModeCustomAttribute()
    {
        return $this->getGtinMode() == self::GTIN_MODE_CUSTOM_ATTRIBUTE;
    }

    //---------------------------------------

    public function getGtinCustomAttribute()
    {
        return $this->config->getGroupValue(self::CONFIG_GROUP, 'gtin_custom_attribute');
    }

    //########################################

    public function getIsbnMode()
    {
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, 'isbn_mode');
    }

    public function isIsbnModeNotSet()
    {
        return $this->getIsbnMode() == self::ISBN_MODE_NOT_SET;
    }

    public function isIsbnModeCustomAttribute()
    {
        return $this->getIsbnMode() == self::ISBN_MODE_CUSTOM_ATTRIBUTE;
    }

    //---------------------------------------

    public function getIsbnCustomAttribute()
    {
        return $this->config->getGroupValue(self::CONFIG_GROUP, 'isbn_custom_attribute');
    }

    //########################################

    public function getOptionImagesURLMode()
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'option_images_url_mode'
        );
    }

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

    //########################################

    public function setConfigValues(array $values)
    {
        if (isset($values['sku_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'sku_mode',
                $values['sku_mode']
            );
        }

        if (isset($values['sku_custom_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'sku_custom_attribute',
                $values['sku_custom_attribute']
            );
        }

        if (isset($values['sku_modification_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'sku_modification_mode',
                $values['sku_modification_mode']
            );
        }

        if (isset($values['sku_modification_custom_value'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'sku_modification_custom_value',
                $values['sku_modification_custom_value']
            );
        }

        if (isset($values['generate_sku_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'generate_sku_mode',
                $values['generate_sku_mode']
            );
        }

        if (isset($values['product_id_override_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'product_id_override_mode',
                $values['product_id_override_mode']
            );
        }

        if (isset($values['upc_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'upc_mode',
                $values['upc_mode']
            );
        }

        if (isset($values['upc_custom_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'upc_custom_attribute',
                $values['upc_custom_attribute']
            );
        }

        if (isset($values['ean_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'ean_mode',
                $values['ean_mode']
            );
        }

        if (isset($values['ean_custom_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'ean_custom_attribute',
                $values['ean_custom_attribute']
            );
        }

        if (isset($values['gtin_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'gtin_mode',
                $values['gtin_mode']
            );
        }

        if (isset($values['gtin_custom_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'gtin_custom_attribute',
                $values['gtin_custom_attribute']
            );
        }

        if (isset($values['isbn_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'isbn_mode',
                $values['isbn_mode']
            );
        }

        if (isset($values['isbn_custom_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'isbn_custom_attribute',
                $values['isbn_custom_attribute']
            );
        }

        if (isset($values['option_images_url_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'option_images_url_mode',
                $values['option_images_url_mode']
            );
        }
    }

    //########################################
}
