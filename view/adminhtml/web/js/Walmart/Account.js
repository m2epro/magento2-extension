define([
    'M2ePro/Common'
], function() {

    window.WalmartAccount = Class.create(Common, {

        // ---------------------------------------

        initValidation: function() {
            this.setValidationCheckRepetitionValue(
                'M2ePro-account-title',
                M2ePro.translator.translate('The specified Title is already used for other Account. Account Title must be unique.'),
                'Account', 'title', 'id',
                M2ePro.formData.id,
                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK')
            );

            jQuery.validator.addMethod('M2ePro-account-customer-id', function(value) {

                var checkResult = false;

                if ($('magento_orders_customer_id_container').getStyle('display') == 'none') {
                    return true;
                }

                new Ajax.Request(M2ePro.url.get('general/checkCustomerId'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        customer_id: value,
                        id: M2ePro.formData.id
                    },
                    onSuccess: function(transport) {
                        checkResult = transport.responseText.evalJSON()['ok'];
                    }
                });

                return checkResult;
            }, M2ePro.translator.translate('No Customer entry is found for specified ID.'));

            jQuery.validator.addMethod(
                'M2ePro-require-select-attribute',
                function(value, el) {
                    if ($('other_listings_mapping_mode').value == 0) {
                        return true;
                    }

                    var isAttributeSelected = false;

                    $$('.attribute-mode-select').each(function(obj) {
                        if (obj.value != 0) {
                            isAttributeSelected = true;
                        }
                    });

                    return isAttributeSelected;
                },
                M2ePro.translator.translate(
                    'If Yes is chosen, you must select at least one Attribute for Product Linking.'
                )
            );

            jQuery.validator.addMethod('M2ePro-validate-price-coefficient', function(value) {

                if (value == '') {
                    return true;
                }

                if (value == '0' || value == '0%') {
                    return false;
                }

                return value.match(/^[+-]?\d+[.]?\d*[%]?$/g);
            }, M2ePro.translator.translate('Coefficient is not valid.'));
        },

        initTokenValidation: function() {
            var self = this;

            jQuery.validator.addMethod('M2ePro-marketplace-merchant', function(value, el) {

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                // reset error message to the default
                this.error = M2ePro.translator.translate('M2E Pro was not able to get access to the Walmart Account');

                var marketplace_id = $('marketplace_id').value;

                var params = [];

                if (marketplace_id == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::MARKETPLACE_CA')) {
                    params = {
                        consumer_id: $('consumer_id').value,
                        private_key: $('private_key').value,
                        marketplace_id: marketplace_id
                    };
                } else {
                    params = {
                        client_id: $('client_id').value,
                        client_secret: $('client_secret').value,
                        marketplace_id: marketplace_id
                    };
                }

                var checkResult = false;
                var checkReason = null;

                new Ajax.Request(M2ePro.url.get('walmart_account/checkAuth'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: params,
                    onSuccess: function(transport) {
                        var response = transport.responseText.evalJSON();
                        checkResult = response['result'];
                        checkReason = response['reason'];
                    }
                });

                if (checkReason != null && typeof checkReason != 'undefined') {
                    this.error = M2ePro.translator.translate('M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%').replace('%error_message%', checkReason);
                }

                return checkResult;

            }, M2ePro.translator.translate('M2E Pro was not able to get access to the Walmart Account'));
        },

        initObservers: function() {
            $('marketplace_id')
                .observe('change', WalmartAccountObj.changeMarketplace)
                .simulate('change');

            $('other_listings_synchronization')
                .observe('change', WalmartAccountObj.other_listings_synchronization_change)
                .simulate('change');
            $('other_listings_mapping_mode')
                .observe('change', WalmartAccountObj.other_listings_mapping_mode_change)
                .simulate('change');

            $('mapping_sku_mode')
                .observe('change', WalmartAccountObj.mapping_sku_mode_change)
                .simulate('change');
            $('mapping_upc_mode')
                .observe('change', WalmartAccountObj.mapping_upc_mode_change)
                .simulate('change');
            $('mapping_gtin_mode')
                .observe('change', WalmartAccountObj.mapping_gtin_mode_change)
                .simulate('change');
            $('mapping_wpid_mode')
                .observe('change', WalmartAccountObj.mapping_wpid_mode_change)
                .simulate('change');
            $('mapping_title_mode')
                .observe('change', WalmartAccountObj.mapping_title_mode_change)
                .simulate('change');

            $('magento_orders_listings_mode')
                .observe('change', WalmartAccountObj.magentoOrdersListingsModeChange)
                .simulate('change');
            $('magento_orders_listings_store_mode')
                .observe('change', WalmartAccountObj.magentoOrdersListingsStoreModeChange)
                .simulate('change');

            $('magento_orders_listings_other_mode')
                .observe('change', WalmartAccountObj.magentoOrdersListingsOtherModeChange)
                .simulate('change');
            $('magento_orders_listings_other_product_mode')
                .observe('change', WalmartAccountObj.magentoOrdersListingsOtherProductModeChange);

            $('magento_orders_number_source')
                .observe('change', WalmartAccountObj.magentoOrdersNumberSourceChange)
                .simulate('change');
            $('magento_orders_number_prefix_prefix')
                .observe('keyup', WalmartAccountObj.magentoOrdersNumberPrefixPrefixChange)
                .simulate('change');
            WalmartAccountObj.renderOrderNumberExample();

            $('magento_orders_customer_mode')
                .observe('change', WalmartAccountObj.magentoOrdersCustomerModeChange)
                .simulate('change');
            $('magento_orders_status_mapping_mode')
                .observe('change', WalmartAccountObj.magentoOrdersStatusMappingModeChange);
        },

        // ---------------------------------------

        changeMarketplace: function() {
            $$('.marketplace-required-field').each(function(obj) {
                obj.hide();
            });

            var marketplaceId = this.value;
            if (marketplaceId === '') {
                return;
            }

            $$('.marketplace-required-field-id' + marketplaceId, '.marketplace-required-field-id-not-null').each(function(obj) {
                obj.show();
            });
        },

        // ---------------------------------------

        other_listings_synchronization_change: function() {
            if (this.value == 1) {
                $('other_listings_mapping_mode_tr').show();
                $('other_listings_store_view_tr').show();
            } else {
                $('other_listings_mapping_mode').value = 0;
                $('other_listings_mapping_mode').simulate('change');
                $('other_listings_mapping_mode_tr').hide();
                $('other_listings_store_view_tr').hide();
            }
        },

        other_listings_mapping_mode_change: function() {
            if (this.value == 1) {
                $('magento_block_walmart_accounts_other_listings_product_mapping').show();
            } else {
                $('magento_block_walmart_accounts_other_listings_product_mapping').hide();

                $('mapping_sku_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE');
                $('mapping_upc_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_UPC_MODE_NONE');
                $('mapping_gtin_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_NONE');
                $('mapping_wpid_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_WPID_MODE_NONE');
                $('mapping_title_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE');
            }

            $('mapping_sku_mode').simulate('change');
            $('mapping_upc_mode').simulate('change');
            $('mapping_gtin_mode').simulate('change');
            $('mapping_wpid_mode').simulate('change');
            $('mapping_title_mode').simulate('change');
        },

        // ---------------------------------------

        mapping_sku_mode_change: function() {
            var self = WalmartAccountObj;

            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE')) {
                $('mapping_sku_priority_td').hide();
            } else {
                $('mapping_sku_priority_td').show();
            }

            $('mapping_sku_attribute').value = '';
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_sku_attribute'));
            }
        },

        mapping_upc_mode_change: function() {
            var self = WalmartAccountObj;

            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_UPC_MODE_NONE')) {
                $('mapping_upc_priority_td').hide();
            } else {
                $('mapping_upc_priority_td').show();
            }

            $('mapping_upc_attribute').value = '';
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_upc_attribute'));
            }
        },

        mapping_gtin_mode_change: function() {
            var self = WalmartAccountObj;

            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_NONE')) {
                $('mapping_gtin_priority_td').hide();
            } else {
                $('mapping_gtin_priority_td').show();
            }

            $('mapping_gtin_attribute').value = '';
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_gtin_attribute'));
            }
        },

        mapping_wpid_mode_change: function() {
            var self = WalmartAccountObj;

            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_WPID_MODE_NONE')) {
                $('mapping_wpid_priority_td').hide();
            } else {
                $('mapping_wpid_priority_td').show();
            }

            $('mapping_wpid_attribute').value = '';
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_wpid_attribute'));
            }
        },

        mapping_title_mode_change: function() {
            var self = WalmartAccountObj;

            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE')) {
                $('mapping_title_priority_td').hide();
            } else {
                $('mapping_title_priority_td').show();
            }

            $('mapping_title_attribute').value = '';
            if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_title_attribute'));
            }
        },

        // ---------------------------------------

        magentoOrdersListingsModeChange: function() {
            var self = WalmartAccountObj;

            if ($('magento_orders_listings_mode').value == 1) {
                $('magento_orders_listings_store_mode_container').show();
            } else {
                $('magento_orders_listings_store_mode_container').hide();
                $('magento_orders_listings_store_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT');
            }

            self.magentoOrdersListingsStoreModeChange();
            self.changeVisibilityForOrdersModesRelatedBlocks();
        },

        magentoOrdersListingsStoreModeChange: function() {
            if ($('magento_orders_listings_store_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM')) {
                $('magento_orders_listings_store_id_container').show();
            } else {
                $('magento_orders_listings_store_id_container').hide();
                $('magento_orders_listings_store_id').value = '';
            }
        },

        magentoOrdersListingsOtherModeChange: function() {
            var self = WalmartAccountObj;

            if ($('magento_orders_listings_other_mode').value == 1) {
                $('magento_orders_listings_other_product_mode_container').show();
                $('magento_orders_listings_other_store_id_container').show();
            } else {
                $('magento_orders_listings_other_product_mode_container').hide();
                $('magento_orders_listings_other_store_id_container').hide();
                $('magento_orders_listings_other_product_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE');
                $('magento_orders_listings_other_store_id').value = '';
            }

            self.magentoOrdersListingsOtherProductModeChange();
            self.changeVisibilityForOrdersModesRelatedBlocks();
        },

        magentoOrdersListingsOtherProductModeChange: function() {
            if ($('magento_orders_listings_other_product_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE')) {
                $('magento_orders_listings_other_product_mode_note').hide();
                $('magento_orders_listings_other_product_tax_class_id_container').hide();
                $('magento_orders_listings_other_product_mode_warning').hide();
            } else {
                $('magento_orders_listings_other_product_mode_note').show();
                $('magento_orders_listings_other_product_tax_class_id_container').show();
                $('magento_orders_listings_other_product_mode_warning').show();
            }
        },

        magentoOrdersNumberSourceChange: function() {
            var self = WalmartAccountObj;
            self.renderOrderNumberExample();
        },

        magentoOrdersNumberPrefixPrefixChange: function() {
            var self = WalmartAccountObj;
            self.renderOrderNumberExample();
        },

        renderOrderNumberExample: function() {
            var orderNumber = $('sample_magento_order_id').value;
            if ($('magento_orders_number_source').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL')) {
                orderNumber = $('sample_walmart_order_id').value;
            }

            orderNumber = $('magento_orders_number_prefix_prefix').value + orderNumber;

            $('order_number_example_container').update(orderNumber);
        },

        magentoOrdersCustomerModeChange: function() {
            var customerMode = $('magento_orders_customer_mode').value;

            if (customerMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED')) {
                $('magento_orders_customer_id_container').show();
                $('magento_orders_customer_id').addClassName('M2ePro-account-product-id');
            } else {  // M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::ORDERS_CUSTOMER_MODE_GUEST') || M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::ORDERS_CUSTOMER_MODE_NEW')
                $('magento_orders_customer_id_container').hide();
                $('magento_orders_customer_id').value = '';
                $('magento_orders_customer_id').removeClassName('M2ePro-account-product-id');
            }

            var action = (customerMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_CUSTOMER_MODE_NEW')) ? 'show' : 'hide';
            $('magento_orders_customer_new_website_id_container')[action]();
            $('magento_orders_customer_new_group_id_container')[action]();
            $('magento_orders_customer_new_notifications_container')[action]();

            if (action == 'hide') {
                $('magento_orders_customer_new_website_id').value = '';
                $('magento_orders_customer_new_group_id').value = '';
                $('magento_orders_customer_new_notifications').value = '';
            }
        },

        magentoOrdersStatusMappingModeChange: function() {
            // Reset dropdown selected values to default
            $('magento_orders_status_mapping_processing').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING');
            $('magento_orders_status_mapping_shipped').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED');

            var disabled = $('magento_orders_status_mapping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT');
            $('magento_orders_status_mapping_processing').disabled = disabled;
            $('magento_orders_status_mapping_shipped').disabled = disabled;
        },

        changeVisibilityForOrdersModesRelatedBlocks: function() {
            var self = WalmartAccountObj;

            if ($('magento_orders_listings_mode').value == 0 && $('magento_orders_listings_other_mode').value == 0) {

                $('magento_block_walmart_accounts_magento_orders_number-wrapper').hide();
                $('magento_orders_number_source').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO');

                $('magento_block_walmart_accounts_magento_orders_customer-wrapper').hide();
                $('magento_orders_customer_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST');
                self.magentoOrdersCustomerModeChange();

                $('magento_block_walmart_accounts_magento_orders_status_mapping-wrapper').hide();
                $('magento_orders_status_mapping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT');
                self.magentoOrdersStatusMappingModeChange();

                $('magento_block_walmart_accounts_magento_orders_refund_and_cancellation').hide();
                $('magento_orders_refund').value = 1;

                $('magento_block_walmart_accounts_magento_orders_tax-wrapper').hide();
                $('magento_orders_tax_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Walmart\\Account::MAGENTO_ORDERS_TAX_MODE_MIXED');
            } else {
                $('magento_block_walmart_accounts_magento_orders_number-wrapper').show();
                $('magento_block_walmart_accounts_magento_orders_customer-wrapper').show();
                $('magento_block_walmart_accounts_magento_orders_status_mapping-wrapper').show();
                $('magento_block_walmart_accounts_magento_orders_refund_and_cancellation').show();
                $('magento_block_walmart_accounts_magento_orders_tax-wrapper').show();
            }
        },

        // ---------------------------------------

        saveAndClose: function() {
            var self = this,
                url = typeof M2ePro.url.urls.formSubmit == 'undefined' ?
                    M2ePro.url.formSubmit + 'back/' + base64_encode('list') + '/' :
                    M2ePro.url.get('formSubmit', {'back': base64_encode('list')});

            if (!self.isValidForm()) {
                return;
            }

            new Ajax.Request(url, {
                method: 'post',
                parameters: Form.serialize($('edit_form')),
                onSuccess: function(transport) {
                    transport = transport.responseText.evalJSON();

                    if (transport.success) {
                        window.close();
                    } else {
                        self.alert(transport.message);
                        return;
                    }
                }
            });
        },

        otherCarrierInit: function(max) {
            var visibleElementsCounter = 0;
            $$('.other_carrier').each(function(obj) {
                if (obj.firstChild.value == '' && visibleElementsCounter !== 0) {
                    $(obj.id + '_separator').hide();
                    $(obj.up().up().up()).hide();
                } else {
                    visibleElementsCounter++;
                }
            });

            var showOtherCarrierAction = $('show_other_carrier_action');
            if (visibleElementsCounter < max) {
                showOtherCarrierAction.removeClassName('action-disabled');
            } else {
                showOtherCarrierAction.addClassName('action-disabled');
            }

            if ($('other_carrier_0').value == '') {
                showOtherCarrierAction.addClassName('action-disabled');
            }

            if (visibleElementsCounter <= 1) {
                $('hide_other_carrier_action').addClassName('action-disabled');
            }
        },

        otherCarrierKeyup: function(element) {
            var showOtherCarrierAction = $('show_other_carrier_action');
            if (!element.value) {
                showOtherCarrierAction.addClassName('action-disabled');
                element.up().nextSibling.removeClassName('M2ePro-required-when-visible');
                return;
            }

            element.up().nextSibling.addClassName('M2ePro-required-when-visible');

            var hiddenElements = $$('.other_carrier').findAll(function(obj) {
                return !$(obj.up().up().up()).visible();
            });

            if (hiddenElements.size() > 0) {
                showOtherCarrierAction.removeClassName('action-disabled');
            }
        },

        otherCarrierUrlKeyup: function(element) {
            if (!element.value) {
                element.previousSibling.firstChild.removeClassName('M2ePro-required-when-visible');
            } else {
                element.previousSibling.firstChild.addClassName('M2ePro-required-when-visible');
            }
        },

        showElement: function() {
            var otherCarriers = $$('.other_carrier');
            if ($(otherCarriers[0].up().up().up()).visible() && otherCarriers[0].firstChild.value == '') {
                return;
            }

            var hiddenElements = otherCarriers.findAll(function(obj) {
                return !$(obj.up().up().up()).visible();
            });

            if (hiddenElements.size() == 0) {
                return;
            }

            var obj = hiddenElements.shift();
            $(obj.up().up().up()).show();
            $(obj.id + '_separator').show();

            $('hide_other_carrier_action').removeClassName('action-disabled');
            $('show_other_carrier_action').addClassName('action-disabled');
        },

        hideElement: function() {
            var visibleElements = [];
            $$('.other_carrier').each(function(obj) {
                if ($(obj.up().up().up()).visible()) {
                    visibleElements.push(obj);
                }
            });

            if (visibleElements.size() > 1) {
                var obj = visibleElements.pop();
                obj.firstChild.value = '';
                obj.nextSibling.value = '';
                $(obj.up().up().up()).hide();
                $(obj.id + '_separator').hide();
            }

            if (visibleElements.size() == 1) {
                $('hide_other_carrier_action').addClassName('action-disabled');
            }

            $('show_other_carrier_action').removeClassName('action-disabled');
        }

        // ---------------------------------------
    });
});
