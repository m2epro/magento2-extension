define([
    'M2ePro/Amazon/Template/Edit'
], function () {

    window.AmazonTemplateShippingOverride = Class.create(AmazonTemplateEdit,  {

        rulesIndex: 0,

        // ---------------------------------------

        initialize: function () {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-override-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Amazon\\Template\\ShippingOverride', 'title', 'id',
                M2ePro.formData.id);
        },

        // ---------------------------------------

        duplicateClick: function ($super, $headId) {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-override-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Amazon\\Template\\ShippingOverride', 'title', 'id', '');

            $super($headId, M2ePro.translator.translate('Add Shipping Override Policy'));
        },

        // ---------------------------------------

        addRow: function (ruleData) {
            var self = this,
                marketplaceId = $('marketplace_id').value;

            ruleData = ruleData || {};
            this.rulesIndex++;

            var tpl = $('shipping_override_rule_table_row_template').down('tbody').innerHTML;
            tpl = tpl.replace(/%i%/g, this.rulesIndex);
            $('shipping_override_rules_table_tbody').insert(tpl);

            var row = $('shipping_override_rule_' + this.rulesIndex + '_tr');
            row.show();

            var serviceEl = row.down('.shipping-override-service');

            var option = new Element('option', {
                class: 'empty',
                value: ''
            });
            serviceEl.insert({bottom: option});

            var services = [];
            $H(self.overrideServicesData).each(function (data) {
                if (data.value.marketplace_id == marketplaceId && services.indexOf(data.value.service) == -1) {
                    services.push(data.value.service);
                }
            });

            services.each(function (service) {
                var option = new Element('option', {
                    value: service
                });
                option.innerHTML = service;
                serviceEl.insert(option);
            });

            if (ruleData.service) {
                serviceEl.value = ruleData.service;
                self.ruleServiceChange.call(self, serviceEl, ruleData);
            }

            var attributeEl = row.down('.shipping-override-cost-custom-attribute');
            attributeEl.addClassName('M2ePro-custom-attribute-can-be-created');
            attributeEl.id = 'shipping_override_cost_custom_attribute_' + this.rulesIndex;
            attributeEl.setAttribute('allowed_attribute_types', 'text,price,select');

            var handlerObj = new AttributeCreator('shipping_override_cost_custom_attribute_' + this.rulesIndex);
            handlerObj.setSelectObj(attributeEl);
            handlerObj.injectAddOption();
        },

        // ---------------------------------------

        removeRow: function (el) {
            el.up('.shipping-override-rule').remove();

            if ($('shipping_override_rules_table_tbody').select('tr').length == 0) {
                AmazonTemplateShippingOverrideObj.addRow();
            }
        },

        // ---------------------------------------

        renderRules: function (data) {
            var self = this;

            $('magento_block_amazon_template_shipping_override_rules').show();

            data.each(function (rule) {
                self.addRow(rule);
            });
        },

        // ---------------------------------------

        marketplaceChange: function () {
            if (this.value != '') {
                if (this.down('.empty-option')) {
                    this.down('.empty-option').hide();
                }
                $('magento_block_amazon_template_shipping_override_rules').show();
            }

            $('shipping_override_rules_table_tbody').update();
            if (this.type == 'select-one') {
                $('shipping_override_rule_table_row_template').down('.shipping-override-rule-currency')
                    .update(this[this.selectedIndex].getAttribute('currency'));
            }
            AmazonTemplateShippingOverrideObj.addRow();
        },

        // ---------------------------------------

        ruleServiceChange: function (el, ruleData) {
            var self = this,
                location = el.up('tr').down('.shipping-override-location'),
                marketplaceId = $('marketplace_id').value;

            ruleData = ruleData || {};

            if (el.value == '') {
                location.value = '';
                location.simulate('change');
                location.disable().hide();

                return;
            }
            location.update().enable().show();

            var option = new Element('option', {
                class: 'empty',
                value: ''
            });
            location.insert({bottom: option});

            $H(self.overrideServicesData).each(function (data) {
                if (data.value.marketplace_id == marketplaceId && data.value.service == el.value) {
                    var option = new Element('option', {
                        value: data.value.location
                    });

                    option.innerHTML = data.value.location;
                    location.insert(option);
                }
            });

            if (ruleData.location) {
                location.value = ruleData.location;
                self.ruleLocationChange.call(self, location, ruleData);
            }
        },

        ruleLocationChange: function (el, ruleData) {
            var self = this,
                service = el.up('tr').down('.shipping-override-service'),
                location = el.up('tr').down('.shipping-override-location'),
                type = el.up('tr').down('.shipping-override-type'),
                option = el.up('tr').down('.shipping-override-option');

            ruleData = ruleData || {};

            if (el.value == '') {
                type.value = '';
                type.simulate('change');
                type.disable().hide();

                option.value = '';

                return;
            }
            type.enable().show();

            $H(self.overrideServicesData).each(function (data) {
                if (service.value == data.value.service && location.value == data.value.location) {
                    option.value = data.value.option;
                    throw $break;
                }
            });

            if (ruleData.type) {
                type.value = ruleData.type;
                self.ruleTypeChange.call(self, type, ruleData);
            }
        },

        ruleTypeChange: function (el, ruleData) {
            var self = this,
                costMode = el.up('tr').down('.shipping-override-cost-mode');

            ruleData = ruleData || {};

            if (el.value == '' || el.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingOverride_Service::TYPE_RESTRICTIVE')) {
                costMode.value = '';
                costMode.simulate('change');
                costMode.disable().hide();

                return;
            }
            costMode.enable().show();

            if (ruleData.cost_mode) {
                costMode.value = ruleData.cost_mode;
                self.ruleCostModeChange.call(self, costMode, ruleData);
            }
        },

        ruleCostModeChange: function (el, ruleData) {
            var self = this,
                costCustomValue = el.up('tr').down('.shipping-override-cost-custom-value'),
                costCustomAttribute = el.up('tr').down('.shipping-override-cost-custom-attribute');

            ruleData = ruleData || {};

            costCustomValue.removeAttribute('readonly');

            if (el.value == '' || el.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingOverride_Service::COST_MODE_FREE')) {
                costCustomValue.disable().hide();
                costCustomAttribute.disable().hide();

                costCustomValue.value = '';
                costCustomAttribute.value = '';

                if (el.value != '' && el.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingOverride_Service::COST_MODE_FREE')) {
                    costCustomValue.enable().show();
                    costCustomValue.value = 0;
                    costCustomValue.setAttribute('readonly', 'readonly');
                }

                return;
            }

            if (el.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingOverride_Service::COST_MODE_CUSTOM_VALUE')) {
                costCustomValue.enable().show();
                costCustomAttribute.disable().hide();

                if (ruleData.cost_value) {
                    costCustomValue.value = ruleData.cost_value;
                }
            } else if (el.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingOverride_Service::COST_MODE_CUSTOM_ATTRIBUTE')) {
                costCustomValue.disable().hide();
                costCustomAttribute.enable().show();

                if (ruleData.cost_value) {
                    costCustomAttribute.value = ruleData.cost_value;
                }
            }
        }

        // ---------------------------------------

    });
});