define([
    'jquery',
    'M2ePro/Common'
], function (jQuery) {
    window.EbayTemplateShipping = Class.create(Common, {

        // ---------------------------------------

        shippingMethods: {},
        missingAttributes: {},

        discountProfiles: [],
        shippingServices: [],
        shippingLocations: [],

        counter: {
            local: 0,
            international: 0,
            total: 0
        },

        originCountry: null,

        rateTablesIds: [],
        excludedLocations: [],

        // ---------------------------------------

        initialize: function ()
        {
            jQuery.validator.addMethod('M2ePro-location-or-postal-required', function () {
                return $('address_mode').value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::ADDRESS_MODE_NONE') ||
                    $('postal_code_mode').value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::POSTAL_CODE_MODE_NONE');
            }, M2ePro.translator.translate('Location or Zip/Postal Code should be specified.'));

            jQuery.validator.addMethod('M2ePro-validate-international-ship-to-location', function (value, el) {
                return $$('input[name="' + el.name + '"]').any(function (o) {
                    return o.checked;
                });
            }, M2ePro.translator.translate('Select one or more international ship-to Locations.'));

            jQuery.validator.addMethod('M2ePro-required-if-calculated', function (value) {

                if (EbayTemplateShippingObj.isLocalShippingModeCalculated() ||
                    EbayTemplateShippingObj.isInternationalShippingModeCalculated()) {
                    return value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::POSTAL_CODE_MODE_NONE');
                }

                return true;
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-shipping-methods', function (value, el) {

                var locationType = /local/.test(el.id) ? 'local' : 'international',
                    shippingModeValue = $(locationType + '_shipping_mode').value;

                shippingModeValue = parseInt(shippingModeValue);

                if (shippingModeValue !== M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT') &&
                    shippingModeValue !== M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED')) {
                    return true;
                }

                return EbayTemplateShippingObj.counter[locationType] != 0;
            }, M2ePro.translator.translate('You should specify at least one Shipping Method.'));

            jQuery.validator.addMethod('M2ePro-validate-shipping-service', function (value, el) {

                var hidden = !$(el).visible();
                var current = el;

                while (!hidden) {
                    el = $(el).up();
                    hidden = !el.visible();
                    if (el == document || el.hasClassName('entry-edit')) {
                        break;
                    }
                }

                if (hidden || current.up('table').id == 'shipping_international_table') {
                    return true;
                }

                return value != '';
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-rate-table', function (value, el) {

                var id = el.id.replace(
                        el.id.indexOf('local') !== -1 ? 'local' : 'international',
                        el.id.indexOf('local') !== -1 ? 'international' : 'local'
                    ).replace('_value', '_mode'),
                    secondElement = $(id),
                    mode = +$(el.id.replace('_value', '_mode')).value;

                if (!secondElement) {
                    return true;
                }

                var elementMode = +secondElement.value,
                    modeAggregate = +M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE')
                        + +M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE');

                return modeAggregate !== (mode + elementMode);
            }, M2ePro.translator.translate('You are submitting different Shipping Rate Table modes for the domestic and international shipping. It contradicts eBay requirements. Please edit the settings.'));
        },

        initObservers: function ()
        {
            jQuery('#country_mode')
                .on('change', EbayTemplateShippingObj.countryModeChange)
                .trigger('change');

            jQuery('#postal_code_mode')
                .on('change', EbayTemplateShippingObj.postalCodeModeChange)
                .trigger('change');

            jQuery('#address_mode')
                .on('change', EbayTemplateShippingObj.addressModeChange)
                .trigger('change');

            jQuery('#local_shipping_mode')
                .on('change', EbayTemplateShippingObj.localShippingModeChange)
                .trigger('change');

            if ($('local_shipping_rate_table_mode')) {
                jQuery('#local_shipping_rate_table_mode')
                    .on('change', EbayTemplateShippingObj.rateTableModeChange);
            }

            if ($('international_shipping_rate_table_mode')) {
                jQuery('#international_shipping_rate_table_mode')
                    .on('change', EbayTemplateShippingObj.rateTableModeChange);
            }

            EbayTemplateShippingObj.prepareMeasurementObservers();

            jQuery('#dispatch_time_mode')
                .on('change', EbayTemplateShippingObj.dispatchTimeChange)
                .trigger('change');

            if ($('cross_border_trade')) {
                jQuery('#cross_border_trade')
                    .on('change', EbayTemplateShippingObj.crossBorderTradeChange)
                    .trigger('change');
            }

            jQuery('#international_shipping_mode')
                .on('change', EbayTemplateShippingObj.internationalShippingModeChange)
                .trigger('change');

            EbayTemplateShippingObj.rateTablesIds.forEach(function(id) {
                jQuery('#' + id).on('change', EbayTemplateShippingObj.rateTableModeChange)
                    .trigger('change')
            });

            EbayTemplateShippingObj.renderShippingMethods(EbayTemplateShippingObj.shippingMethods);

            EbayTemplateShippingObj.checkMessages('local');
            EbayTemplateShippingObj.checkMessages('international');
        },

        // ---------------------------------------

        countryModeChange: function ()
        {
            var self = EbayTemplateShippingObj,
                elem = $('country_mode');
            if (elem.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::COUNTRY_MODE_CUSTOM_VALUE')) {

                self.updateHiddenValue(elem, $('country_custom_value'));
            }

            if (elem.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE')) {

                self.updateHiddenValue(elem, $('country_custom_attribute'));
            }
        },

        // ---------------------------------------

        postalCodeModeChange: function ()
        {
            var self = EbayTemplateShippingObj,
                elem = $('postal_code_mode');

            if (elem.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::POSTAL_CODE_MODE_CUSTOM_VALUE')) {
                $('postal_code_custom_value_tr').show();
            } else {
                $('postal_code_custom_value_tr').hide();
            }

            if (elem.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE')) {

                self.updateHiddenValue(elem, $('postal_code_custom_attribute'));
            }
        },

        // ---------------------------------------

        addressModeChange: function ()
        {
            var self = EbayTemplateShippingObj,
                elem = $('address_mode');

            if (elem.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::ADDRESS_MODE_CUSTOM_VALUE')) {
                $('address_custom_value_tr').show();
            } else {
                $('address_custom_value_tr').hide();
            }

            if (elem.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE')) {

                self.updateHiddenValue(elem, $('address_custom_attribute'));
            }
        },

        // ---------------------------------------

        dispatchTimeChange: function ()
        {
            var self = EbayTemplateShippingObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::DISPATCH_TIME_MODE_VALUE')) {

                self.updateHiddenValue(this, $('dispatch_time_value'));
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::DISPATCH_TIME_MODE_ATTRIBUTE')) {

                self.updateHiddenValue(this, $('dispatch_time_attribute'));
            }
        },

        // ---------------------------------------

        localShippingModeChange: function ()
        {
            // ---------------------------------------
            $('magento_block_ebay_template_shipping_form_data_international-wrapper').hide();
            $('local_shipping_methods_tr').hide();
            $('magento_block_ebay_template_shipping_form_data_excluded_locations-wrapper').show();
            // ---------------------------------------

            // clear selected shipping methods
            // ---------------------------------------
            $$('#shipping_local_tbody .icon-btn').each(function (el) {
                EbayTemplateShippingObj.removeRow.call(el, 'local');
            });
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeFlat()
                || EbayTemplateShippingObj.isLocalShippingModeCalculated()
            ) {
                $$('.local-shipping-tr').invoke('show');
                jQuery('#dispatch_time_mode').trigger('change');

                $('domestic_shipping_fieldset-wrapper').setStyle({
                    borderBottom: null
                });
            } else {
                $$('.local-shipping-tr').invoke('hide');
                $('domestic_shipping_fieldset-wrapper').setStyle({
                    borderBottom: '0px'
                });
            }
            // ---------------------------------------

            // ---------------------------------------
            EbayTemplateShippingObj.updateMeasurementVisibility();
            EbayTemplateShippingObj.updateCashOnDeliveryCostVisibility();
            EbayTemplateShippingObj.updateCrossBorderTradeVisibility();
            EbayTemplateShippingObj.updateRateTableVisibility('local');
            EbayTemplateShippingObj.updateLocalHandlingCostVisibility();
            EbayTemplateShippingObj.renderDiscountProfiles('local');
            EbayTemplateShippingObj.clearMessages('local');
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeFlat()) {
                $('magento_block_ebay_template_shipping_form_data_international-wrapper').show();
                $('local_shipping_methods_tr').show();
            }
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeCalculated()) {
                $('magento_block_ebay_template_shipping_form_data_international-wrapper').show();
                $('local_shipping_methods_tr').show();
            }
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeFreight()) {
                $('international_shipping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_NO_INTERNATIONAL');
                jQuery('#international_shipping_mode').trigger('change');

                $('magento_block_ebay_template_shipping_form_data_excluded_locations-wrapper').hide();
                EbayTemplateShippingExcludedLocationsObj.setSelectedLocations([]);
            }
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeLocal()) {
                $('international_shipping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_NO_INTERNATIONAL');
                jQuery('#international_shipping_mode').trigger('change');

                $('magento_block_ebay_template_shipping_form_data_excluded_locations-wrapper').hide();
                EbayTemplateShippingExcludedLocationsObj.setSelectedLocations([]);
            }
            // ---------------------------------------
        },

        isLocalShippingModeFlat: function ()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT');
        },

        isLocalShippingModeCalculated: function ()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED');
        },

        isLocalShippingModeFreight: function ()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FREIGHT');
        },

        isLocalShippingModeLocal: function ()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_LOCAL');
        },

        // ---------------------------------------

        internationalShippingModeChange: function ()
        {
            // clear selected shipping methods
            // ---------------------------------------
            $$('#shipping_international_tbody .icon-btn').each(function (el) {
                EbayTemplateShippingObj.removeRow.call(el, 'international');
            });
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isInternationalShippingModeFlat()
                || EbayTemplateShippingObj.isInternationalShippingModeCalculated()
            ) {
                $('add_international_shipping_method_button').show();
                $('shipping_international_table').hide();
                $('international_shipping_methods_tr').show();
                $$('.international-shipping-tr').invoke('show');
            } else {
                $('international_shipping_methods_tr').hide();
                $$('.international-shipping-tr').invoke('hide');

                if ($('international_shipping_rate_table_mode')) {
                    $('international_shipping_rate_table_mode').selectedIndex = 0;
                    jQuery('#international_shipping_rate_table_mode').trigger('change');
                }
            }
            // ---------------------------------------

            // ---------------------------------------
            EbayTemplateShippingObj.updateMeasurementVisibility();
            EbayTemplateShippingObj.renderDiscountProfiles('international');
            EbayTemplateShippingObj.updateRateTableVisibility('international');
            EbayTemplateShippingObj.updateInternationalHandlingCostVisibility();
            EbayTemplateShippingObj.clearMessages('international');
            // ---------------------------------------
        },

        isInternationalShippingModeFlat: function ()
        {
            return $('international_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT');
        },

        isInternationalShippingModeCalculated: function ()
        {
            return $('international_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED');
        },

        isInternationalShippingModeNoInternational: function ()
        {
            return $('international_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_NO_INTERNATIONAL');
        },

        isShippingModeCalculated: function (locationType)
        {
            if (locationType == 'local') {
                return EbayTemplateShippingObj.isLocalShippingModeCalculated();
            }

            if (locationType == 'international') {
                return EbayTemplateShippingObj.isInternationalShippingModeCalculated();
            }

            return false;
        },

        // ---------------------------------------

        crossBorderTradeChange: function ()
        {
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::CROSS_BORDER_TRADE_NONE')) {
                $('international_shipping_none').show();
            } else {
                $('international_shipping_none').hide();
                if (EbayTemplateShippingObj.isInternationalShippingModeNoInternational()) {
                    $('international_shipping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT');
                    jQuery('#international_shipping_mode').trigger('change');
                }
            }
        },

        // ---------------------------------------

        updateCrossBorderTradeVisibility: function ()
        {
            if (!$('magento_block_ebay_template_shipping_form_data_cross_border_trade-wrapper')) {
                return;
            }

            if (EbayTemplateShippingObj.isLocalShippingModeFlat()
                || EbayTemplateShippingObj.isLocalShippingModeCalculated()
            ) {
                $('magento_block_ebay_template_shipping_form_data_cross_border_trade-wrapper').show();
            } else {
                $('magento_block_ebay_template_shipping_form_data_cross_border_trade-wrapper').hide();
                $('cross_border_trade').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::CROSS_BORDER_TRADE_NONE');
            }
        },

        // ---------------------------------------

        renderRateTables: function (options)
        {
            options = options || {};

            var select = $(options.elementId);

            select.innerHTML = '';

            if (options.data && Object.keys(options.data).length) {

                [{value: 0, text: 'No'}, {value: 1, text: 'Yes'}].forEach(function (item) {
                    var option = new Element('option', {
                        value: item.value,
                        mode: M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE'),
                        selected: item.value == options.value
                    });
                    option.innerText = item.text;
                    select.appendChild(option);
                });

                var appendIn = new Element('optgroup', {label: 'Rate Table'});
                select.appendChild(appendIn);

                Object.keys(options.data).forEach(function (key) {
                    var option = new Element('option', {
                        value: key,
                        mode: M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE'),
                        selected: key == options.value
                    });
                    option.innerText = options.data[key];
                    appendIn.appendChild(option);
                });

                select.setAttribute('sell-api', 1);

            } else {

                [{value: 0, text: 'No'}, {value: 1, text: 'Yes'}].forEach(function (item) {

                    var option = new Element('option', {
                        value: item.value,
                        mode: M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE'),
                        selected: item.value == options.value
                    });
                    option.innerText = item.text;
                    select.appendChild(option);
                });
            }
        },

        updateRateTablesData: function (options)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_template_shipping/getRateTableData'), {
                method: 'get',
                parameters: {
                    'account_id': options.accountId,
                    'marketplace_id': options.marketplaceId,
                    'type': options.type
                },
                onSuccess: function (transport) {

                    var response = transport.responseText.evalJSON(true);

                    if (response.sell_api_disabled) {
                        self.sellApiAuthPopup(options);
                        return;
                    }

                    if (response.error) {
                        var elm = $(options.elementId);
                        var advice = Validation.createAdvice('error', elm, false, response.error);
                        Validation.showAdvice(elm, advice, 'error');
                        Validation.updateCallback(elm, 'failed');
                        elm.addClassName('validation-failed');
                        return;
                    } else {
                        options.data = response.data;
                    }

                    var button = $(options.elementId).up().up().down('a.update_rate_table_button');
                    button.innerText = M2ePro.translator.translate("Refresh Rate Tables");
                    button.setAttribute('data-is-downloaded', '1');

                    var select = $(options.elementId);
                    select.stopObserving('change');
                    self.renderRateTables(options);
                    select.observe('change', EbayTemplateShippingObj.rateTableModeChange)
                        .simulate('change');

                    var otherId = options.elementId.replace(
                        options.elementId.indexOf('local') !== -1 ? 'local' : 'international',
                        options.elementId.indexOf('local') !== -1 ? 'international' : 'local'
                    );

                    var otherElement = $(otherId);

                    if (!otherElement) {
                        return;
                    }

                    button = otherElement.up().up().down('a.update_rate_table_button');

                    if (!button.dataset.isDownloaded) {
                        button.simulate('click');
                    }
                }
            });
        },

        sellApiAuthPopup: function (options)
        {
            var self = this;
            this.confirm({
                title: M2ePro.translator.translate("Download Shipping Rate Tables"),
                content: M2ePro.translator.translate('sell_api_popup_text'),
                actions: {
                    confirm: function () {
                        var win = window.open(M2ePro.url.get('ebay_account/edit', {
                            id: options.accountId,
                            sell_api: 1
                        }));

                        setTimeout(function run() {

                            if (!win.closed) {
                                setTimeout(run, 250);
                                return;
                            }

                            self.updateRateTablesData(options);

                        }, 250);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        updateRateTableVisibility: function (locationType)
        {
            var shippingMode = $(locationType + '_shipping_mode').value;

            if (!$(locationType + '_shipping_rate_table_mode_tr')) {
                return;
            }

            if (shippingMode != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT')) {
                $(locationType + '_shipping_rate_table_mode_tr').hide();

                $$('[id^="'+locationType+'_shipping_rate_table_value"').forEach(function (el) {
                    el.value = 0;
                });
            } else {
                $(locationType + '_shipping_rate_table_mode_tr').show();
            }
        },

        isDomesticRateTableEnabled: function ()
        {
            return $$('[id^="local_shipping_rate_table_value"]').some(function (el) {
                return +el.value !== 0;
            });
        },

        isInternationalRateTableEnabled: function ()
        {
            return $$('[id^="international_shipping_rate_table_value"]').some(function (el) {
                return +el.value !== 0;
            });
        },

        isRateTableEnabled: function ()
        {
            return EbayTemplateShippingObj.isDomesticRateTableEnabled()
                || EbayTemplateShippingObj.isInternationalRateTableEnabled();
        },

        rateTableModeChange: function ()
        {
            var otherId = this.id.replace(
                this.id.indexOf('local') !== -1 ? 'local' : 'international',
                this.id.indexOf('local') !== -1 ? 'international' : 'local'
                ),
                otherElement = $(otherId),
                mode = +this.options[this.selectedIndex].getAttribute('mode'),
                isModeChanged = false;

            var modeElement = $(this.id.replace('value', 'mode')),
                modeOtherElement = $(otherId.replace('value', 'mode'));

            if (otherElement && this.options.length && otherElement.options.length) {

                if (+this.value !== 0 && +otherElement.value === 0) {
                    modeOtherElement.value = mode;
                }

                if (+this.value === 0 && +otherElement.value !== 0) {
                    isModeChanged = true;
                    modeElement.value = otherElement.options[otherElement.selectedIndex]
                        .getAttribute('mode');
                }

                if (+this.value === 0 && +otherElement.value === 0) {

                    isModeChanged = true;

                    if (this.dataset.currentMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE')
                        || otherElement.dataset.currentMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE')
                    ) {
                        modeElement.value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE');
                        modeOtherElement.value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE');
                    } else {
                        modeElement.value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE');
                        modeOtherElement.value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE');
                    }
                }
            } else if (+this.value === 0) {

                isModeChanged = true;

                if (this.dataset.currentMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE')) {
                    modeElement.value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE');
                } else {
                    modeElement.value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE');
                }
            }

            if (!isModeChanged) {
                modeElement.value = this.options[this.selectedIndex].getAttribute('mode');
            }

            var note = this.up().down('.m2epro-field-tooltip');

            if (otherElement && +otherElement.getAttribute('sell-api')) {
                note.down('.shipping_rate_table_note_accepted').hide();
                note.down('.shipping_rate_table_note_identifier').show();
            } else {
                note.down('.shipping_rate_table_note_accepted').show();
                note.down('.shipping_rate_table_note_identifier').hide();
            }

            EbayTemplateShippingObj.updatePackageBlockState();
        },

        // ---------------------------------------

        updateLocalHandlingCostVisibility: function ()
        {
            if (!$('local_handling_cost_cv_tr')) {
                return;
            }

            if (EbayTemplateShippingObj.isLocalShippingModeFlat()) {
                $('local_handling_cost_cv_tr').hide();
                $('local_handling_cost').value = '';
            }
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeCalculated()) {
                $('local_handling_cost_cv_tr').show();
            }
            // ---------------------------------------
        },

        updateInternationalHandlingCostVisibility: function ()
        {
            if (!$('international_handling_cost_cv_tr')) {
                return;
            }

            if (EbayTemplateShippingObj.isInternationalShippingModeCalculated()) {
                $('international_handling_cost_cv_tr').show();
            } else {
                $('international_handling_cost_cv_tr').hide();
                $('international_handling_cost').value = '';
            }
        },

        // ---------------------------------------

        updateDiscountProfiles: function (accountId) {
            new Ajax.Request(M2ePro.url.get('ebay_template_shipping/updateDiscountProfiles'), {
                method: 'get',
                parameters: {
                    'account_id': accountId
                },
                onSuccess: function (transport) {
                    EbayTemplateShippingObj.discountProfiles[accountId]['profiles'] = transport.responseText.evalJSON(true);
                    EbayTemplateShippingObj.renderDiscountProfiles('local', accountId);
                    EbayTemplateShippingObj.renderDiscountProfiles('international', accountId);
                }
            });
        },

        renderDiscountProfiles: function (locationType, renderAccountId) {
            if (typeof renderAccountId == 'undefined') {
                $$('.' + locationType + '-discount-profile-account-tr').each(function (account) {
                    var accountId = account.readAttribute('account_id');

                    if ($(locationType + '_shipping_discount_combined_profile_id_' + accountId)) {
                        var value = EbayTemplateShippingObj.discountProfiles[accountId]['selected'][locationType];

                        var html = EbayTemplateShippingObj.getDiscountProfilesHtml(locationType, accountId);
                        $(locationType + '_shipping_discount_combined_profile_id_' + accountId).update(html);

                        if (value && EbayTemplateShippingObj.discountProfiles[accountId]['profiles'].length > 0) {
                            var select = $(locationType + '_shipping_discount_combined_profile_id_' + accountId);

                            for (var i = 0; i < select.length; i++) {
                                if (select[i].value == value) {
                                    select.value = value;
                                    break;
                                }
                            }
                        }
                    }
                });
            } else {
                if ($(locationType + '_shipping_discount_combined_profile_id_' + renderAccountId)) {
                    var value = EbayTemplateShippingObj.discountProfiles[renderAccountId]['selected'][locationType];
                    var html = EbayTemplateShippingObj.getDiscountProfilesHtml(locationType, renderAccountId);

                    $(locationType + '_shipping_discount_combined_profile_id_' + renderAccountId).update(html);

                    if (value && EbayTemplateShippingObj.discountProfiles[renderAccountId]['profiles'].length > 0) {
                        $(locationType + '_shipping_discount_combined_profile_id_' + renderAccountId).value = value;
                    }
                }
            }

        },

        getDiscountProfilesHtml: function (locationType, accountId)
        {
            var shippingModeSelect = $(locationType + '_shipping_mode');
            var desiredProfileType = null;
            var html = '<option value="">' + M2ePro.translator.translate('None') + '</option>';

            switch (parseInt(shippingModeSelect.value)) {
                case M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT'):
                    desiredProfileType = 'flat_shipping';
                    break;
                case M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED'):
                    desiredProfileType = 'calculated_shipping';
                    break;
            }

            if (desiredProfileType === null) {
                return html;
            }

            EbayTemplateShippingObj.discountProfiles[accountId]['profiles'].each(function (profile) {
                if (profile.type != desiredProfileType) {
                    return;
                }

                html += '<option value="' + profile.profile_id + '">' + profile.profile_name + '</option>';
            });

            return html;
        },

        // ---------------------------------------

        updateCashOnDeliveryCostVisibility: function ()
        {
            if (!$('cash_on_delivery_cost_cv_tr')) {
                return;
            }

            if (EbayTemplateShippingObj.isLocalShippingModeFlat()
                || EbayTemplateShippingObj.isLocalShippingModeCalculated()
            ) {
                $('cash_on_delivery_cost_cv_tr').show();
            } else {
                $('cash_on_delivery_cost_cv_tr').hide();
                $('cash_on_delivery_cost').value = '';
            }
        },

        // ---------------------------------------

        packageSizeChange: function ()
        {
            var self = EbayTemplateShippingObj;

            var packageSizeMode = this.value;

            $('package_size_mode').value = packageSizeMode;
            $('package_size_attribute').value = '';

            if (packageSizeMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::PACKAGE_SIZE_CUSTOM_VALUE')) {
                self.updateHiddenValue(this, $('package_size_value'));

                var showDimension = parseInt(this.options[this.selectedIndex].getAttribute('dimensions_supported'));
                self.updateDimensionVisibility(showDimension);
            } else if (packageSizeMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('package_size_attribute'));
                self.updateDimensionVisibility(true);
            } else if (packageSizeMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::PACKAGE_SIZE_NONE')) {
                self.updateHiddenValue(this, $('package_size_value'));
                self.updateDimensionVisibility(false);
            }
        },

        // ---------------------------------------

        updateDimensionVisibility: function (showDimension)
        {
            if (showDimension) {
                $('dimensions_tr').show();
                jQuery('#dimension_mode').trigger('change');
            } else {
                $('dimensions_tr').hide();
                $('dimension_mode').value = 0;
                jQuery('#dimension_mode').trigger('change');
            }
        },

        // ---------------------------------------

        dimensionModeChange: function ()
        {
            $$('.dimensions_ca_tr, .dimensions_cv_tr').invoke('hide');

            if (this.value != 0) {
                $$(this.value == 1 ? '.dimensions_cv_tr' : '.dimensions_ca_tr').invoke('show');
            }
        },

        // ---------------------------------------

        weightChange: function ()
        {
            var measurementNoteElement = this.up().next('.note');

            $('weight_cv').hide();
            measurementNoteElement.hide();

            var weightMode = this.value;

            $('weight_mode').value = weightMode;
            $('weight_attribute').value = '';

            if (weightMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::WEIGHT_CUSTOM_VALUE')) {
                $('weight_cv').show();
            } else if (weightMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::WEIGHT_CUSTOM_ATTRIBUTE')) {
                EbayTemplateShippingObj.updateHiddenValue(this, $('weight_attribute'));
                measurementNoteElement.show();
            }
        },

        // ---------------------------------------

        isMeasurementSystemEnglish: function ()
        {
            return $('measurement_system').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::MEASUREMENT_SYSTEM_ENGLISH');
        },

        measurementSystemChange: function ()
        {
            $$('.measurement-system-english, .measurement-system-metric').invoke('hide');

            if (EbayTemplateShippingObj.isMeasurementSystemEnglish()) {
                $$('.measurement-system-english').invoke('show');
            } else {
                $$('.measurement-system-metric').invoke('show');
            }
        },

        // ---------------------------------------

        updateMeasurementVisibility: function ()
        {
            EbayTemplateShippingObj.updatePackageBlockState();
            EbayTemplateShippingObj.prepareMeasurementObservers();
        },

        prepareMeasurementObservers: function ()
        {
            jQuery('#measurement_system')
                .on('change', EbayTemplateShippingObj.measurementSystemChange)
                .trigger('change');

            jQuery('#package_size')
                .on('change', EbayTemplateShippingObj.packageSizeChange)
                .trigger('change');

            jQuery('#dimension_mode')
                .on('change', EbayTemplateShippingObj.dimensionModeChange)
                .trigger('change');

            jQuery('#weight')
                .on('change', EbayTemplateShippingObj.weightChange)
                .trigger('change');
        },

        // ---------------------------------------

        serviceChange: function ()
        {
            var row = $(this).up('tr');

            if (this.up('table').id != 'shipping_international_table') {
                this.down(0).hide();
            }

            if (this.value === '') {
                row.select('.cost-mode')[0].hide();
                row.select('.shipping-cost-cv')[0].hide();
                row.select('.shipping-cost-ca')[0].hide();
                row.select('.shipping-cost-additional')[0].hide();
                row.select('.shipping-cost-additional-ca')[0].hide();
            } else {
                row.select('.cost-mode')[0].show();
                jQuery(row.select('.cost-mode')[0]).trigger('change');
            }
        },

        // ---------------------------------------

        serviceCostModeChange: function ()
        {
            var row = $(this).up('tr');
            // ---------------------------------------

            // ---------------------------------------
            var inputCostCV = row.select('.shipping-cost-cv')[0];
            var inputCostCA = row.select('.shipping-cost-ca')[0];
            var inputCostAddCV = row.select('.shipping-cost-additional')[0];
            var inputCostAddCA = row.select('.shipping-cost-additional-ca')[0];
            var inputPriority = row.select('.shipping-priority')[0];
            // ---------------------------------------

            // ---------------------------------------
            [inputCostCV, inputCostCA, inputCostAddCV, inputCostAddCA].invoke('hide');

            inputPriority.show();
            // ---------------------------------------

            // ---------------------------------------
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_VALUE')) {
                inputCostCV.show();
                inputCostCV.disabled = false;

                inputCostAddCV.show();
                inputCostAddCV.disabled = false;
            }
            // ---------------------------------------

            // ---------------------------------------
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_ATTRIBUTE')) {
                inputCostCA.show();
                inputCostAddCA.show();
            }
            // ---------------------------------------

            // ---------------------------------------
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_FREE')) {

                var isLocalMethod = /local/.test(row.id);

                if (isLocalMethod && EbayTemplateShippingObj.isLocalShippingModeCalculated()) {
                    inputPriority.value = 0;
                    inputCostCV.value = 0;
                    inputCostAddCV.value = 0;

                    [inputPriority, inputCostCV, inputCostAddCV].invoke('hide');

                } else {
                    inputCostCV.show();
                    inputCostCV.value = 0;
                    inputCostCV.disabled = true;

                    inputCostAddCV.show();
                    inputCostAddCV.value = 0;
                    inputCostAddCV.disabled = true;
                }
            }
            // ---------------------------------------
        },

        // ---------------------------------------

        shippingLocationChange: function ()
        {
            var i = this.name.match(/\d+/);
            var current = this;

            if (this.value != 'Worldwide') {
                return;
            }

            $$('input[name="shipping[shippingLocation][' + i + '][]"]').each(function (item) {
                if (current.checked && item != current) {
                    item.checked = false;
                    item.disabled = true;
                } else {
                    item.disabled = false;
                }
            });
        },

        // ---------------------------------------

        addRow: function (type) // local|international
        {
            var self = this;

            $('shipping_' + type + '_table').show();
            $('add_' + type + '_shipping_method_button').hide();

            var id = 'shipping_' + type + '_tbody';
            var i = EbayTemplateShippingObj.counter.total;

            // ---------------------------------------
            var tpl = $$('#block_listing_template_shipping_table_row_template_table tbody')[0].innerHTML;
            tpl = tpl.replace(/%i%/g, i);
            tpl = tpl.replace(/%type%/g, type);
            $(id).insert(tpl);
            // ---------------------------------------

            // ---------------------------------------
            var row = $('shipping_variant_' + type + '_' + i + '_tr');
            // ---------------------------------------

            // ---------------------------------------
            row.down('button.remove_shipping_method_button').addEventListener('click', self.removeRow.bind(this, type));
            // ---------------------------------------

            // ---------------------------------------
            AttributeObj.renderAttributesWithEmptyOption('shipping[shipping_cost_attribute][' + i + ']', row.down('.shipping-cost-ca'));
            var handlerObj = new AttributeCreator('shipping[shipping_cost_attribute][' + i + ']');
            handlerObj.setSelectObj($('shipping[shipping_cost_attribute][' + i + ']'));
            handlerObj.injectAddOption();

            AttributeObj.renderAttributesWithEmptyOption('shipping[shipping_cost_additional_attribute][' + i + ']', row.down('.shipping-cost-additional-ca'));
            var handlerObj = new AttributeCreator('shipping[shipping_cost_additional_attribute][' + i + ']');
            handlerObj.setSelectObj($('shipping[shipping_cost_additional_attribute][' + i + ']'));
            handlerObj.injectAddOption();
            // ---------------------------------------

            // ---------------------------------------
            EbayTemplateShippingObj.renderServices(row.select('.shipping-service')[0], type);
            EbayTemplateShippingObj.initRow(row);
            // ---------------------------------------

            // ---------------------------------------
            if (type == 'international') {
                tpl = $$('#block_shipping_table_locations_row_template_table tbody')[0].innerHTML;
                tpl = tpl.replace(/%i%/g, i);
                $(id).insert(tpl);
                EbayTemplateShippingObj.renderShipToLocationCheckboxes(i);
            }
            // ---------------------------------------

            // ---------------------------------------
            EbayTemplateShippingObj.counter[type]++;
            EbayTemplateShippingObj.counter.total++;
            // ---------------------------------------

            // ---------------------------------------
            if (type == 'local' && EbayTemplateShippingObj.counter[type] >= 4) {
                $(id).up('table').select('tfoot')[0].hide();
            }
            if (type == 'international' && EbayTemplateShippingObj.counter[type] >= 5) {
                $(id).up('table').select('tfoot')[0].hide();
            }
            // ---------------------------------------

            // ---------------------------------------
            var isAttributeMode = function (element) {
                return element.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE');
            };

            row.down('[name^="shipping[shipping_cost_attribute]"]').observe('change', function (event) {
                var element = event.target.up('tr').down('[name^="shipping[cost_mode]"]');

                if (!isAttributeMode(element)) {
                    return;
                }

                EbayTemplateShippingObj.checkMessages(type);
            });

            row.down('[name^="shipping[shipping_cost_additional_attribute]"]').observe('change', function (event) {
                var element = event.target.up('tr').down('[name^="shipping[cost_mode]"]');

                if (!isAttributeMode(element)) {
                    return;
                }

                EbayTemplateShippingObj.checkMessages(type);
            });
            // ---------------------------------------

            return row;
        },

        // ---------------------------------------

        initRow: function (row)
        {
            var locationType = /local/.test(row.id) ? 'local' : 'international';

            // ---------------------------------------
            if (EbayTemplateShippingObj.isShippingModeCalculated(locationType)) {
                row.select('.cost-mode')[0].value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CALCULATED');
                row.select('.shipping-mode-option-notcalc').invoke('remove');

                if (locationType == 'international' || $$('#shipping_local_tbody .cost-mode').length > 1) {
                    // only one calculated shipping method can have free mode
                    row.select('.shipping-mode-option-free').invoke('remove');
                }
            } else {
                row.select('.cost-mode')[0].value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_FREE');
                row.select('.shipping-mode-option-calc')[0].remove();
            }
            // ---------------------------------------

            // ---------------------------------------
            EbayTemplateShippingObj.renderServices(row.select('.shipping-service')[0], locationType);
            // ---------------------------------------

            // ---------------------------------------
            jQuery(row.select('.cost-mode')[0]).trigger('change');
            jQuery(row.select('.shipping-service')[0]).trigger('change');
            // ---------------------------------------
        },

        // ---------------------------------------

        renderServices: function (el, locationType)
        {
            var html = '';
            var isCalculated = EbayTemplateShippingObj.isShippingModeCalculated(locationType);
            var selectedPackage = $('package_size_value').value;
            var categoryMethods = '';

            // not selected international shipping service
            if (locationType == 'international') {
                html += '<option value="">--</option>';
            } else {
                html += '<option value="">' + M2ePro.translator.translate('Select Shipping Service') + '</option>';
            }

            if (Object.isArray(EbayTemplateShippingObj.shippingServices) && EbayTemplateShippingObj.shippingServices.length == 0) {
                $(el).update(html);
                return;
            }

            $H(EbayTemplateShippingObj.shippingServices).each(function (category) {

                categoryMethods = '';
                category.value.methods.each(function (service) {
                    var isServiceOfSelectedDestination = (locationType == 'local' && service.is_international == 0) || (locationType == 'international' && service.is_international == 1);
                    var isServiceOfSelectedType = (isCalculated && service.is_calculated == 1) || (!isCalculated && service.is_flat == 1);

                    if (!isServiceOfSelectedDestination || !isServiceOfSelectedType) {
                        return;
                    }

                    if (isCalculated) {
                        if (service.data.ShippingPackage.indexOf(selectedPackage) != -1) {
                            categoryMethods += '<option value="' + service.ebay_id + '">' + service.title + '</option>';
                        }

                        return;
                    }

                    categoryMethods += '<option value="' + service.ebay_id + '">' + service.title + '</option>';
                });

                if (categoryMethods != '') {
                    noCategoryTitle = category[0] == '';
                    if (noCategoryTitle) {
                        html += categoryMethods;
                    } else {
                        if (locationType == 'local') {
                            html += '<optgroup ebay_id="' + category.key + '" label="' + category.value.title + '">' + categoryMethods + '</optgroup>';
                        } else {
                            html += '<optgroup label="' + category.value.title + '">' + categoryMethods + '</optgroup>';
                        }

                    }
                }
            });

            $(el).update(html);
        },

        // ---------------------------------------

        renderShipToLocationCheckboxes: function (i)
        {
            var html = '';

            // ---------------------------------------
            EbayTemplateShippingObj.shippingLocations.each(function (location) {
                if (location.ebay_id == 'Worldwide') {
                    html += '<div style="margin-bottom: 10px;">' +
                        '<input' +
                        ' id="shipping_shippingLocation_' + location.ebay_id + '_' + i + '"' +
                        ' type="checkbox"' +
                        ' name="shipping[shippingLocation][' + i + '][]" value="' + location.ebay_id + '"' +
                        ' onclick="EbayTemplateShippingObj.shippingLocationChange.call(this);"' +
                        ' class="shipping-location M2ePro-validate-international-ship-to-location admin__control-checkbox"' +
                        '/>' +
                        '<label ' +
                        'for="shipping_shippingLocation_' + location.ebay_id + '_' + i + '"' +
                        'class="admin__field-label"' +
                        '>' +
                        '<span><b>' + location.title + '</b></span>' +
                        '</label>' +
                        '</div>';
                } else {
                    html +=
                        '<input' +
                        ' id="shipping_shippingLocation_' + location.ebay_id + '_' + i + '"' +
                        ' type="checkbox"' +
                        ' name="shipping[shippingLocation][' + i + '][]" value="' + location.ebay_id + '"' +
                        ' onclick="EbayTemplateShippingObj.shippingLocationChange.call(this);"' +
                        ' class="admin__control-checkbox"' +
                        '/>' +
                        '<label style="width: 142px; text-align: left; margin-bottom: 5px;" ' +
                        'class="nobr admin__field-label"' +
                        'for="shipping_shippingLocation_' + location.ebay_id + '_' + i + '"' +
                        '>' +
                        '<span>' + location.title + '</span>' +
                        '</label>';
                }
            });
            // ---------------------------------------

            // ---------------------------------------
            $$('#shipping_variant_locations_' + i + '_tr td')[0].innerHTML = '<div style="margin: 5px 10px">' + html + '</div>';
            $$('#shipping_variant_locations_' + i + '_tr td')[0].innerHTML += '<div style="clear: both; margin-bottom: 10px;" />';
            // ---------------------------------------

            if (!EbayTemplateShippingObj.shippingMethods[i]) {
                return;
            }

            // ---------------------------------------
            var locations = [];
            EbayTemplateShippingObj.shippingMethods[i].locations.each(function (item) {
                locations.push(item);
            });
            // ---------------------------------------

            // ---------------------------------------
            $$('input[name="shipping[shippingLocation][' + i + '][]"]').each(function (el) {
                if (locations.indexOf(el.value) != -1) {
                    el.checked = true;
                }
                jQuery(el).trigger('change');
            });
            // ---------------------------------------

            $$('input[value="Worldwide"]').each(function (element) {
                EbayTemplateShippingObj.shippingLocationChange.call(element);
            });
        },

        // ---------------------------------------

        removeRow: function (locationType, el)
        {
            var targetNode;

            if (el.nodeType === Node.ELEMENT_NODE) {
                targetNode = el;
            } else {
                targetNode = el.target;
            }

            var table = targetNode.up('table');

            if (locationType == 'international') {
                targetNode.up('tr').next().remove();
            }

            targetNode.up('tr').remove();

            EbayTemplateShippingObj.counter[locationType]--;

            if (EbayTemplateShippingObj.counter[locationType] == 0) {
                $('shipping_' + locationType + '_table').hide();
                $('add_' + locationType + '_shipping_method_button').show();
            }

            if (locationType == 'local' && EbayTemplateShippingObj.counter[locationType] < 4) {
                table.select('tfoot')[0].show();
            }
            if (locationType == 'international' && EbayTemplateShippingObj.counter[locationType] < 5) {
                table.select('tfoot')[0].show();
            }

            EbayTemplateShippingObj.updateMeasurementVisibility();
        },

        // ---------------------------------------

        hasMissingServiceAttribute: function (code, position)
        {
            if (typeof EbayTemplateShippingObj.missingAttributes['services'][position] == 'undefined') {
                return false;
            }

            if (typeof EbayTemplateShippingObj.missingAttributes['services'][position][code] == 'undefined') {
                return false;
            }

            return true;
        },

        addMissingServiceAttributeOption: function (select, code, position, value)
        {
            var option = document.createElement('option');

            option.value = value;
            option.innerHTML = EbayTemplateShippingObj.missingAttributes['services'][position][code];

            var first = select.down('.empty').next();

            first.insert({before: option});
        },

        renderShippingMethods: function (shippingMethods)
        {
            if (shippingMethods.length > 0) {
                $('shipping_local_table').show();
                $('add_local_shipping_method_button').hide();
            } else {
                $('shipping_local_table').hide();
                $('add_local_shipping_method_button').show();
            }

            shippingMethods.each(function (service, i) {

                var type = service.shipping_type == 1 ? 'international' : 'local';
                var row = EbayTemplateShippingObj.addRow(type);

                row.down('.shipping-service').value = service.shipping_value;
                row.down('.cost-mode').value = service.cost_mode;

                if (service.cost_mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_VALUE')) {
                    row.down('.shipping-cost-cv').value = service.cost_value;
                    row.down('.shipping-cost-additional').value = service.cost_additional_value;
                } else if (service.cost_mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_ATTRIBUTE')) {
                    if (EbayTemplateShippingObj.hasMissingServiceAttribute('cost_value', i)) {
                        EbayTemplateShippingObj.addMissingServiceAttributeOption(
                            row.down('.shipping-cost-ca select'), 'cost_value', i, service.cost_value
                        );
                    }

                    if (EbayTemplateShippingObj.hasMissingServiceAttribute('cost_additional_value', i)) {
                        EbayTemplateShippingObj.addMissingServiceAttributeOption(
                            row.down('.shipping-cost-additional-ca select'), 'cost_additional_value', i, service.cost_additional_value
                        );
                    }

                    row.down('.shipping-cost-ca select').value = service.cost_value;
                    row.down('.shipping-cost-additional-ca select').value = service.cost_additional_value;
                }

                row.down('.shipping-priority').value = service.priority;
                jQuery(row.down('.cost-mode')).trigger('change');
                jQuery(row.down('.shipping-service')).trigger('change');
            });
        },

        replaceSelectWithInputHidden: function (select)
        {
            var td = select.up('td');
            var label = select.options[select.selectedIndex].innerHTML;
            var input = '<input type="hidden" ' +
                'name="' + select.name + '" ' +
                'id="' + select.id + '" ' +
                'value="' + select.value + '" ' +
                'class="' + select.className + '" />';

            $(select).replace('');
            $(td).insert('<span>' + label + input + '</span>');

            if (td.down('.cost-mode')) {
                td.down('.cost-mode').observe('change', EbayTemplateShippingObj.serviceCostModeChange);
            }
        },

        // ---------------------------------------

        updatePackageBlockState: function ()
        {
            if (this.isLocalShippingModeCalculated() || this.isInternationalShippingModeCalculated()) {
                this.setCalculatedPackageBlockState();
            } else if (this.isLocalShippingModeLocal() || this.isLocalShippingModeFreight()) {
                this.setNonePackageBlockState();
            } else {
                this.setCommonPackageBlockState();
            }
        },

        setCalculatedPackageBlockState: function ()
        {
            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').show();

            jQuery('dimension_mode').trigger('change');

            if ($('package_size').selectedIndex == 0) {
                $('package_size').selectedIndex = 1;
            }

            $('package_size_none').hide();
            jQuery('package_size').trigger('change');

            if ($('weight').selectedIndex == 0) {
                $('weight').selectedIndex = 1;
            }

            $('weight_mode_none').hide();
            jQuery('weight').trigger('change');
        },

        setCommonPackageBlockState: function()
        {
            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').show();

            $('package_size_none').show();
            jQuery('package_size').trigger('change');

            $('weight_mode_none').show();
            jQuery('weight').trigger('change');

            jQuery('dimension_mode').trigger('change');
        },

        setNonePackageBlockState: function()
        {
            $('package_size_none').show();
            $('package_size').selectedIndex = 0;
            jQuery('package_size').trigger('change');

            $('dimension_mode').selectedIndex = 0;
            jQuery('dimension_mode').trigger('change');

            $('weight_mode_none').show();
            $('weight').selectedIndex = 0;
            jQuery('weight').trigger('change');

            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').hide();
        },

        // ---------------------------------------

        checkMessages: function (type)
        {
            if (typeof EbayListingTemplateSwitcherObj == 'undefined') {
                // not inside template switcher
                return;
            }

            var container, excludeTable, data, formElements = Form.getElements('template_shipping_data_container');

            if (type == 'local') {
                container = 'shipping_local_table_messages';
                excludeTable = $('shipping_international_table');

                formElements = formElements.map(function (element) {

                    if (element.up('table') == excludeTable) {
                        return false;
                    }

                    return element;
                }).filter(function (el) {
                    return el;
                });

                data = Form.serializeElements(formElements);

            } else if (type == 'international') {
                container = 'shipping_international_table_messages';
                excludeTable = $('shipping_local_table');

                formElements = formElements.map(function (element) {

                    if (element.up('table') == excludeTable) {
                        return false;
                    }

                    return element;
                }).filter(function (el) {
                    return el;
                });

                data = Form.serializeElements(formElements);

            } else {
                return;
            }

            var id = '',
                nick = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_SHIPPING'),
                storeId = EbayListingTemplateSwitcherObj.storeId,
                marketplaceId = EbayListingTemplateSwitcherObj.marketplaceId,
                callback = function () {
                    var refresh = $(container).down('a.refresh-messages');
                    if (refresh) {
                        refresh.observe('click', function () {
                            this.checkMessages();
                        }.bind(this))
                    }
                }.bind(this);

            TemplateManagerObj.checkMessages(
                id,
                nick,
                data,
                storeId,
                marketplaceId,
                container,
                callback
            );
        },

        clearMessages: function (type)
        {
            if (typeof EbayListingTemplateSwitcherObj == 'undefined') {
                // not inside template switcher
                return;
            }

            var container = type == 'local' ? 'shipping_local_table_messages' : 'shipping_international_table_messages';
            $(container).innerHTML = '';
        }

        // ---------------------------------------
    });
});
