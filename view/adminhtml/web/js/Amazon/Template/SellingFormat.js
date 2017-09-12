define([
    'jquery',
    'M2ePro/Amazon/Template/Edit'
], function (jQuery) {

    window.AmazonTemplateSellingFormat = Class.create(AmazonTemplateEdit, {

        discountRulesCount: 0,

        // ---------------------------------------

        initialize: function()
        {
            var self = this;
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

                if (self.isElementHiddenFromPage(el)) {
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

                var fromTime = $('regular_sale_price_start_date_value'),
                    toTime = $('regular_sale_price_end_date_value');

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

            jQuery.validator.addMethod('M2ePro-customer-allowed-types', function(value, element) {
                return !(
                    $('is_regular_customer_allowed').value == 0 &&
                    $('is_business_customer_allowed').value == 0
                );
            }, M2ePro.translator.translate('At least one Selling Type should be enabled.'));

            jQuery.validator.addMethod('M2ePro-business-discount-availability', function(value, element) {
                if (!$('is_business_customer_allowed') || $('is_business_customer_allowed').value == 0 ||
                    element.value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE')
                ) {
                    return true;
                }

                return $$('#business_discounts_custom_value_discount_table_tbody .business-discount-rule').length > 0;
            }, M2ePro.translator.translate('You should add at least one Discount Rule.'));

            jQuery.validator.addMethod('M2ePro-business-discount-qty-unique', function(value, element) {
                return !(element.up('tbody').select('.M2ePro-business-discount-qty-unique[value="' + value + '"]').length > 1);
            }, M2ePro.translator.translate('The Quantity value should be unique.'));

            jQuery.validator.addMethod('M2ePro-business-discount-attribute-coefficient-unique', function(value, element) {
                if (Validation.get('IsEmpty').test(value)) {
                    return true;
                }

                var similarValues;

                if (value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                    var attribute = element.up('tr').down('.business-discount-attribute').value;
                    similarValues = element.up('tbody').select('.business-discount-attribute[value="' + attribute + '"]');
                } else {
                    similarValues = element.up('tbody').select('.M2ePro-business-discount-attribute-coefficient-unique[value="' + value + '"]');
                }

                if (similarValues.length === 1) {
                    return true;
                }

                var coefficientValue = element.up('tr').down('.business-discount-coefficient').value;
                var similarValues = similarValues.filter(function(el) {
                    return el.up('tr').down('.business-discount-coefficient').value == coefficientValue
                });

                return !(similarValues.length > 1);
            }, M2ePro.translator.translate('You should specify a unique pair of Magento Attribute and Price Change value for each Discount Rule.'));
        },

        initObservers: function()
        {
            if ($('is_regular_customer_allowed')) {
                $('is_regular_customer_allowed')
                    .observe('change', AmazonTemplateSellingFormatObj.is_regular_customer_allowed_change)
                    .simulate('change');
            }

            if ($('is_business_customer_allowed')) {
                $('is_business_customer_allowed')
                    .observe('change', AmazonTemplateSellingFormatObj.is_business_customer_allowed_change)
                    .simulate('change');
            }

            $('qty_mode')
                .observe('change', AmazonTemplateSellingFormatObj.qty_mode_change)
                .simulate('change');

            $('qty_modification_mode')
                .observe('change', AmazonTemplateSellingFormatObj.qtyPostedMode_change)
                .simulate('change');

            if ($('regular_price_mode')) {
                $('regular_price_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.regular_price_mode_change)
                    .simulate('change');
            }

            if ($('regular_map_price_mode')) {
                $('regular_map_price_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.regular_map_price_mode_change)
                    .simulate('change');
            }

            if ($('regular_sale_price_start_date_mode')) {
                $('regular_sale_price_start_date_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.regular_sale_price_start_date_mode_change)
                    .simulate('change');
            }

            if ($('regular_sale_price_end_date_mode')) {
                $('regular_sale_price_end_date_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.regular_sale_price_end_date_mode_change)
                    .simulate('change');
            }

            if ($('regular_sale_price_mode')) {
                $('regular_sale_price_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.regular_sale_price_mode_change)
                    .simulate('change');
            }

            if ($('regular_price_increase_vat_percent')) {
                $('regular_price_increase_vat_percent')
                    .observe('change', AmazonTemplateSellingFormatObj.regular_price_increase_vat_percent_mode_change)
                    .simulate('change');
            }

            if ($('business_price_mode')) {
                $('business_price_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.business_price_mode_change)
                    .simulate('change');
            }

            if ($('business_price_increase_vat_percent')) {
                $('business_price_increase_vat_percent')
                    .observe('change', AmazonTemplateSellingFormatObj.business_price_increase_vat_percent_mode_change)
                    .simulate('change');
            }

            if ($('business_discounts_mode')) {
                $('business_discounts_mode')
                    .observe('change', AmazonTemplateSellingFormatObj.business_discounts_mode_change)
                    .simulate('change');
            }

            if ($('business_discounts_custom_value_discount_table_tbody')) {
                $('business_discounts_custom_value_discount_table_tbody').on(
                    'change', 'select.business-discount-mode', AmazonTemplateSellingFormatObj.business_discount_price_mode_change
                );
            }

            if ($('business_discounts_mode')) {
                AmazonTemplateSellingFormatObj.renderDiscountRules(M2ePro.formData.discount_rules);
            }
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

        regular_price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            $('regular_price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('regular_price_custom_attribute'));
            }

            $('regular_price_note').innerHTML = M2ePro.translator.translate('Product Price for Amazon Listing(s).');
        },

        regular_map_price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            $('regular_map_price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('regular_map_price_custom_attribute'));
            }
        },

        // ---------------------------------------

        regular_sale_price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_NONE')) {

                $('regular_sale_price_start_date_mode_tr', 'regular_sale_price_end_date_mode_tr', 'regular_sale_price_coefficient_td').invoke('hide');
                $('regular_sale_price_start_date_value_tr', 'regular_sale_price_end_date_value_tr').invoke('hide');
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_SPECIAL')) {
                $('regular_sale_price_coefficient_td').show();
                $('regular_sale_price_start_date_mode_tr', 'regular_sale_price_end_date_mode_tr').invoke('hide');
                $('regular_sale_price_start_date_value_tr', 'regular_sale_price_end_date_value_tr').invoke('hide');
            } else {
                $('regular_sale_price_start_date_mode_tr', 'regular_sale_price_end_date_mode_tr', 'regular_sale_price_coefficient_td').invoke('show');
                $('regular_sale_price_start_date_mode').simulate('change');
                $('regular_sale_price_end_date_mode').simulate('change');
            }

            $('regular_sale_price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('regular_sale_price_custom_attribute'));
            }

            $('regular_sale_price_note').innerHTML = M2ePro.translator.translate('The Price, at which you want to sell your Product(s) at specific time.');
        },

        regular_sale_price_start_date_mode_change: function()
        {
            $('regular_sale_price_start_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::DATE_VALUE')) {
                $('regular_sale_price_start_date_value_tr').show();
            } else {
                $('regular_sale_price_start_date_value_tr').hide();
                AmazonTemplateSellingFormatObj.updateHiddenValue(this, $('regular_sale_price_start_date_custom_attribute'));
            }

        },

        regular_sale_price_end_date_mode_change: function()
        {
            $('regular_sale_price_end_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::DATE_VALUE')) {
                $('regular_sale_price_end_date_value_tr').show();
            } else {
                $('regular_sale_price_end_date_value_tr').hide();
                AmazonTemplateSellingFormatObj.updateHiddenValue(this, $('regular_sale_price_end_date_custom_attribute'));
            }

        },

        // ---------------------------------------

        regular_price_increase_vat_percent_mode_change: function()
        {
            var vatPercentTr = $('regular_price_vat_percent_tr'),
                vatPercent = $('regular_price_vat_percent');

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
        },

        // ---------------------------------------

        // Business ------------------------------
        // ---------------------------------------

        is_regular_customer_allowed_change: function ()
        {
            var regularCustomer = $('is_regular_customer_allowed'),
                businessCustomer = $('is_regular_customer_allowed'),
                regularBlock = $('magento_block_amazon_template_selling_format_prices');

            regularBlock.hide();
            if (regularCustomer.value == 1){
                regularBlock.show();
            }
        },

        is_business_customer_allowed_change: function ()
        {
            var businessCustomer = $('is_business_customer_allowed'),
                businessBlock = $('magento_block_amazon_template_selling_format_business_prices');

            businessBlock.hide();
            if (businessCustomer.value == 1){
                businessBlock.show();
            }
        },

        business_price_mode_change: function()
        {
            var self = AmazonTemplateSellingFormatObj;

            $('business_price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('business_price_custom_attribute'));
            }

            $('business_price_note').innerHTML = M2ePro.translator.translate('Business Product Price for Amazon Listing(s).');
        },

        business_price_increase_vat_percent_mode_change: function()
        {
            var vatPercentTr = $('business_price_vat_percent_tr'),
                vatPercent = $('business_price_vat_percent');

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
        },

        business_discounts_mode_change: function()
        {
            var qtyPriceMode = $('business_discounts_mode');

            $(
                'business_discounts_tier_customer_group_id_tr', 'business_discounts_tier_coefficient_td',
                'business_discounts_custom_value_discount_table', 'business_discounts_not_set_table'
            ).invoke('hide');

            $('business_discounts_custom_value_discount_table_tbody').update();

            AmazonTemplateSellingFormatObj.discountRulesCount = 0;

            $$('.add_discount_rule_button').each(function (el) {
                el.show();
            });

            if (qtyPriceMode.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::BUSINESS_DISCOUNTS_MODE_TIER')) {
                $('business_discounts_tier_customer_group_id_tr').show();
                $('business_discounts_tier_coefficient_td').show();
                return;
            }

            if (qtyPriceMode.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE')) {
                $('business_discounts_not_set_table').show();
                return;
            }
        },

        addRow: function()
        {
            $('business_discounts_custom_value_discount_table').show();
            $('business_discounts_not_set_table').hide();

            var id = 'business_discounts_custom_value_discount_table_tbody';
            var i = AmazonTemplateSellingFormatObj.discountRulesCount;

            // ---------------------------------------
            var tpl = $$('#business_discounts_custom_value_discount_table_row_template tbody')[0].innerHTML;
            tpl = tpl.replace(/%i%/g, i);
            $(id).insert(tpl);
            // ---------------------------------------

            AmazonTemplateSellingFormatObj.discountRulesCount++;

            if (AmazonTemplateSellingFormatObj.discountRulesCount >= 5) {
                $$('.add_discount_rule_button').each(function (el) {
                    el.hide();
                });
            }

            var handlerObj = new AttributeCreator('business_discount[mode][' + i + ']');
            handlerObj.setSelectObj($(id).select('select[name="business_discount[mode][' + i + ']"]').first());
            handlerObj.injectAddOption();

            return $('custom_value_discount_rule_' + i + '_tr');
        },

        removeRow: function(btnEl)
        {
            btnEl.up('tr').remove();

            AmazonTemplateSellingFormatObj.discountRulesCount--;

            if (AmazonTemplateSellingFormatObj.discountRulesCount < 5) {
                $$('.add_discount_rule_button').each(function (el) {
                    el.show();
                });
            }

            if (AmazonTemplateSellingFormatObj.discountRulesCount == 0) {
                $('business_discounts_custom_value_discount_table').hide();
                $('business_discounts_not_set_table').show();
            }
        },

        renderDiscountRules: function(discountRules)
        {
            if (discountRules.length === 0) {
                return;
            }

            if ($('business_discounts_mode').value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_SellingFormat::BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE')) {
                return;
            }

            discountRules.each(function(rule, i) {
                var row = AmazonTemplateSellingFormatObj.addRow();

                row.down('.business-discount-qty').value = rule.qty;

                if (rule.mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                    var modeOption = row.down('.business-discount-mode option[attribute_code="' + rule.attribute + '"]');
                    if (modeOption) modeOption.selected = true;
                    row.down('.business-discount-attribute').value = rule.attribute;
                } else {
                    row.down('.business-discount-mode').value = rule.mode;
                }

                row.down('.business-discount-coefficient').value = rule.coefficient;
            });
        },

        business_discount_price_mode_change: function(event,target)
        {
            var self = AmazonTemplateSellingFormatObj;

            target.previous().value = '';
            if (target.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(target, target.previous());
            }
        }

    });
});