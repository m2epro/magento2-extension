define([
    'jquery',
    'M2ePro/Common'
], function (jQuery) {

    window.WalmartSettingsMain = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-walmart-required-identifier-setting', function(value, el) {

                var result = false;

                if ($('product_id_override_mode').value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::PRODUCT_ID_OVERRIDE_MODE_ALL')) {
                    return true;
                }

                $$('.M2ePro-walmart-required-identifier-setting').each(function(obj) {
                    if (obj.value > 0) {
                        result = true;
                        return;
                    }
                });

                return result;
            }, M2ePro.translator.translate('Required at least one identifier'));
        },

        // ---------------------------------------

        sku_mode_change: function()
        {
            var self = WalmartSettingsMainObj;

            $('sku_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::SKU_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('sku_custom_attribute'));
            }
        },

        sku_modification_mode_change: function()
        {
            var self = WalmartSettingsMainObj;

            if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::SKU_MODIFICATION_MODE_TEMPLATE')) {
                $('sku_modification_custom_value').value = '%value%';
            } else {
                $('sku_modification_custom_value').value = '';
            }

            if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::SKU_MODIFICATION_MODE_NONE')) {
                $('sku_modification_custom_value_tr').hide();
            } else {
                $('sku_modification_custom_value_tr').show();
            }
        },

        upc_mode_change: function()
        {
            var self = WalmartSettingsMainObj;

            $('upc_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::UPC_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('upc_custom_attribute'));
            }
        },

        ean_mode_change: function()
        {
            var self = WalmartSettingsMainObj;

            $('ean_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::EAN_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('ean_custom_attribute'));
            }
        },

        gtin_mode_change: function()
        {
            var self = WalmartSettingsMainObj;

            $('gtin_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::GTIN_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('gtin_custom_attribute'));
            }
        },

        isbn_mode_change: function()
        {
            var self = WalmartSettingsMainObj;

            $('isbn_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::ISBN_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('isbn_custom_attribute'));
            }
        }

        // ---------------------------------------
    });
});