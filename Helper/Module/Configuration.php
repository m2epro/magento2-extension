<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Configuration
{
    private const CONFIG_GROUP = '/general/configuration/';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    public function __construct(\Ess\M2ePro\Model\Config\Manager $config)
    {
        $this->config = $config;
    }

    public function getViewShowProductsThumbnailsMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'view_show_products_thumbnails_mode'
        );
    }

    public function getViewShowBlockNoticesMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'view_show_block_notices_mode'
        );
    }

    public function getProductForceQtyMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'product_force_qty_mode'
        );
    }

    public function isEnableProductForceQtyMode(): bool
    {
        return $this->getProductForceQtyMode() === 1;
    }

    public function getProductForceQtyValue(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'product_force_qty_value'
        );
    }

    public function getMagentoAttributePriceTypeConvertingMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'magento_attribute_price_type_converting_mode'
        );
    }

    public function isEnableMagentoAttributePriceTypeConvertingMode(): bool
    {
        return $this->getMagentoAttributePriceTypeConvertingMode() === 1;
    }

    public function getListingProductInspectorMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'listing_product_inspector_mode'
        );
    }

    public function isEnableListingProductInspectorMode(): bool
    {
        return $this->getListingProductInspectorMode() === 1;
    }

    public function getGroupedProductMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'grouped_product_mode'
        );
    }

    public function isGroupedProductModeOptions(): bool
    {
        return $this->getGroupedProductMode() === \Ess\M2ePro\Model\Listing\Product::GROUPED_PRODUCT_MODE_OPTIONS;
    }

    public function isGroupedProductModeSet(): bool
    {
        return $this->getGroupedProductMode() === \Ess\M2ePro\Model\Listing\Product::GROUPED_PRODUCT_MODE_SET;
    }

    //########################################

    public function getSecureImageUrlInItemDescriptionMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'secure_image_url_in_item_description_mode'
        );
    }

    public function getViewProductsGridUseAlternativeMysqlSelectMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'view_products_grid_use_alternative_mysql_select_mode'
        );
    }

    public function getRendererDescriptionConvertLinebreaksMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'renderer_description_convert_linebreaks_mode'
        );
    }

    public function getOtherPayPalUrl()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'other_pay_pal_url'
        );
    }

    public function getProductIndexMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'product_index_mode'
        );
    }

    public function getQtyPercentageRoundingGreater(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'qty_percentage_rounding_greater'
        );
    }

    public function getCreateWithFirstProductOptionsWhenVariationUnavailable(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'create_with_first_product_options_when_variation_unavailable'
        );
    }

    //########################################

    public function setConfigValues(array $values): void
    {
        if (isset($values['view_show_products_thumbnails_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'view_show_products_thumbnails_mode',
                $values['view_show_products_thumbnails_mode']
            );
        }

        if (isset($values['view_show_block_notices_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'view_show_block_notices_mode',
                $values['view_show_block_notices_mode']
            );
        }

        if (isset($values['product_force_qty_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'product_force_qty_mode',
                $values['product_force_qty_mode']
            );
        }

        if (isset($values['product_force_qty_value'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'product_force_qty_value',
                $values['product_force_qty_value']
            );
        }

        if (isset($values['magento_attribute_price_type_converting_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'magento_attribute_price_type_converting_mode',
                $values['magento_attribute_price_type_converting_mode']
            );
        }

        if (isset($values['listing_product_inspector_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'listing_product_inspector_mode',
                $values['listing_product_inspector_mode']
            );
        }

        if (isset($values['grouped_product_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'grouped_product_mode',
                $values['grouped_product_mode']
            );
        }
    }
}
