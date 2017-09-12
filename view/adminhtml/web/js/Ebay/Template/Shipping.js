define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function (jQuery, modal) {
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

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-location-or-postal-required', function() {
                return $('address_mode').value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::ADDRESS_MODE_NONE') ||
                    $('postal_code_mode').value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::POSTAL_CODE_MODE_NONE');
            }, M2ePro.translator.translate('Location or Zip/Postal Code should be specified.'));

            jQuery.validator.addMethod('M2ePro-validate-international-ship-to-location', function(value, el) {
                return $$('input[name="'+el.name+'"]').any(function(o) {
                    return o.checked;
                });
            }, M2ePro.translator.translate('Select one or more international ship-to Locations.'));

            jQuery.validator.addMethod('M2ePro-required-if-calculated', function(value) {

                if(EbayTemplateShippingObj.isLocalShippingModeCalculated() ||
                    EbayTemplateShippingObj.isInternationalShippingModeCalculated()) {
                    return value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::POSTAL_CODE_MODE_NONE');
                }

                return true;
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-shipping-methods', function(value, el) {

                var locationType = /local/.test(el.id) ? 'local' : 'international',
                    shippingModeValue = $(locationType + '_shipping_mode').value;

                shippingModeValue = parseInt(shippingModeValue);

                if (shippingModeValue !== M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT') &&
                    shippingModeValue !== M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED')) {
                    return true;
                }

                return EbayTemplateShippingObj.counter[locationType] != 0;
            },  M2ePro.translator.translate('You should specify at least one Shipping Method.'));

            jQuery.validator.addMethod('M2ePro-validate-shipping-service', function(value, el) {

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
        },

        initObservers: function()
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

            EbayTemplateShippingObj.prepareMeasurementObservers('local');

            jQuery('#dispatch_time')
                .on('change', EbayTemplateShippingObj.dispatchTimeChange)
                .trigger('change');

            if ($('click_and_collect_mode')) {
                jQuery('#click_and_collect_mode')
                    .on('change', EbayTemplateShippingObj.clickAndCollectModeChange)
                    .trigger('change');
            }

            if ($('cross_border_trade')) {
                jQuery('#cross_border_trade')
                    .on('change', EbayTemplateShippingObj.crossBorderTradeChange)
                    .trigger('change');
            }

            jQuery('#international_shipping_mode')
                .on('change', EbayTemplateShippingObj.internationalShippingModeChange)
                .trigger('change');

            jQuery('.shipping_excluded_location_region_link').each(function(){
                jQuery(this).on('click', EbayTemplateShippingObj.checkExcludeLocationsRegionsSelection)
                    .on('mouseover', function(event){ this.down('label').style.textDecoration = 'underline'; })
                    .on('mouseout', function(event){ this.down('label').style.textDecoration = 'none'; });
            });

            jQuery('.shipping_excluded_location').each(function(){
                jQuery(this).on('change', EbayTemplateShippingObj.selectExcludeLocation);
            });

            EbayTemplateShippingObj.renderShippingMethods(EbayTemplateShippingObj.shippingMethods);

            EbayTemplateShippingObj.checkMessages('local');
            EbayTemplateShippingObj.checkMessages('international');
        },

        // ---------------------------------------

        countryModeChange : function()
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

        postalCodeModeChange: function()
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

        addressModeChange: function()
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

        dispatchTimeChange: function()
        {
            if (!$('click_and_collect_mode')) {
                return;
            }

            if (this.value > 3 || (!EbayTemplateShippingObj.isLocalShippingModeFlat()
                && !EbayTemplateShippingObj.isLocalShippingModeCalculated())
            ) {
                $('click_and_collect_mode_tr').hide();
                $('click_and_collect_mode').selectedIndex = 1;
                jQuery('#click_and_collect_mode').trigger('change');

                return;
            }

            $('click_and_collect_mode_tr').show();
            jQuery('#click_and_collect_mode').trigger('change');
        },

        // ---------------------------------------

        localShippingModeChange: function()
        {
            // ---------------------------------------
            $('magento_block_ebay_template_shipping_form_data_international-wrapper').hide();
            $('local_shipping_methods_tr').hide();
            $('magento_block_ebay_template_shipping_form_data_excluded_locations-wrapper').show();
            // ---------------------------------------

            // clear selected shipping methods
            // ---------------------------------------
            $$('#shipping_local_tbody .icon-btn').each(function(el) {
                EbayTemplateShippingObj.removeRow.call(el, 'local');
            });
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeFlat()
                || EbayTemplateShippingObj.isLocalShippingModeCalculated()
            ) {
                $$('.local-shipping-tr').invoke('show');
                jQuery('#dispatch_time').trigger('change');

                $('domestic_shipping_fieldset-wrapper').setStyle({
                    borderBottom: null
                });
            } else {
                $$('.local-shipping-tr').invoke('hide');
                $('domestic_shipping_fieldset-wrapper').setStyle({
                    borderBottom: '0px'
                });

                if ($('click_and_collect_mode')) {
                    $('click_and_collect_mode').selectedIndex = 1;
                    jQuery('#click_and_collect_mode').trigger('change');
                }
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
                EbayTemplateShippingObj.resetExcludeLocationsList();
            }
            // ---------------------------------------

            // ---------------------------------------
            if (EbayTemplateShippingObj.isLocalShippingModeLocal()) {
                $('international_shipping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_NO_INTERNATIONAL');
                jQuery('#international_shipping_mode').trigger('change');

                $('magento_block_ebay_template_shipping_form_data_excluded_locations-wrapper').hide();
                EbayTemplateShippingObj.resetExcludeLocationsList();
            }
            // ---------------------------------------
        },

        isLocalShippingModeFlat: function()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT');
        },

        isLocalShippingModeCalculated: function()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED');
        },

        isLocalShippingModeFreight: function()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FREIGHT');
        },

        isLocalShippingModeLocal: function()
        {
            return $('local_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_LOCAL');
        },

        // ---------------------------------------

        hasSurcharge: function(locationType)
        {
            var marketplaceId = $$('[name="shipping[marketplace_id]"]')[0];
            return locationType == 'local' && marketplaceId  && ['1', '9'].indexOf(marketplaceId.value) != -1;
        },

        // ---------------------------------------

        internationalShippingModeChange: function()
        {
            // clear selected shipping methods
            // ---------------------------------------
            $$('#shipping_international_tbody .icon-btn').each(function(el) {
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
                EbayTemplateShippingObj.deleteExcludedLocation('international', 'type', 'excluded_locations_hidden');
                EbayTemplateShippingObj.updateExcludedLocationsTitles('excluded_locations_titles');

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

        isInternationalShippingModeFlat: function()
        {
            return $('international_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT');
        },

        isInternationalShippingModeCalculated: function()
        {
            return $('international_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_CALCULATED');
        },

        isInternationalShippingModeNoInternational: function()
        {
            return $('international_shipping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_NO_INTERNATIONAL');
        },

        getCalculatedLocationType: function()
        {
            if (EbayTemplateShippingObj.isLocalShippingModeCalculated()) {
                return 'local';
            }

            if (EbayTemplateShippingObj.isInternationalShippingModeCalculated()) {
                return 'international';
            }

            return null;
        },

        isShippingModeCalculated: function(locationType)
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

        isClickAndCollectEnabled: function()
        {
            if (!$('click_and_collect_mode')) {
                return false;
            }

            return $('click_and_collect_mode').value == 1;
        },

        // ---------------------------------------

        crossBorderTradeChange: function()
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

        updateCrossBorderTradeVisibility: function()
        {
            if(!$('magento_block_ebay_template_shipping_form_data_cross_border_trade-wrapper')) {
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

        updateRateTableVisibility: function(locationType)
        {
            var shippingMode = $(locationType + '_shipping_mode').value;

            if (!$(locationType+'_shipping_rate_table_mode_tr')) {
                return;
            }

            if (shippingMode != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping::SHIPPING_TYPE_FLAT')) {
                $(locationType+'_shipping_rate_table_mode_tr').hide();
                $(locationType+'_shipping_rate_table_mode').value = 0;
            } else {
                $(locationType+'_shipping_rate_table_mode_tr').show();
            }
        },

        isRateTableEnabled: function()
        {
            var local = $('local_shipping_rate_table_mode'),
                international = $('international_shipping_rate_table_mode');

            if (!local && !international) {
                return false;
            }

            return (local && local.value != 0) ||
                (international && international.value != 0);
        },

        rateTableModeChange: function()
        {
            var absoluteHide = !!(!EbayTemplateShippingObj.isLocalShippingModeFlat() ||
            EbayTemplateShippingObj.isRateTableEnabled());
            $$('[id^="shipping_variant_cost_surcharge_"]').each(function(surchargeRow) {
                var row = surchargeRow.previous('tr');

                // for template without data
                if (!row) {
                    return;
                }

                var inputCostSurchargeCV = surchargeRow.select('.shipping-cost-surcharge')[0];
                var inputCostSurchargeCA = surchargeRow.select('.shipping-cost-surcharge-ca')[0];

                inputCostSurchargeCV.hide();
                inputCostSurchargeCA.hide();

                if (absoluteHide || !(/(FedEx|UPS)/.test(row.select('.shipping-service')[0].value)) ||
                    row.select('.cost-mode')[0].value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_FREE')) {
                    surchargeRow.hide();
                } else {
                    surchargeRow.show();

                    if (row.select('.cost-mode')[0].value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_VALUE')) {
                        inputCostSurchargeCV.show();
                        inputCostSurchargeCV.disabled = false;
                    } else if (row.select('.cost-mode')[0].value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_ATTRIBUTE')) {
                        inputCostSurchargeCA.show();
                    }
                }
            });

            EbayTemplateShippingObj.updatePackageBlockState();
        },

        // ---------------------------------------

        clickAndCollectModeChange: function()
        {
            EbayTemplateShippingObj.updatePackageBlockState();
        },

        // ---------------------------------------

        updateLocalHandlingCostVisibility: function()
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

        updateInternationalHandlingCostVisibility: function()
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

        updateDiscountProfiles: function(accountId)
        {
            new Ajax.Request(M2ePro.url.get('ebay_template_shipping/updateDiscountProfiles'), {
                method: 'get',
                parameters: {
                    'account_id': accountId
                },
                onSuccess: function(transport) {
                    EbayTemplateShippingObj.discountProfiles[accountId]['profiles'] = transport.responseText.evalJSON(true);
                    EbayTemplateShippingObj.renderDiscountProfiles('local', accountId);
                    EbayTemplateShippingObj.renderDiscountProfiles('international', accountId);
                }
            });
        },

        renderDiscountProfiles: function(locationType, renderAccountId)
        {
            if (typeof renderAccountId == 'undefined') {
                $$('.' + locationType + '-discount-profile-account-tr').each(function(account) {
                    var accountId = account.readAttribute('account_id');

                    if ($(locationType + '_shipping_discount_profile_id_' + accountId)) {
                        var value = EbayTemplateShippingObj.discountProfiles[accountId]['selected'][locationType];

                        var html = EbayTemplateShippingObj.getDiscountProfilesHtml(locationType, accountId);
                        $(locationType + '_shipping_discount_profile_id_' + accountId).update(html);

                        if (value && EbayTemplateShippingObj.discountProfiles[accountId]['profiles'].length > 0) {
                            var select = $(locationType + '_shipping_discount_profile_id_' + accountId);

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
                if ($(locationType + '_shipping_discount_profile_id_' + renderAccountId)) {
                    var value = EbayTemplateShippingObj.discountProfiles[renderAccountId]['selected'][locationType];
                    var html = EbayTemplateShippingObj.getDiscountProfilesHtml(locationType, renderAccountId);

                    $(locationType + '_shipping_discount_profile_id_' + renderAccountId).update(html);

                    if (value && EbayTemplateShippingObj.discountProfiles[renderAccountId]['profiles'].length > 0) {
                        $(locationType + '_shipping_discount_profile_id_' + renderAccountId).value = value;
                    }
                }
            }

        },

        getDiscountProfilesHtml: function(locationType, accountId)
        {
            var shippingModeSelect = $(locationType + '_shipping_mode');
            var desiredProfileType = null;
            var html = '<option value="">'+M2ePro.translator.translate('None')+'</option>';

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

            EbayTemplateShippingObj.discountProfiles[accountId]['profiles'].each(function(profile) {
                if (profile.type != desiredProfileType) {
                    return;
                }

                html += '<option value="'+profile.profile_id+'">'+profile.profile_name+'</option>';
            });

            return html;
        },

        // ---------------------------------------

        updateCashOnDeliveryCostVisibility: function()
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

        packageSizeChange: function()
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
            }
        },

        // ---------------------------------------

        updateDimensionVisibility: function(showDimension)
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

        dimensionModeChange: function()
        {
            $$('.dimensions_ca_tr, .dimensions_cv_tr').invoke('hide');

            if (this.value != 0) {
                $$(this.value == 1 ? '.dimensions_cv_tr' : '.dimensions_ca_tr').invoke('show');
            }
        },

        // ---------------------------------------

        weightChange: function()
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

        isMeasurementSystemEnglish: function()
        {
            return $('measurement_system').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Calculated::MEASUREMENT_SYSTEM_ENGLISH');
        },

        measurementSystemChange: function()
        {
            $$('.measurement-system-english, .measurement-system-metric').invoke('hide');

            if (EbayTemplateShippingObj.isMeasurementSystemEnglish()) {
                $$('.measurement-system-english').invoke('show');
            } else {
                $$('.measurement-system-metric').invoke('show');
            }
        },

        // ---------------------------------------

        updateMeasurementVisibility: function()
        {
            if (EbayTemplateShippingObj.isLocalShippingModeCalculated()) {
                EbayTemplateShippingObj.showMeasurementOptions('local', 'calculated');
                EbayTemplateShippingObj.updatePackageBlockState();
                return;
            }

            if (EbayTemplateShippingObj.isInternationalShippingModeCalculated()) {
                EbayTemplateShippingObj.showMeasurementOptions('international', 'calculated');
                EbayTemplateShippingObj.updatePackageBlockState();
                return;
            }

            if (EbayTemplateShippingObj.isLocalShippingModeFlat()
                && EbayTemplateShippingObj.isRateTableEnabled()
            ) {
                EbayTemplateShippingObj.showMeasurementOptions('local', 'flat');
            }

            EbayTemplateShippingObj.updatePackageBlockState();
        },

        showMeasurementOptions: function(locationType, shippingMode)
        {
            $$('#block_shipping_template_calculated_options tr').each(function(element) {
                if (element.hasClassName('visible-for-'+shippingMode+'-by-default')) {
                    element.show();
                } else {
                    element.hide();
                }
            });

            EbayTemplateShippingObj.prepareMeasurementObservers(shippingMode);
        },

        prepareMeasurementObservers: function(shippingMode)
        {
            jQuery('#measurement_system')
                .on('change', EbayTemplateShippingObj.measurementSystemChange)
                .trigger('change');

            if (shippingMode == 'calculated') {
                jQuery('#package_size')
                    .on('change', EbayTemplateShippingObj.packageSizeChange)
                    .trigger('change');
            }

            if ($('dimension_mode')) {
                jQuery('#dimension_mode')
                    .on('change', EbayTemplateShippingObj.dimensionModeChange)
                    .trigger('change');
            }

            jQuery('#weight')
                .on('change', EbayTemplateShippingObj.weightChange)
                .trigger('change');
        },

        // ---------------------------------------

        serviceChange: function()
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

        serviceCostModeChange: function()
        {
            var row = $(this).up('tr');

            // ---------------------------------------
            var surchargeRow = $('shipping_variant_cost_surcharge_' + this.name.match(/\d+/) + '_tr');

            if (EbayTemplateShippingObj.isLocalShippingModeFlat() && surchargeRow) {
                var inputCostSurchargeCV = surchargeRow.select('.shipping-cost-surcharge')[0];
                var inputCostSurchargeCA = surchargeRow.select('.shipping-cost-surcharge-ca')[0];

                if (!EbayTemplateShippingObj.isRateTableEnabled() &&
                    /(FedEx|UPS)/.test(row.select('.shipping-service')[0].value)) {
                    surchargeRow.show();
                } else {
                    surchargeRow.hide();
                }
            }
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
            if (surchargeRow) {
                inputCostSurchargeCV.hide();
                inputCostSurchargeCA.hide();
            }

            inputPriority.show();
            // ---------------------------------------

            // ---------------------------------------
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_VALUE')) {
                inputCostCV.show();
                inputCostCV.disabled = false;

                inputCostAddCV.show();
                inputCostAddCV.disabled = false;

                if (surchargeRow && !EbayTemplateShippingObj.isRateTableEnabled()) {
                    inputCostSurchargeCV.show();
                    inputCostSurchargeCV.disabled = false;
                }
            }
            // ---------------------------------------

            // ---------------------------------------
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_ATTRIBUTE')) {
                inputCostCA.show();
                inputCostAddCA.show();
                surchargeRow && !EbayTemplateShippingObj.isRateTableEnabled() && inputCostSurchargeCA.show();
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

                if (surchargeRow) {
                    inputCostSurchargeCV.hide();
                    inputCostSurchargeCA.hide();

                    surchargeRow.hide();
                }
            }
            // ---------------------------------------
        },

        // ---------------------------------------

        shippingLocationChange: function()
        {
            var i = this.name.match(/\d+/);
            var current = this;

            if (this.value != 'Worldwide') {
                return;
            }

            $$('input[name="shipping[shippingLocation][' + i + '][]"]').each(function(item) {
                if (current.checked && item != current) {
                    item.checked = false;
                    item.disabled = true;
                } else {
                    item.disabled = false;
                }
            });
        },

        // ---------------------------------------

        addRow: function(type) // local|international
        {
            $('shipping_'+type+'_table').show();
            $('add_'+type+'_shipping_method_button').hide();

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
            if (EbayTemplateShippingObj.isLocalShippingModeFlat() &&
                EbayTemplateShippingObj.hasSurcharge(type)) {

                tpl = $$('#block_shipping_table_cost_surcharge_row_template_table tbody')[0].innerHTML;
                tpl = tpl.replace(/%i%/g, i);
                $(id).insert(tpl);

                AttributeObj.renderAttributesWithEmptyOption(
                    'shipping[shipping_cost_surcharge_attribute][' + i + ']',
                    $('shipping_variant_cost_surcharge_' + i + '_tr').down('.shipping-cost-surcharge-ca'));
                $('shipping[shipping_cost_surcharge_attribute][' + i + ']').insert({
                    top: new Element('option', {selected: true}).update(M2ePro.translator.translate('None'))
                });
                var handlerObj = new AttributeCreator('shipping[shipping_cost_surcharge_attribute][' + i + ']');
                handlerObj.setSelectObj($('shipping[shipping_cost_surcharge_attribute][' + i + ']'));
                handlerObj.injectAddOption();
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
            var isAttributeMode = function(element) {
                return element.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE');
            };

            row.down('[name^="shipping[shipping_cost_attribute]"]').observe('change', function(event) {
                var element = event.target.up('tr').down('[name^="shipping[cost_mode]"]');

                if (!isAttributeMode(element)) {
                    return;
                }

                EbayTemplateShippingObj.checkMessages(type);
            });

            row.down('[name^="shipping[shipping_cost_additional_attribute]"]').observe('change', function(event) {
                var element = event.target.up('tr').down('[name^="shipping[cost_mode]"]');

                if (!isAttributeMode(element)) {
                    return;
                }

                EbayTemplateShippingObj.checkMessages(type);
            });

            if (type == 'local') {

                var next = row.next("[id^='shipping_variant_cost_surcharge']");

                if (next) {
                    next.down('[name^="shipping[shipping_cost_surcharge_attribute]"]').observe('change', function(event) {
                        var element = row.down('[name^="shipping[cost_mode]"]');

                        if (!isAttributeMode(element)) {
                            return;
                        }

                        EbayTemplateShippingObj.checkMessages(type);
                    });
                }
            }
            // ---------------------------------------

            return row;
        },

        // ---------------------------------------

        initRow: function(row)
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

        renderServices: function(el, locationType)
        {
            var html = '';
            var isCalculated = EbayTemplateShippingObj.isShippingModeCalculated(locationType);
            var selectedPackage = $('package_size_value').value;
            var categoryMethods = '';

            // not selected international shipping service
            if (locationType == 'international') {
                html += '<option value="">--</option>';
            } else {
                html += '<option value="">'+ M2ePro.translator.translate('Select Shipping Service') +'</option>';
            }

            if (Object.isArray(EbayTemplateShippingObj.shippingServices) && EbayTemplateShippingObj.shippingServices.length == 0) {
                $(el).update(html);
                return;
            }

            $H(EbayTemplateShippingObj.shippingServices).each(function(category) {

                categoryMethods = '';
                category.value.methods.each(function(service) {
                    var isServiceOfSelectedDestination = (locationType == 'local' && service.is_international == 0) || (locationType == 'international' && service.is_international == 1);
                    var isServiceOfSelectedType = (isCalculated && service.is_calculated == 1) || (! isCalculated && service.is_flat == 1);

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
                            html += '<optgroup ebay_id="'+category.key+'" label="' + category.value.title + '">' + categoryMethods + '</optgroup>';
                        } else {
                            html += '<optgroup label="' + category.value.title + '">' + categoryMethods + '</optgroup>';
                        }

                    }
                }
            });

            $(el).update(html);
        },

        // ---------------------------------------

        renderShipToLocationCheckboxes: function(i)
        {
            var html = '';

            // ---------------------------------------
            EbayTemplateShippingObj.shippingLocations.each(function(location) {
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
                        'for="shipping_shippingLocation_'+ location.ebay_id + '_' + i + '"' +
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
                        'for="shipping_shippingLocation_'+ location.ebay_id + '_' + i + '"' +
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
            EbayTemplateShippingObj.shippingMethods[i].locations.each(function(item) {
                locations.push(item);
            });
            // ---------------------------------------

            // ---------------------------------------
            $$('input[name="shipping[shippingLocation][' + i + '][]"]').each(function(el) {
                if (locations.indexOf(el.value) != -1) {
                    el.checked = true;
                }
                jQuery(el).trigger('change');
            });
            // ---------------------------------------

            $$('input[value="Worldwide"]').each(function(element) {
                EbayTemplateShippingObj.shippingLocationChange.call(element);
            });
        },

        // ---------------------------------------

        removeRow: function(locationType)
        {
            var table = $(this).up('table');

            if (locationType == 'international') {
                $(this).up('tr').next().remove();
            }

            if (EbayTemplateShippingObj.hasSurcharge(locationType)) {
                var i = $(this).up('tr').id.match(/\d+/);
                var next = $(this).up('tr').next('[id=shipping_variant_cost_surcharge_' + i + '_tr]');
                next && next.remove();
            }

            $(this).up('tr').remove();

            EbayTemplateShippingObj.counter[locationType]--;

            if (EbayTemplateShippingObj.counter[locationType] == 0) {
                $('shipping_'+locationType+'_table').hide();
                $('add_'+locationType+'_shipping_method_button').show();
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

        hasMissingServiceAttribute: function(code, position)
        {
            if (typeof EbayTemplateShippingObj.missingAttributes['services'][position] == 'undefined') {
                return false;
            }

            if (typeof EbayTemplateShippingObj.missingAttributes['services'][position][code] == 'undefined') {
                return false;
            }

            return true;
        },

        addMissingServiceAttributeOption: function(select, code, position, value)
        {
            var option = document.createElement('option');

            option.value = value;
            option.innerHTML = EbayTemplateShippingObj.missingAttributes['services'][position][code];

            var first = select.down('.empty').next();

            first.insert({ before: option });
        },

        renderShippingMethods: function(shippingMethods)
        {
            if (shippingMethods.length > 0) {
                $('shipping_local_table').show();
                $('add_local_shipping_method_button').hide();
            } else {
                $('shipping_local_table').hide();
                $('add_local_shipping_method_button').show();
            }

            shippingMethods.each(function(service, i) {

                var type = service.shipping_type == 1 ? 'international' : 'local';
                var row = EbayTemplateShippingObj.addRow(type);
                var surchargeRow = $('shipping_variant_cost_surcharge_' + i + '_tr');

                row.down('.shipping-service').value = service.shipping_value;
                row.down('.cost-mode').value = service.cost_mode;

                if (service.cost_mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Shipping\\Service::COST_MODE_CUSTOM_VALUE')) {
                    row.down('.shipping-cost-cv').value = service.cost_value;
                    row.down('.shipping-cost-additional').value = service.cost_additional_value;

                    if (surchargeRow) {
                        surchargeRow.down('.shipping-cost-surcharge').value = service.cost_surcharge_value;
                    }

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

                    if (surchargeRow) {
                        surchargeRow.down('.shipping-cost-surcharge-ca select').value = service.cost_surcharge_value;
                    }

                }

                row.down('.shipping-priority').value = service.priority;
                jQuery(row.down('.cost-mode')).trigger('change');
                jQuery(row.down('.shipping-service')).trigger('change');
            });
        },

        replaceSelectWithInputHidden: function(select)
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

        initExcludeListPopup: function()
        {
            var element = jQuery('#magento_block_ebay_template_shipping_form_data_exclude_locations_popup');

            modal({
                title: M2ePro.translator.translate('Excluded Shipping Locations'),
                type: 'slide',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    click: function () {
                        element.modal('closeModal');
                    }
                },{
                    text: M2ePro.translator.translate('Save'),
                    class: 'action-primary',
                    id: 'save_popup_button',
                    click: function () {
                        EbayTemplateShippingObj.saveExcludeLocationsList();
                        element.modal('closeModal');
                    }
                }]
            }, element);
        },

        showExcludeListPopup: function()
        {
            var self = EbayTemplateShippingObj;

            self.updatePopupData();
            self.checkExcludeLocationSelection();

            self.afterInitPopupActions();

            jQuery('#magento_block_ebay_template_shipping_form_data_exclude_locations_popup').modal('openModal');
        },

        // ---------------------------------------

        updatePopupData: function()
        {
            $('excluded_locations_popup_hidden').value = $('excluded_locations_hidden').value;
            EbayTemplateShippingObj.updateExcludedLocationsTitles();
        },

        checkExcludeLocationSelection: function()
        {
            var self = EbayTemplateShippingObj,
                excludedLocations = $('excluded_locations_popup_hidden').value.evalJSON();

            $$('.shipping_excluded_location').each(function(el) { el.checked = 0; });

            $$('.shipping_excluded_location').each(function(el) {

                for (var i = 0; i < excludedLocations.length; i++) {
                    if (excludedLocations[i]['code'] == el.value) {
                        el.checked = 1;
                        el.hasClassName('shipping_excluded_region') && self.selectExcludedLocationAllRegion(el.value, 1);
                    }
                }
            });

            EbayTemplateShippingObj.updateExcludedLocationsSelectedRegions();
        },

        selectExcludedLocationAllRegion: function(regionCode, checkBoxState)
        {
            $$('div[id="shipping_excluded_location_international_region_' + regionCode + '"] .shipping_excluded_location').each(function(el) {
                el.checked = checkBoxState;
            });
        },

        afterInitPopupActions: function()
        {
            var firstNavigationLink = $$('.shipping_excluded_location_region_link').shift();
            firstNavigationLink && jQuery(firstNavigationLink).trigger('click');

            EbayTemplateShippingObj.isInternationalShippingModeNoInternational()
                ? $('exclude_locations_popup_international').hide()
                : $('exclude_locations_popup_international').show();

            EbayTemplateShippingObj.updatePopupSizes();
        },

        updatePopupSizes: function()
        {
            var popupHeight = '445px',
                popupGeneralContentMinHeight = '380px';

            if (EbayTemplateShippingObj.isInternationalShippingModeNoInternational()) {
                popupHeight = '280px';
                popupGeneralContentMinHeight = '200px';
            }

            $('excluded_locations_popup_content_general').setStyle({ 'min-height': popupGeneralContentMinHeight });

            if ($('exclude_locations_international_regions')) {
                var standartRegionHeight = $('exclude_locations_international_regions').getHeight();
                $('exclude_locations_international_locations').setStyle({ 'height': standartRegionHeight + 'px' });
            }
        },

        // ---------------------------------------

        saveExcludeLocationsList: function()
        {
            var title          = $('excluded_locations_popup_titles').innerHTML,
                titleContainer = $('excluded_locations_titles');

            title == M2ePro.translator.translate('None')
                ? titleContainer.innerHTML = M2ePro.translator.translate('No Locations are currently excluded.')
                : titleContainer.innerHTML = title;

            $('excluded_locations_hidden').value = $('excluded_locations_popup_hidden').value;
        },

        resetExcludeLocationsList: function(window)
        {
            window = window || 'general';

            if (window == 'general') {
                $('excluded_locations_hidden').value = '[]';
                $('excluded_locations_titles').innerHTML = M2ePro.translator.translate('No Locations are currently excluded.');
                return;
            }

            $('excluded_locations_popup_hidden').value = '[]';
            EbayTemplateShippingObj.updateExcludedLocationsTitles();
            EbayTemplateShippingObj.checkExcludeLocationSelection();
        },

        // ---------------------------------------

        selectExcludeLocation: function()
        {
            EbayTemplateShippingObj.updateExcludedLocationsHiddenInput(this);
            EbayTemplateShippingObj.updateExcludedLocationsTitles();
            EbayTemplateShippingObj.updateExcludedLocationsSelectedRegions();
        },

        updateExcludedLocationsHiddenInput: function(element)
        {
            var self = EbayTemplateShippingObj,
                asia = $('shipping_excluded_location_international_Asia');

            if (element.hasClassName('shipping_excluded_region')) {

                element.checked
                    ? self.processRegionWasSelected(element) : self.processRegionWasDeselected(element);

                self.processRelatedRegions(element);

            } else {

                element.checked
                    ? self.processOneLocationWasSelected(element) : self.processOneLocationWasDeselected(element);

                if (self.isChildAsiaRegion(element.getAttribute('region'))) {
                    self.processAsiaChildRegion(element);
                }
            }

            if (self.isAllLocationsOfAsiaAreSelected() && !asia.checked) {
                asia.checked = 1;
                self.processRegionWasSelected($(asia));
            }
        },

        // ---------------------------------------

        processRegionWasSelected: function(regionCheckBox)
        {
            var self = EbayTemplateShippingObj,

                code   = regionCheckBox.value,
                title  = regionCheckBox.next().innerHTML,
                region = regionCheckBox.getAttribute('region'),
                type   = regionCheckBox.getAttribute('location_type');

            self.selectExcludedLocationAllRegion(code, 1);
            self.deleteExcludedLocation(code, 'region');
            self.addExcludedLocation(code, title, region, type);
        },

        processRegionWasDeselected: function(regionCheckBox)
        {
            var self = EbayTemplateShippingObj,
                code = regionCheckBox.value;

            self.selectExcludedLocationAllRegion(code, 0);
            self.deleteExcludedLocation(code);
        },

        processRelatedRegions: function(regionCheckBox)
        {
            var self = EbayTemplateShippingObj;

            if (self.isAsiaRegion(regionCheckBox.value)) {
                self.processAsiaRegion(regionCheckBox);
            }

            if (self.isChildAsiaRegion(regionCheckBox.value)) {
                self.processAsiaChildRegion(regionCheckBox);
            }
        },

        processAsiaRegion: function(regionCheckBox)
        {
            var self = EbayTemplateShippingObj;

            var middleEast = $('shipping_excluded_location_international_Middle East'),
                southeastAsia = $('shipping_excluded_location_international_Southeast Asia');

            if (regionCheckBox.checked) {

                if (!middleEast.checked) {
                    middleEast.checked = 1;
                    self.processRegionWasSelected(middleEast);
                }

                if (!southeastAsia.checked) {
                    southeastAsia.checked = 1;
                    self.processRegionWasSelected(southeastAsia);
                }

                return;
            }

            middleEast.checked = 0;
            southeastAsia.checked = 0;

            self.processRegionWasDeselected(middleEast);
            self.processRegionWasDeselected(southeastAsia);
        },

        processAsiaChildRegion: function(regionCheckBox)
        {
            var self = EbayTemplateShippingObj,
                asia = $('shipping_excluded_location_international_Asia');

            if (!regionCheckBox.checked && asia.checked) {

                var code = asia.value;

                asia.checked = 0;
                self.deleteExcludedLocation(code, 'code');

                $$('div[id="shipping_excluded_location_international_region_' + code + '"] .shipping_excluded_location').each(function(el) {
                    el.checked = 1;
                    self.addExcludedLocation(el.value, el.next().innerHTML, el.getAttribute('region'), el.getAttribute('type'));
                });
            }
        },

        processOneLocationWasSelected: function(locationCheckBox)
        {
            var self = EbayTemplateShippingObj,

                code   = locationCheckBox.value,
                title  = locationCheckBox.next().innerHTML,
                region = locationCheckBox.getAttribute('region'),
                type   = locationCheckBox.getAttribute('location_type');

            self.addExcludedLocation(code, title, region, type);

            if (!self.isAllLocationsOfRegionAreSelected(region)) {
                return;
            }

            if (self.isAsiaRegion(region) && !self.isAllLocationsOfAsiaAreSelected()) {
                return;
            }

            var regionTitle = $('shipping_excluded_location_international_' + region).next('label').innerHTML;

            $('shipping_excluded_location_international_' + region).checked = 1;
            self.deleteExcludedLocation(region, 'region');
            self.addExcludedLocation(region, regionTitle, null, type);
        },

        processOneLocationWasDeselected: function(locationCheckBox)
        {
            var self = EbayTemplateShippingObj,

                code   = locationCheckBox.value,
                region = locationCheckBox.getAttribute('region'),
                type   = locationCheckBox.getAttribute('location_type');

            self.deleteExcludedLocation(code);

            if (region == null) {
                return;
            }

            self.deleteExcludedLocation(region);
            self.deleteExcludedLocation(region, 'region');

            $('shipping_excluded_location_international_' + region).checked = 0;

            var result = self.getLocationsByRegion(region);
            result['locations'].each(function(el) {
                self.addExcludedLocation(el.value, el.next().innerHTML, region, type);
            });
        },

        // ---------------------------------------

        updateExcludedLocationsTitles: function(sourse)
        {
            sourse = sourse || 'excluded_locations_popup_titles';

            var excludedLocations = $(sourse.replace('titles','hidden')).value.evalJSON(),
                title = sourse == 'excluded_locations_popup_titles'
                    ? M2ePro.translator.translate('None')
                    : M2ePro.translator.translate('No Locations are currently excluded.');

            if (excludedLocations.length) {

                title = [];

                excludedLocations.each(function(location) {
                    var currentTitle = EbayTemplateShippingObj.isRootLocation(location)
                        ? '<b>' + location['title'] + '</b>' : location['title'];
                    title.push(currentTitle);
                });

                title = title.join(', ');
            }

            $('excluded_locations_reset_link').show();
            if (sourse == 'excluded_locations_popup_titles' && title == M2ePro.translator.translate('None')) {
                $('excluded_locations_reset_link').hide()
            }

            $(sourse).innerHTML = title;
        },

        updateExcludedLocationsSelectedRegions: function()
        {
            $$('.shipping_excluded_location_region_link').each(function(el) {

                var locations = EbayTemplateShippingObj.getLocationsByRegion(el.getAttribute('region'));

                el.removeClassName('have_selected_locations');
//            if (locations['total'] != locations['selected'] && locations['selected'] > 0) {
                if (locations['selected'] > 0 && !el.children[0].checked) {
                    el.addClassName('have_selected_locations');
                    el.down('span', 1).innerHTML = '(' + locations['selected'] + ' ' + M2ePro.translator.translate('selected') + ')';
                }
            });
        },

        // ---------------------------------------

        getLocationsByRegion: function(regionCode)
        {
            if (regionCode == null) {
                return false;
            }

            var totalCount    = 0,
                selectedLocations = [];

            $$('div[id="shipping_excluded_location_international_region_' + regionCode + '"] .shipping_excluded_location').each(function(el) {
                totalCount ++;
                el.checked && selectedLocations.push(el);
            });

            return {total: totalCount, selected: selectedLocations.length, locations: selectedLocations};
        },

        isAllLocationsOfRegionAreSelected: function(regionCode)
        {
            var locations = EbayTemplateShippingObj.getLocationsByRegion(regionCode);

            if (!locations) {
                return false;
            }

            return locations['total'] == locations['selected'];
        },

        isAllLocationsOfAsiaAreSelected: function()
        {
            var asiaLocations = EbayTemplateShippingObj.getLocationsByRegion('Asia'),
                eastLocations = EbayTemplateShippingObj.getLocationsByRegion('Middle East'),
                southLocations = EbayTemplateShippingObj.getLocationsByRegion('Southeast Asia');

            if (!asiaLocations || !eastLocations || !southLocations) {
                return false;
            }

            return asiaLocations['total'] == asiaLocations['selected'] &&
                eastLocations['total'] == eastLocations['selected'] &&
                southLocations['total'] == southLocations['selected'];
        },

        isRootLocation: function(location)
        {
            return !!(location['region'] == null);
        },

        isAsiaRegion: function(location)
        {
            return location == 'Asia';
        },

        isChildAsiaRegion: function(location)
        {
            return location == 'Middle East' || location == 'Southeast Asia';
        },

        // ---------------------------------------

        addExcludedLocation: function(code, title, region, type, sourse)
        {
            sourse = sourse || 'excluded_locations_popup_hidden';

            var excludedLocations = $(sourse).value.evalJSON();
            var item = {
                code: code,
                title: title,
                region: region,
                type: type
            };

            excludedLocations.push(item);
            $(sourse).value = Object.toJSON(excludedLocations);
        },

        deleteExcludedLocation: function(code, key, sourse)
        {
            key = key || 'code';
            sourse = sourse || 'excluded_locations_popup_hidden';

            var excludedLocations  = $(sourse).value.evalJSON(),
                resultAfterDelete  = [];

            for (var i = 0; i < excludedLocations.length; i++) {
                if (excludedLocations[i][key] != code) {
                    resultAfterDelete.push(excludedLocations[i]);
                }
            }
            $(sourse).value = Object.toJSON(resultAfterDelete);
        },

        // ---------------------------------------

        checkExcludeLocationsRegionsSelection: function()
        {
            $$('.shipping_excluded_location_region').invoke('hide');
            $$('.shipping_excluded_location_region_link').invoke('removeClassName','selected_region');

            $('shipping_excluded_location_international_region_' + this.getAttribute('region')).show();
            this.addClassName('selected_region');
        },

        // ---------------------------------------

        updatePackageBlockState: function()
        {
            if (this.isLocalShippingModeCalculated() || this.isInternationalShippingModeCalculated()) {
                this.setCalculatedPackageBlockState();
                return;
            }

            if (this.isClickAndCollectEnabled() &&
                (this.isLocalShippingModeFlat() || this.isLocalShippingModeCalculated()) &&
                $('dispatch_time').value <= 3
            ) {
                this.setClickAndCollectPackageBlockState();
                return;
            }

            if (this.isRateTableEnabled()) {
                this.setRateTablePackageBlockState();
                return;
            }

            this.setNonePackageBlockState();
        },

        setCalculatedPackageBlockState: function()
        {
            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').show();

            var dimensionsTr = $('dimensions_tr');
            var dimensionSelect = $('dimension_mode');
            if (dimensionsTr) {
                dimensionsTr.show();
                jQuery(dimensionSelect).trigger('change');
            }

            var packageSizeTr = $('package_size_tr');
            var packageSizeSelect = $('package_size');
            if (packageSizeTr) {
                packageSizeTr.show();
                jQuery(packageSizeSelect).trigger('change');
            }

            var weightTr = $('weight_tr');
            var weightSelect = $('weight');
            if (weightTr) {
                if ($('weight').selectedIndex == 0) {
                    $('weight').selectedIndex = 1;
                }

                weightTr.show();
                $('weight_mode_none').hide();
                jQuery(weightSelect).trigger('change');
            }
        },

        setRateTablePackageBlockState: function()
        {
            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').show();

            var dimensionsTr = $('dimensions_tr');
            var dimensionSelect = $('dimension_mode');
            if (dimensionsTr) {
                dimensionsTr.hide();
                dimensionSelect.selectedIndex = 0;
                jQuery(dimensionSelect).trigger('change');
            }

            var packageSizeTr = $('package_size_tr');
            var packageSizeSelect = $('package_size');
            if (packageSizeTr) {
                packageSizeTr.hide();
                packageSizeSelect.selectedIndex = 0;
                jQuery(packageSizeSelect).trigger('change');
            }

            var weightTr = $('weight_tr');
            var weightSelect = $('weight');
            if (weightTr) {
                weightTr.show();
                $('weight_mode_none').show();
                jQuery(weightSelect).trigger('change');
            }
        },

        setClickAndCollectPackageBlockState: function()
        {
            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').show();

            var dimensionsTr = $('dimensions_tr');
            var dimensionSelect = $('dimension_mode');
            if (dimensionsTr) {
                dimensionsTr.show();
                jQuery(dimensionSelect).trigger('change');
            }

            var packageSizeTr = $('package_size_tr');
            var packageSizeSelect = $('package_size');
            if (packageSizeTr) {
                packageSizeTr.hide();
                packageSizeSelect.selectedIndex = 0;
                jQuery(packageSizeSelect).trigger('change');
            }

            var weightTr = $('weight_tr');
            var weightSelect = $('weight');
            if (weightTr) {
                weightTr.show();
                $('weight_mode_none').show();
                jQuery(weightSelect).trigger('change');
            }
        },

        setNonePackageBlockState: function()
        {
            $('magento_block_ebay_template_shipping_form_data_calculated-wrapper').hide();

            var dimensionsTr = $('dimensions_tr');
            var dimensionSelect = $('dimension_mode');
            if (dimensionsTr) {
                dimensionSelect.selectedIndex = 0;
                jQuery(dimensionSelect).trigger('change');
            }

            var weightTr = $('weight_tr');
            var weightSelect = $('weight');
            if (weightTr) {
                weightSelect.selectedIndex = 0;
                jQuery(weightSelect).trigger('change');
            }
        },

        // ---------------------------------------

        checkMessages: function(type)
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
                }).filter(function(el) { return el; });

                data = Form.serializeElements(formElements);

            } else if (type == 'international') {
                container = 'shipping_international_table_messages';
                excludeTable = $('shipping_local_table');

                formElements = formElements.map(function (element) {

                    if (element.up('table') == excludeTable) {
                        return false;
                    }

                    return element;
                }).filter(function(el) { return el; });

                data = Form.serializeElements(formElements);

            } else {
                return;
            }

            var id = '',
                nick = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_SHIPPING'),
                storeId = EbayListingTemplateSwitcherObj.storeId,
                marketplaceId = EbayListingTemplateSwitcherObj.marketplaceId,
                checkAttributesAvailability = false,
                callback = function() {
                    var refresh = $(container).down('a.refresh-messages');
                    if (refresh) {
                        refresh.observe('click', function() {
                            this.checkMessages();
                        }.bind(this))
                    }
                }.bind(this);

            TemplateHandlerObj.checkMessages(
                id,
                nick,
                data,
                storeId,
                marketplaceId,
                checkAttributesAvailability,
                container,
                callback
            );
        },

        clearMessages: function(type)
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