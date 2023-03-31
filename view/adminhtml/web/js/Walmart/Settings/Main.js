define([
    'jquery',
    'M2ePro/Common'
], function(jQuery) {

    window.WalmartSettingsMain = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {
            jQuery.validator.addMethod('M2ePro-walmart-required-identifier-setting', function(value, el) {
                if ($('product_id_override_mode').value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::PRODUCT_ID_OVERRIDE_MODE_ALL')) {
                    return true;
                }

                return $('product_id_mode').value > 0;
            }, M2ePro.translator.translate('Required identifier'));
        },

        // ---------------------------------------

        sku_mode_change: function() {
            var self = WalmartSettingsMainObj;

            $('sku_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::SKU_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('sku_custom_attribute'));
            }
        },

        sku_modification_mode_change: function() {
            if ($('sku_modification_mode').value != M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::SKU_MODIFICATION_MODE_TEMPLATE')) {
                $('sku_modification_custom_value').value = '';
            }

            if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::SKU_MODIFICATION_MODE_NONE')) {
                $('sku_modification_custom_value_tr').hide();
            } else {
                $('sku_modification_custom_value_tr').show();
            }
        },

        product_id_mode_change: function() {
            var self = WalmartSettingsMainObj;

            $('product_id_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart_Configuration::PRODUCT_ID_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('product_id_custom_attribute'));
            }
        },

        // ---------------------------------------
    });
});
