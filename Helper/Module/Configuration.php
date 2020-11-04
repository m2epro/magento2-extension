<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Helper\Module\Configuration
 */
class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    const CONFIG_GROUP = '/general/configuration/';

    //########################################

    public function getViewShowProductsThumbnailsMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'view_show_products_thumbnails_mode'
        );
    }

    public function getViewShowBlockNoticesMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'view_show_block_notices_mode'
        );
    }

    public function getProductForceQtyMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'product_force_qty_mode'
        );
    }

    public function isEnableProductForceQtyMode()
    {
        return $this->getProductForceQtyMode() == 1;
    }

    public function getProductForceQtyValue()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'product_force_qty_value'
        );
    }

    public function getMagentoAttributePriceTypeConvertingMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'magento_attribute_price_type_converting_mode'
        );
    }

    public function isEnableMagentoAttributePriceTypeConvertingMode()
    {
        return $this->getMagentoAttributePriceTypeConvertingMode() == 1;
    }

    public function getListingProductInspectorMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'listing_product_inspector_mode'
        );
    }

    public function isEnableListingProductInspectorMode()
    {
        return $this->getListingProductInspectorMode() == 1;
    }

    public function getGroupedProductMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'grouped_product_mode'
        );
    }

    public function isGroupedProductModeOptions()
    {
        return $this->getGroupedProductMode() == \Ess\M2ePro\Model\Listing\Product::GROUPED_PRODUCT_MODE_OPTIONS;
    }

    public function isGroupedProductModeSet()
    {
        return $this->getGroupedProductMode() == \Ess\M2ePro\Model\Listing\Product::GROUPED_PRODUCT_MODE_SET;
    }

    //########################################

    public function getSecureImageUrlInItemDescriptionMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'secure_image_url_in_item_description_mode'
        );
    }

    public function getViewProductsGridUseAlternativeMysqlSelectMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'view_products_grid_use_alternative_mysql_select_mode'
        );
    }

    public function getRendererDescriptionConvertLinebreaksMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'renderer_description_convert_linebreaks_mode'
        );
    }

    public function getOtherPayPalUrl()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'other_pay_pal_url'
        );
    }

    public function getProductIndexMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'product_index_mode'
        );
    }

    public function getQtyPercentageRoundingGreater()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'qty_percentage_rounding_greater'
        );
    }

    public function getCreateWithFirstProductOptionsWhenVariationUnavailable()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'create_with_first_product_options_when_variation_unavailable'
        );
    }

    //########################################

    public function setConfigValues(array $values)
    {
        if (isset($values['view_show_products_thumbnails_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'view_show_products_thumbnails_mode',
                $values['view_show_products_thumbnails_mode']
            );
        }

        if (isset($values['view_show_block_notices_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'view_show_block_notices_mode',
                $values['view_show_block_notices_mode']
            );
        }

        if (isset($values['product_force_qty_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'product_force_qty_mode',
                $values['product_force_qty_mode']
            );
        }

        if (isset($values['product_force_qty_value'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'product_force_qty_value',
                $values['product_force_qty_value']
            );
        }

        if (isset($values['magento_attribute_price_type_converting_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'magento_attribute_price_type_converting_mode',
                $values['magento_attribute_price_type_converting_mode']
            );
        }

        if (isset($values['listing_product_inspector_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'listing_product_inspector_mode',
                $values['listing_product_inspector_mode']
            );
        }

        if (isset($values['grouped_product_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'grouped_product_mode',
                $values['grouped_product_mode']
            );
        }
    }

    //########################################
}
