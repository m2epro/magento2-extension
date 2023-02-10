define([
    'M2ePro/Common'
], function() {
    window.AmazonRepricer = Class.create(Common, {

        initialize: function() {
            var self = this;

            jQuery.validator.addMethod('M2ePro-account-repricing-price-value', function(value, el) {
                if (self.isFieldContainerHiddenFromPage(el)) {
                    return true;
                }

                if (!value.match(/^\d+[.]?\d*?$/g)) {
                    return false;
                }

                if (value <= 0) {
                    return false;
                }

                return true;

            }, M2ePro.translator.translate('Invalid input data. Decimal value required. Example 12.05'));

            jQuery.validator.addMethod('M2ePro-account-repricing-price-percent', function(value, el) {
                if (self.isFieldContainerHiddenFromPage(el)) {
                    return true;
                }

                if (!value.match(/^\d+$/g)) {
                    return false;
                }

                if (value <= 0 || value > 100) {
                    return false;
                }

                return true;

            }, M2ePro.translator.translate('Please enter correct value.'));
        },

        initObservers: function() {
            if ($('regular_price_mode')) {
                $('regular_price_mode')
                        .observe('change', AmazonRepricerObj.regular_price_mode_change)
                        .simulate('change');
            }

            if ($('min_price_mode')) {
                $('min_price_mode')
                        .observe('change', AmazonRepricerObj.min_price_mode_change)
                        .simulate('change');
            }

            if ($('max_price_mode')) {
                $('max_price_mode')
                        .observe('change', AmazonRepricerObj.max_price_mode_change)
                        .simulate('change');
            }

            if ($('disable_mode')) {
                $('disable_mode')
                        .observe('change', AmazonRepricerObj.disable_mode_change)
                        .simulate('change');
            }
        },

        linkOrRegisterRepricing: function(id) {
            return setLocation(M2ePro.url.get('amazon_repricer/linkOrRegister', {
                id: id,
            }));
        },

        unlinkRepricing: function(id) {
            this.confirm({
                actions: {
                    confirm: function() {
                        AmazonRepricerObj.openUnlinkPage(id);
                    },
                    cancel: function() {
                        $$('.action_select').forEach(function (obj) {
                            obj.value = 0;
                        })
                        return false;
                    }
                }
            });
        },

        openUnlinkPage: function(id) {
            return setLocation(M2ePro.url.get('amazon_repricer/openUnlinkPage', {
                id: id,
            }));
        },

        regular_price_mode_change: function() {
            var self = AmazonRepricerObj,
                    regularPriceAttr = $('regular_price_attribute'),
                    regularPriceCoeficient = $('regular_price_coefficient_td'),
                    variationRegularPrice = $('regular_price_variation_mode_tr');

            regularPriceAttr && (regularPriceAttr.value = '');
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, regularPriceAttr);
            }

            regularPriceCoeficient.hide();
            variationRegularPrice.hide();

            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_MANUAL') &&
                    this.value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::REGULAR_PRICE_MODE_PRODUCT_POLICY')) {

                regularPriceCoeficient.show();
                variationRegularPrice.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_MANUAL')) {
                $$('.repricing-min-price-mode-regular-depended').each(function(element) {
                    if (element.selected) {
                        element.up().selectedIndex = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_MANUAL');
                        element.simulate('change');
                    }

                    element.hide();
                });

                $$('.repricing-max-price-mode-regular-depended').each(function(element) {
                    if (element.selected) {
                        element.up().selectedIndex = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_MANUAL');
                        element.simulate('change');
                    }

                    element.hide();
                });
            } else {
                $$('.repricing-min-price-mode-regular-depended').each(function(element) {
                    element.show();
                });

                $$('.repricing-max-price-mode-regular-depended').each(function(element) {
                    element.show();
                });
            }
        },

        min_price_mode_change: function() {
            var self = AmazonRepricerObj,
                    minPriceValueTr = $('min_price_value_tr'),
                    minPricePercentTr = $('min_price_percent_tr'),
                    minPriceWarning = $('min_price_warning_tr'),
                    minPriceAttr = $('min_price_attribute'),
                    minPriceCoeficient = $('min_price_coefficient_td'),
                    variationMinPrice = $('min_price_variation_mode_tr');

            minPriceWarning.hide();
            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_MANUAL')) {
                minPriceWarning.show();
            }

            minPriceCoeficient.hide();
            variationMinPrice.hide();

            minPriceAttr && (minPriceAttr.value = '');
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, minPriceAttr);

                minPriceCoeficient.show();
                variationMinPrice.show();
            }

            minPriceValueTr.hide();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::MIN_PRICE_MODE_REGULAR_VALUE')) {
                minPriceValueTr.show();
            }

            minPricePercentTr.hide();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::MIN_PRICE_MODE_REGULAR_PERCENT')) {
                minPricePercentTr.show();
            }
        },

        max_price_mode_change: function() {
            var self = AmazonRepricerObj,
                    maxPriceValueTr = $('max_price_value_tr'),
                    maxPricePercentTr = $('max_price_percent_tr'),
                    maxPriceWarning = $('max_price_warning_tr'),
                    maxPriceAttr = $('max_price_attribute'),
                    maxPriceCoeficient = $('max_price_coefficient_td'),
                    variationMaxPrice = $('max_price_variation_mode_tr');

            maxPriceWarning.hide();
            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_MANUAL')) {
                maxPriceWarning.show();
            }

            maxPriceCoeficient.hide();
            variationMaxPrice.hide();

            maxPriceAttr && (maxPriceAttr.value = '');
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, maxPriceAttr);

                maxPriceCoeficient.show();
                variationMaxPrice.show();
            }

            maxPriceValueTr.hide();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::MAX_PRICE_MODE_REGULAR_VALUE')) {
                maxPriceValueTr.show();
            }

            maxPricePercentTr.hide();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::MAX_PRICE_MODE_REGULAR_PERCENT')) {
                maxPricePercentTr.show();
            }
        },

        disable_mode_change: function() {
            var self = AmazonRepricerObj,
                    disableModeAttr = $('disable_mode_attribute');

            disableModeAttr && (disableModeAttr.value = '');
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account_Repricing::DISABLE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, disableModeAttr);
            }
        },

        saveAndEditClick: function(url)
        {
            if (!this.isValidForm()) {
                return;
            }

            if (typeof url == 'undefined' || url === '') {
                url = M2ePro.url.get('formSubmit', {'back': base64_encode('edit')});
            }

            this.submitForm(url);
        },

        saveAndCloseClick: function() {
            if (!this.isValidForm()) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('formSubmit'), {
                method: 'post',
                parameters: Form.serialize($('edit_form')),
                onSuccess: function(transport) {
                    var result = transport.responseText.evalJSON();

                    if (result.status) {
                        window.close();
                    } else {
                        console.error('Repricer Settings Saving Error');
                    }
                }
            });
        },
    });
});
