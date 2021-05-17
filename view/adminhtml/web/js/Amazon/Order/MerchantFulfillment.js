define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function(modal) {
    window.AmazonOrderMerchantFulfillment = Class.create(Common, {

        // ---------------------------------------

        orderId: null,
        validateCustomDimension: true,
        cachedFields: {},

        // ---------------------------------------

        initialize: function() {
            var self = this;

            jQuery.validator.addMethod('M2ePro-validate-must-arrive-date', function(value) {
                return value.match('^[0-9]{4}-[0-9]{2}-[0-9]{1,2}$');
            }, M2ePro.translator.translate('Please enter a valid date.'));

            jQuery.validator.addMethod('M2ePro-validate-dimension', function(value) {
                return value != M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::DIMENSION_SOURCE_NONE');
            }, M2ePro.translator.translate('Please select an option.'));

            jQuery.validator.addMethod('M2ePro-validate-weight', function(value) {
                return value != M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::WEIGHT_SOURCE_NONE');
            }, M2ePro.translator.translate('Please select an option.'));

            jQuery.validator.addMethod('M2ePro-validate-required-custom-dimension', function(value, element) {
                if (self.validateCustomDimension) {
                    var validationResult = Validation.get('M2ePro-required-when-visible').test(value, element);
                    self.validateCustomDimension = validationResult;
                    return validationResult;
                } else {
                    return true;
                }
            }, M2ePro.translator.translate('This is a required fields.'));

            jQuery.validator.addMethod('M2ePro-validate-custom-dimension', function(value) {
                if (self.validateCustomDimension) {
                    var validationResult = Validation.get('validate-greater-than-zero').test(value);
                    self.validateCustomDimension = validationResult;
                    return validationResult;
                } else {
                    return true;
                }
            }, M2ePro.translator.translate('Please enter a number greater than 0 in this fields.'));
        },

        // ---------------------------------------

        openPopUp: function(content, buttons = [], type = 'slide') {
            var self = this;

            self.closePopUp();

            var containerEl = $('shipping_services_popUp');

            if (containerEl) {
                containerEl.remove();
            }

            $('html-body').insert({bottom: '<div id="shipping_services_popUp"></div>'});
            $('shipping_services_popUp').update(content);

            self.shippingServicesPopUp = jQuery('#shipping_services_popUp');

            modal({
                title: M2ePro.translator.translate('Amazon\'s Shipping Services'),
                type: type,
                buttons: buttons
            }, self.shippingServicesPopUp);

            self.shippingServicesPopUp.modal('openModal');
        },

        closePopUp: function() {
            if (this.shippingServicesPopUp) {
                this.shippingServicesPopUp.modal('closeModal');
            }
        },

        // ---------------------------------------

        validate: function() {
            this.validateCustomDimension = true;

            var validationResult = true;

            this.shippingServicesPopUp.find('form').each(function() {
                validationResult = validationResult && jQuery(this).validation().valid();
            });

            return validationResult;
        },

        // ---------------------------------------

        getPopupAction: function(orderId) {
            var self = this;

            if (orderId && this.orderId != orderId) {
                this.orderId = orderId;
            }

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/getPopup', {order_id: this.orderId}), {
                method: 'post',
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true);

                    if (data.status) {
                        self.openPopUp(data.html, [{
                            text: M2ePro.translator.translate('Cancel'),
                            click: function() {
                                self.closePopUp();
                            }
                        }, {
                            text: M2ePro.translator.translate('Continue'),
                            class: 'action primary',
                            click: function() {
                                self.getShippingServicesAction();
                            }
                        }]);
                        self.cacheForm(false);
                    } else {
                        self.openPopUp(data.html, [{
                            text: M2ePro.translator.translate('Close'),
                            click: function() {
                                self.closePopUp();
                            }
                        }], 'popup');
                    }
                }
            });
        },

        getShippingServicesAction: function() {
            if (!this.validate()) {
                return;
            }

            this.cacheForm(true);

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/getShippingServices', {order_id: this.orderId}), {
                method: 'post',
                parameters: Form.serialize($('order_merchantFulfillment_configuration')),
                onSuccess: function(transport) {
                    self.openPopUp(transport.responseText, [{
                        text: M2ePro.translator.translate('Back'),
                        class: 'back',
                        click: function() {
                            self.getPopupAction();
                        }
                    }, {
                        text: M2ePro.translator.translate('Continue'),
                        class: 'action primary shipping-services-continue-btn',
                        click: function() {
                            self.createShippingOfferAction();
                        }
                    }]);

                    $$('.shipping-services-continue-btn')[0].disable();
                }
            });
        },

        createShippingOfferAction: function() {
            if (!confirm(M2ePro.translator.translate('Are you sure you want to create Shipment now?'))) {
                return;
            }

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/createShippingOffer', {order_id: this.orderId}), {
                method: 'post',
                parameters: Form.serialize($('shippingServices_form')),
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true);

                    var buttons = [{
                        text: M2ePro.translator.translate('Close'),
                        click: function() {
                            self.closePopUp();
                        }
                    }];

                    if (data.show_try_again_btn === true) {
                        buttons.unshift({
                            text: M2ePro.translator.translate('Use Amazon\'s Shipping Services'),
                            class: 'back',
                            click: function() {
                                self.resetDataAction();
                            }
                        });
                    }

                    self.openPopUp(transport.responseText, buttons);
                }
            });
        },

        cancelShippingOfferAction: function() {
            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/cancelShippingOffer', {order_id: this.orderId}), {
                method: 'post',
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true);

                    if (data['success']) {
                        self.getPopupAction();
                    } else {
                        alert('Internal error: ' + data['error_message']);
                    }
                }
            });
        },

        refreshDataAction: function() {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/refreshData', {order_id: this.orderId}), {
                method: 'post',
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true);

                    if (data['success']) {
                        self.getPopupAction();
                    } else {
                        alert('Internal error: ' + data['error_message']);
                    }
                }
            });
        },

        resetDataAction: function() {
            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/resetData', {order_id: this.orderId}), {
                method: 'post',
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true);

                    if (data['success']) {
                        self.getPopupAction();
                    } else {
                        alert('Internal error.');
                    }

                }
            });
        },

        getShippingLabelAction: function() {
            this.openWindow(M2ePro.url.get('amazon_order_merchantFulfillment/getLabel', {
                order_id: this.orderId
            }));
        },

        markAsShippedAction: function(orderId) {
            var self = this;
            this.orderId = orderId;

            new Ajax.Request(M2ePro.url.get('amazon_order_merchantFulfillment/markAsShipped', {order_id: this.orderId}), {
                method: 'post',
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true);

                    if (data['success']) {
                        setLocation(
                            M2ePro.url.get('amazon_order/updateShippingStatus', {
                                id: self.orderId
                            })
                        );
                    } else {
                        self.openPopUp(data['html'], {width: 400});
                    }
                }
            });
        },

        useMerchantFulfillmentAction: function() {
            this.closePopUp();
            this.getPopupAction();
        },

        // ---------------------------------------

        onPopupScroll: function() {
            if (!$('must_arrive_by_date')) {
                return;
            }

            var bounds = $('must_arrive_by_date').getBoundingClientRect();

            Calendar.setup({
                inputField: "must_arrive_by_date",
                ifFormat: "%Y-%m-%d",
                singleClick: true,
                cache: true,
                position: [
                    bounds.left + bounds.width,
                    bounds.top + window.scrollY
                ]
            });

            var calendar = $$('.calendar');

            if (calendar.length) {
                calendar = calendar[0];
                calendar.hide();
            }
        },

        shippingServicesChange: function() {
            $$('.shipping-services-continue-btn')[0].enable();
        },

        packageDimensionSourceChange: function() {
            var packageDimensionSource = $('package_dimension_source'),
                selectedOption = packageDimensionSource.options[packageDimensionSource.selectedIndex],
                packageDimensionCustom = $('package_dimension_custom'),
                packageDimensionCustomValue = $('package_dimension_custom_value'),
                packageDimensionCustomAttribute = $('package_dimension_custom_attribute'),
                packageDimensionPredefined = $('package_dimension_predefined');

            packageDimensionCustom.hide();
            packageDimensionCustomValue.hide();
            packageDimensionCustomAttribute.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::DIMENSION_SOURCE_NONE')) {
                packageDimensionCustom.hide();
                packageDimensionPredefined.value = '';
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::DIMENSION_SOURCE_CUSTOM')) {
                packageDimensionCustom.show();
                packageDimensionCustomValue.show();
                packageDimensionPredefined.value = selectedOption.getAttribute('dimension_code');
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::DIMENSION_SOURCE_CUSTOM_ATTRIBUTE')) {
                packageDimensionCustom.show();
                packageDimensionCustomAttribute.show();
                packageDimensionPredefined.value = selectedOption.getAttribute('dimension_code');
            } else {
                $('package_dimension_width').clear();
                $('package_dimension_length').clear();
                $('package_dimension_height').clear();
                packageDimensionPredefined.value = selectedOption.getAttribute('dimension_code');

                if (packageDimensionPredefined.value.match(M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::VIRTUAL_PREDEFINED_PACKAGE'))) {

                    $('package_dimension_width').value = selectedOption.getAttribute('width');
                    $('package_dimension_length').value = selectedOption.getAttribute('length');
                    $('package_dimension_height').value = selectedOption.getAttribute('height');
                    $('package_dimension_measure').value = selectedOption.getAttribute('unit');
                    $('package_weight_measure').value = selectedOption.getAttribute('weight_unit');

                    packageDimensionCustom.show();
                }
            }
        },

        fulfillmentPackageWeightChange: function() {
            var customAttribute = $('package_weight_custom_attribute'),
                customValueTr = $('package_weight_custom_value_tr');

            customValueTr.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon_MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_ATTRIBUTE')) {
                AmazonOrderMerchantFulfillmentObj.updateHiddenValue(this, customAttribute);
            }
        },

        shippingCountryChange: function() {
            new Ajax.Request(M2ePro.url.get('order/getCountryRegions'), {
                method: 'post',
                parameters: {
                    country: this.value
                },
                onSuccess: function(transport) {
                    var data = transport.responseText.evalJSON(true),
                        regionStateInput = $('ship_from_address_region_state_input'),
                        regionStateSelect = $('ship_from_address_region_state_select');

                    regionStateInput.hide();
                    regionStateInput.disable();
                    regionStateSelect.hide();
                    regionStateSelect.disable();

                    if (data.length === 0) {
                        regionStateInput.show();
                        regionStateInput.enable();
                    } else {
                        regionStateSelect.show();
                        regionStateSelect.enable();

                        var regionStateSelectValue = regionStateSelect.value;
                        regionStateSelect.update();

                        var optionHtml = '';
                        data.each(function(item) {
                            var selected = '';
                            // if (item.id == regionStateSelectValue || item.label == regionStateSelectValue) {
                            //     selected = 'selected="selected"';
                            // }
                            optionHtml += '<option value="' + item.label + '" ' + selected + '>' + item.label + '</option>';
                        });

                        regionStateSelect.update(optionHtml);
                        regionStateSelect.value = regionStateSelectValue;

                        var firstOption = regionStateSelect.select('option:first')[0];
                        firstOption.value = '';
                        firstOption.hide();
                    }
                }
            });
        },

        // ---------------------------------------

        cacheForm: function(isSerialize) {
            var self = this;
            var fieldsToCache = [
                'must_arrive_by_date',
                'declared_value',
                'package_dimension_source',
                'package_dimension_predefined',
                'package_dimension_length',
                'package_dimension_width',
                'package_dimension_height',
                'package_dimension_length_custom_attribute',
                'package_dimension_width_custom_attribute',
                'package_dimension_height_custom_attribute',
                'package_dimension_measure',
                'package_weight_source',
                'package_weight_custom_value',
                'package_weight_custom_attribute',
                'package_weight_measure'
            ];

            if (isSerialize) {
                this.cachedFields.cached = true;
                fieldsToCache.forEach(function(field) {
                    if ($(field)) {
                        self.cachedFields[field] = $(field).value;
                    }
                });
            } else if (this.cachedFields.hasOwnProperty('cached')) {
                fieldsToCache.forEach(function(field) {
                    if ($(field)) {
                        $(field).value = self.cachedFields[field];
                    }
                });
                $('package_dimension_predefined').simulate('change');
            }
        }

        // ---------------------------------------
    });
});
