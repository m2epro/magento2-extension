define([
    'jquery',
    'M2ePro/Amazon/Template/Edit'
], function (jQuery) {

    window.AmazonTemplateSellingFormat = Class.create(AmazonTemplateEdit, {

        // ---------------------------------------

        initialize: function()
        {
            this.setValidationCheckRepetitionValue('M2ePro-price-tpl-title',
                                                    M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                                                    'Template\\SellingFormat', 'title', 'id',
                                                    M2ePro.formData.id,
                                                    M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'));

            jQuery.validator.addMethod('M2ePro-validate-price-coefficient', function(value) {

                if (value == '') {
                    return true;
                }

                if (value == '0' || value == '0%') {
                    return false;
                }

                return value.match(/^[+-]?\d+[.]?\d*[%]?$/g);
            }, M2ePro.translator.translate('Coefficient is not valid.'));

            jQuery.validator.addMethod('M2ePro-validate-qty', function(value, el) {

                if (!el.up('.admin__field').visible()) {
                    return true;
                }

                if (value.match(/[^\d]+/g)) {
                    return false;
                }

                if (value <= 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Wrong value. Only integer numbers.'));

            jQuery.validator.addMethod('M2ePro-validate-vat-percent', function(value) {

                if (value.length > 6) {
                    return false;
                }

                if (value < 0) {
                    return false;
                }

                value = Math.ceil(value);

                return value > 0 && value <= 30;
            }, M2ePro.translator.translate('Wrong value. Must be no more than 30. Max applicable length is 6 characters, including the decimal (e.g., 12.345).'));

            jQuery.validator.addMethod('M2ePro-date-range-to', function(value) {

                var fromTime = $('sale_price_start_date_value'),
                    toTime = $('sale_price_end_date_value');

                if (fromTime.value == '' || toTime.value == '' ||
                    !jQuery(fromTime).is(':visible') || !jQuery(toTime).is(':visible')) {
                    return true;
                }

                fromTime = new Date(fromTime.value);
                toTime = new Date(toTime.value);

                if (!fromTime || !toTime) {
                    return true;
                }

                return toTime.getTime() > fromTime.getTime();
            }, M2ePro.translator.translate('Wrong date range.'));
        },

        initObservers: function()
        {
            $('qty_mode')
                .observe('change', AmazonTemplateSellingFormatObj.qty_mode_change)
                .simulate('change');

            $('qty_modification_mode')
                .observe('change', AmazonTemplateSellingFormatObj.qtyPostedMode_change)
                .simulate('change');

            $('price_mode')
                .observe('change', AmazonTemplateSellingFormatObj.price_mode_change)
                .simulate('change');

            $('map_price_mode')
                .observe('change', AmazonTemplateSellingFormatObj.map_price_mode_change)
                .simulate('change');

            $('sale_price_start_date_mode')
                .observe('change', AmazonTemplateSellingFormatObj.sale_price_start_date_mode_change)
                .simulate('change');

            $('sale_price_end_date_mode')
                .observe('change', AmazonTemplateSellingFormatObj.sale_price_end_date_mode_change)
                .simulate('change');

            $('sale_price_mode')
                .observe('change', AmazonTemplateSellingFormatObj.sale_price_mode_change)
                .simulate('change');

            $('price_increase_vat_percent')
                .observe('change', AmazonTemplateSellingFormatObj.price_increase_vat_percent_mode_change)
                .simulate('change');
        },

        // ---------------------------------------

        duplicateClick: function($super, $headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-price-tpl-title',
                                                    M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                                                    'Template\\SellingFormat', 'title', '','',
                                                    M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'));

            $super($headId, M2ePro.translator.translate('Add Price, Quantity and Format Policy'));
        },

        // ---------------------------------------

        qty_mode_change: function()
        {
            $('qty_custom_value_tr', 'qty_percentage_tr', 'qty_modification_mode_tr').invoke('hide');

            $('qty_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_NUMBER')) {
                $('qty_custom_value_tr').show();
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE')) {
                AmazonTemplateSellingFormatObj.updateHiddenValue(this, $('qty_custom_attribute'));
            }

            $('qty_modification_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::QTY_MODIFICATION_MODE_OFF');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {

                $('qty_modification_mode_tr').show();

                $('qty_modification_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::QTY_MODIFICATION_MODE_ON');

                if (M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                    M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                    M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {
                    $('qty_modification_mode').value = M2ePro.formData.qty_modification_mode;
                }
            }

            $('qty_modification_mode').simulate('change');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {

                $('qty_percentage_tr').show();
            }
        },

        qtyPostedMode_change: function()
        {
            $('qty_min_posted_value_tr').hide();
            $('qty_max_posted_value_tr').hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::QTY_MODIFICATION_MODE_ON')) {
                $('qty_min_posted_value_tr').show();
                $('qty_max_posted_value_tr').show();
            }
        },

        // ---------------------------------------

        price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            $('price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('price_custom_attribute'));
            }

            $('price_note').innerHTML = M2ePro.translator.translate('Product Price for Amazon Listing(s).');
        },

        map_price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            $('map_price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('map_price_custom_attribute'));
            }
        },

        // ---------------------------------------

        sale_price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_NONE')) {

                $('sale_price_start_date_mode_tr', 'sale_price_end_date_mode_tr', 'sale_price_coefficient_td').invoke('hide');
                $('sale_price_start_date_value_tr', 'sale_price_end_date_value_tr').invoke('hide');
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_SPECIAL')) {
                $('sale_price_coefficient_td').show();
                $('sale_price_start_date_mode_tr', 'sale_price_end_date_mode_tr').invoke('hide');
                $('sale_price_start_date_value_tr', 'sale_price_end_date_value_tr').invoke('hide');
            } else {
                $('sale_price_start_date_mode_tr', 'sale_price_end_date_mode_tr', 'sale_price_coefficient_td').invoke('show');
                $('sale_price_start_date_mode').simulate('change');
                $('sale_price_end_date_mode').simulate('change');
            }

            $('sale_price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('sale_price_custom_attribute'));
            }

            $('sale_price_note').innerHTML = M2ePro.translator.translate('The Price, at which you want to sell your Product(s) at specific time.');
        },

        sale_price_start_date_mode_change: function()
        {
            $('sale_price_start_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::DATE_VALUE')) {
                $('sale_price_start_date_value_tr').show();
            } else {
                $('sale_price_start_date_value_tr').hide();
                AmazonTemplateSellingFormatObj.updateHiddenValue(this, $('sale_price_start_date_custom_attribute'));
            }

        },

        sale_price_end_date_mode_change: function()
        {
            $('sale_price_end_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::DATE_VALUE')) {
                $('sale_price_end_date_value_tr').show();
            } else {
                $('sale_price_end_date_value_tr').hide();
                AmazonTemplateSellingFormatObj.updateHiddenValue(this, $('sale_price_end_date_custom_attribute'));
            }

        },

        // ---------------------------------------

        price_increase_vat_percent_mode_change: function()
        {
            var vatPercentTr = $('price_vat_percent_tr'),
                vatPercent = $('price_vat_percent');

            vatPercentTr.hide();
            vatPercent.removeClassName('M2ePro-validate-vat-percent');
            vatPercent.removeClassName('required-entry');

            if (+this.value) {
                vatPercentTr.show();
                vatPercent.addClassName('M2ePro-validate-vat-percent');
                vatPercent.addClassName('required-entry');
            } else {
                vatPercent.value = '';
            }
        }

        // ---------------------------------------
    });
});