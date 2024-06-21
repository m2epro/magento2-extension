define([
    'M2ePro/Common'
], function() {

    window.AmazonAccount = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {
            var self = this;

            this.setValidationCheckRepetitionValue('M2ePro-account-title',
                M2ePro.translator.translate('The specified Title is already used for other Account. Account Title must be unique.'),
                'Account', 'title', 'id',
                M2ePro.formData.id,
                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'));

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

                    $$('.attribute-mode-select').forEach(function(obj) {
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

            jQuery.validator.addMethod('M2ePro-is-ready-for-document-generation', function(value) {
                var checkResult = false;

                if ($('auto_invoicing').value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::AUTO_INVOICING_VAT_CALCULATION_SERVICE')) {
                    return true;
                }

                if ($('invoice_generation').value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::INVOICE_GENERATION_BY_EXTENSION')) {
                    return true;
                }

                new Ajax.Request(M2ePro.url.get('amazon_account/isReadyForDocumentGeneration'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        account_id: M2ePro.formData.id,
                        new_store_mode: $('magento_orders_listings_store_mode').value,
                        new_store_id: $('magento_orders_listings_store_id').value
                    },
                    onSuccess: function(transport) {
                        checkResult = transport.responseText.evalJSON()['result'];
                    }
                });

                return checkResult;
            }, M2ePro.translator.translate('is_ready_for_document_generation'));
        },

        initObservers: function() {
            //tab listingOther
            $('other_listings_synchronization')
                .observe('change', AmazonAccountObj.other_listings_synchronization_change)
                .simulate('change');
            $('other_listings_mapping_mode')
                .observe('change', AmazonAccountObj.other_listings_mapping_mode_change)
                .simulate('change');

            $('mapping_general_id_mode')
                .observe('change', AmazonAccountObj.mapping_general_id_mode_change)
                .simulate('change');
            $('mapping_sku_mode')
                .observe('change', AmazonAccountObj.mapping_sku_mode_change)
                .simulate('change');
            $('mapping_title_mode')
                .observe('change', AmazonAccountObj.mapping_title_mode_change)
                .simulate('change');

            if ($('auto_invoicing')) {
                $('auto_invoicing')
                    .observe('change', AmazonAccountObj.autoInvoicingModeChange)
                    .simulate('change');
            }

            //tab order
            $('magento_orders_listings_mode').observe('change', AmazonAccountObj.magentoOrdersListingsModeChange).simulate('change');
            $('magento_orders_listings_store_mode').observe('change', AmazonAccountObj.magentoOrdersListingsStoreModeChange).simulate('change');

            $('magento_orders_listings_other_mode').observe('change', AmazonAccountObj.magentoOrdersListingsOtherModeChange).simulate('change');
            $('magento_orders_listings_other_product_mode').observe('change', AmazonAccountObj.magentoOrdersListingsOtherProductModeChange);

            $('magento_orders_number_source').observe('change', AmazonAccountObj.magentoOrdersNumberSourceChange).simulate('change');

            $('magento_orders_number_prefix_prefix').observe('keyup', AmazonAccountObj.magentoOrdersNumberPrefixPrefixChange);
            $('magento_orders_number_prefix_afn').observe('keyup', AmazonAccountObj.magentoOrdersNumberPrefixPrefixChange);
            $('magento_orders_number_prefix_prime').observe('keyup', AmazonAccountObj.magentoOrdersNumberPrefixPrefixChange);
            $('magento_orders_number_prefix_b2b').observe('keyup', AmazonAccountObj.magentoOrdersNumberPrefixPrefixChange);

            $('magento_orders_fba_mode').observe('change', AmazonAccountObj.magentoOrdersFbaModeChange).simulate('change');
            $('magento_orders_fba_store_mode').observe('change', AmazonAccountObj.magentoOrdersFbaStoreModeChange);

            $('magento_orders_customer_mode').observe('change', AmazonAccountObj.magentoOrdersCustomerModeChange).simulate('change');
            $('magento_orders_tax_mode').observe('change', AmazonAccountObj.magentoOrdersTaxModeChange).simulate('change');
            $('magento_orders_tax_amazon_collects').observe('change', AmazonAccountObj.magentoOrdersTaxAmazonCollectsChange).simulate('change');
            $('magento_orders_tax_amazon_collects_for_eea_shipment')
                .observe('change', AmazonAccountObj.magentoOrdersTaxSkipTaxInEEAOrders)
                .simulate('change');

            $('magento_orders_tax_amazon_round_of_rate_value')
                    .observe('change', AmazonAccountObj.magentoOrderTaxRoundOfRateValueChange)
            $('magento_orders_status_mapping_mode').observe('change', AmazonAccountObj.magentoOrdersStatusMappingModeChange);

            // tab fbaInventory
            const $fbaInventoryMode = $('fba_inventory_mode');
            if ($fbaInventoryMode) {
                $fbaInventoryMode
                        .observe('change', AmazonAccountObj.fbaInventoryModeChange)
                        .simulate('change')
            }
        },

        // ---------------------------------------

        deleteClick: function(id) {
            this.confirm({
                content: M2ePro.translator.translate('confirmation_account_delete'),
                actions: {
                    confirm: function() {
                        if (id === undefined) {
                            setLocation(M2ePro.url.get('deleteAction'));
                        } else {
                            setLocation(M2ePro.url.get('*/amazon_account/delete/', {
                                id: id,
                            }));
                        }
                    },
                    cancel: function() {
                        return false;
                    }
                }
            });
        },

        checkClick: function() {
            this.submitForm(M2ePro.url.get('checkAction'));

            return false;
        },

        // ---------------------------------------

        getToken: function(marketplaceId) {
            var title = $('title');

            title.removeClassName('required-entry M2ePro-account-title');
            $('other_listings_mapping_mode').removeClassName('M2ePro-require-select-attribute');

            this.submitForm(M2ePro.url.get(
                'amazon_account/beforeGetToken',
                {
                    'id': M2ePro.formData.id,
                    'marketplace_id': marketplaceId
                }
            ));
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
                $('magento_block_amazon_accounts_other_listings_product_mapping').show();
            } else {
                $('magento_block_amazon_accounts_other_listings_product_mapping').hide();

                $('mapping_general_id_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE');
                $('mapping_sku_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE');
                $('mapping_title_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE');
            }

            $('mapping_general_id_mode').simulate('change');
            $('mapping_sku_mode').simulate('change');
            $('mapping_title_mode').simulate('change');
        },

        // ---------------------------------------

        mapping_general_id_mode_change: function() {
            var self = AmazonAccountObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE')) {
                $('mapping_general_id_priority_td').hide();
            } else {
                $('mapping_general_id_priority_td').show();
            }

            $('mapping_general_id_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_general_id_attribute'));
            }
        },

        mapping_sku_mode_change: function() {
            var self = AmazonAccountObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE')) {
                $('mapping_sku_priority_td').hide();
            } else {
                $('mapping_sku_priority_td').show();
            }

            $('mapping_sku_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_sku_attribute'));
            }
        },

        mapping_title_mode_change: function() {
            var self = AmazonAccountObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE')) {
                $('mapping_title_priority_td').hide();
            } else {
                $('mapping_title_priority_td').show();
            }

            $('mapping_title_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('mapping_title_attribute'));
            }
        },

        // ---------------------------------------

        magentoOrdersListingsModeChange: function() {
            var self = AmazonAccountObj;

            if ($('magento_orders_listings_mode').value == 1) {
                $('magento_orders_listings_store_mode_container').show();
            } else {
                $('magento_orders_listings_store_mode_container').hide();
                $('magento_orders_listings_store_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT');
            }

            self.magentoOrdersListingsStoreModeChange();
            self.changeVisibilityForOrdersModesRelatedBlocks();
        },

        magentoOrdersListingsStoreModeChange: function() {
            if ($('magento_orders_listings_store_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM')) {
                $('magento_orders_listings_store_id_container').show();
            } else {
                $('magento_orders_listings_store_id_container').hide();
                $('magento_orders_listings_store_id').value = '';
            }
        },

        magentoOrdersListingsOtherModeChange: function() {
            var self = AmazonAccountObj;

            if ($('magento_orders_listings_other_mode').value == 1) {
                $('magento_orders_listings_other_product_mode_container').show();
                $('magento_orders_listings_other_store_id_container').show();
            } else {
                $('magento_orders_listings_other_product_mode_container').hide();
                $('magento_orders_listings_other_store_id_container').hide();
                $('magento_orders_listings_other_product_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE');
                $('magento_orders_listings_other_store_id').value = '';
            }

            self.magentoOrdersListingsOtherProductModeChange();
            self.changeVisibilityForOrdersModesRelatedBlocks();
        },

        magentoOrdersListingsOtherProductModeChange: function() {
            if ($('magento_orders_listings_other_product_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Amazon\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE')) {
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
            var self = AmazonAccountObj;
            self.renderOrderNumberExample();
        },

        magentoOrdersNumberPrefixPrefixChange: function() {
            var self = AmazonAccountObj;
            self.renderOrderNumberExample();
        },

        renderOrderNumberExample: function() {
            var orderNumber = $('sample_magento_order_id').value;
            if ($('magento_orders_number_source').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL')) {
                orderNumber = $('sample_amazon_order_id').value;
            }

            var regular = orderNumber,
                afn = orderNumber,
                prime = orderNumber,
                b2b = orderNumber;

            var regularPrefix = $('magento_orders_number_prefix_prefix').value;
            regular = regularPrefix + regular;
            afn = $('magento_orders_number_prefix_afn').value + afn;
            prime =  $('magento_orders_number_prefix_prime').value + prime;
            b2b =  $('magento_orders_number_prefix_b2b').value + b2b;

            $('order_number_example_container_regular').update(regular);
            $('order_number_example_container_afn').update(afn);
            $('order_number_example_container_prime').update(prime);
            $('order_number_example_container_b2b').update(b2b);
        },

        magentoOrdersFbaModeChange: function() {
            var self = AmazonAccountObj;

            if ($('magento_orders_fba_mode').value == 0) {
                $('magento_orders_fba_store_mode').value = 0;
                $('magento_orders_fba_store_mode_container').hide();
                $('magento_orders_fba_stock_mode').value = 0;
                $('magento_orders_fba_stock_mode_container').hide();
            } else {
                $('magento_orders_fba_store_mode_container').show();
                $('magento_orders_fba_stock_mode_container').show();
            }

            self.magentoOrdersFbaStoreModeChange();
        },

        magentoOrdersFbaStoreModeChange: function() {
            if ($('magento_orders_fba_store_mode').value == 0) {
                $('magento_orders_fba_store_id').value = '';
                $('magento_orders_fba_store_id_container').hide();
            } else {
                $('magento_orders_fba_store_id_container').show();
            }
        },

        magentoOrdersCustomerModeChange: function() {
            var customerMode = $('magento_orders_customer_mode').value;

            if (customerMode == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED')) {
                $('magento_orders_customer_id_container').show();
                $('magento_orders_customer_id').addClassName('M2ePro-account-product-id');
            } else {
                $('magento_orders_customer_id_container').hide();
                $('magento_orders_customer_id').value = '';
                $('magento_orders_customer_id').removeClassName('M2ePro-account-product-id');
            }

            var action = (customerMode == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_CUSTOMER_MODE_NEW')) ? 'show' : 'hide';
            $('magento_orders_customer_new_website_id_container')[action]();
            $('magento_orders_customer_new_group_id_container')[action]();
            $('magento_orders_customer_new_notifications_container')[action]();

            if (action == 'hide') {
                $('magento_orders_customer_new_website_id').value = '';
                $('magento_orders_customer_new_group_id').value = '';
                $('magento_orders_customer_new_notifications').value = '';
            }
        },

        openExcludedStatesPopup: function() {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_account/getExcludedStatesPopupHtml'), {
                method: 'post',
                parameters: {
                    selected_states: $('magento_orders_tax_excluded_states').value
                },
                onSuccess: function(transport) {

                    var excludedStates = $('excluded_states_popup');

                    if (!excludedStates) {
                        excludedStates = new Element('div', {
                            id: 'excluded_states_popup'
                        });
                    }

                    excludedStates.innerHTML = transport.responseText;

                    self.excludedStatesPopUp = jQuery(excludedStates).modal({
                        title: M2ePro.translator.translate('Select states where tax will be excluded'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'primary',
                            click: function() {
                                self.changeExcludedStates();
                            }
                        }]
                    });

                    self.excludedStatesPopUp.modal('openModal');
                }
            });
        },

        changeExcludedStates: function() {
            var self = this;
            var excludedStates = [];

            $$('.excluded_state_checkbox').each(function(element) {
                if (element.checked) {
                    excludedStates.push(element.value);
                }
            });

            $('magento_orders_tax_excluded_states').value = excludedStates.toString();

            self.excludedStatesPopUp.modal('closeModal');
        },

        openExcludedCountriesPopup: function() {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_account/getExcludedCountriesPopupHtml'), {
                method: 'post',
                parameters: {
                    selected_countries: $('magento_orders_tax_excluded_countries').value
                },
                onSuccess: function(transport) {
                    var excludedCountries = $('excluded_countries_popup');

                    if (!excludedCountries) {
                        excludedCountries = new Element('div', {
                            id: 'excluded_countries_popup'
                        });
                    }

                    excludedCountries.innerHTML = transport.responseText;

                    self.excludedCountriesPopUp = jQuery(excludedCountries).modal({
                        title: M2ePro.translator.translate('Select countries where VAT will be excluded'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'primary',
                            click: function() {
                                self.changeExcludedCountries();
                            }
                        }]
                    });

                    self.excludedCountriesPopUp.modal('openModal');
                }
            });
        },

        changeExcludedCountries: function() {
            var self = this;
            var excludedCountries = [];

            $$('.excluded_country_checkbox').each(function(element) {
                if (element.checked) {
                    excludedCountries.push(element.value);
                }
            });

            $('magento_orders_tax_excluded_countries').value = excludedCountries.toString();

            self.excludedCountriesPopUp.modal('closeModal');
        },

        magentoOrdersTaxModeChange: function() {
            if ($('magento_orders_tax_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL') ||
                $('magento_orders_tax_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_TAX_MODE_MIXED')) {
                $('magento_orders_tax_amazon_collects_container').show();
                $('magento_orders_tax_amazon_collects_for_uk_shipment_container').show();
                $('magento_orders_tax_round_of_rate_value_container').show();
            } else {
                $('magento_orders_tax_amazon_collects_container').hide();
                $('magento_orders_tax_amazon_collects_for_uk_shipment_container').hide();
                $('magento_orders_tax_round_of_rate_value_container').hide();
            }

            if ($('marketplace_id').value != M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::MARKETPLACE_US')) {
                $('magento_orders_tax_amazon_collects_container').hide();
            }
        },

        magentoOrdersTaxAmazonCollectsChange: function() {
            if ($('magento_orders_tax_amazon_collects').value == 1) {
                $('show_excluded_states_button').show();
            } else {
                $('show_excluded_states_button').hide();
            }
        },

        magentoOrdersTaxSkipTaxInEEAOrders: function() {
            if ($('magento_orders_tax_amazon_collects_for_eea_shipment').value == 1) {
                $('show_excluded_countries_button').show();
            } else {
                $('show_excluded_countries_button').hide();
            }
        },

        magentoOrderTaxRoundOfRateValueChange : function () {
            if (this.value == 0) {
                return;
            }

            var popup = jQuery('#magento_orders_tax_amazon_round_of_rate_value_confirmation_popup_template').clone();
            var field = this;

            popup.confirm({
                title: M2ePro.translator.translate('Are you sure?'),
                actions: {
                    confirm: function () {
                        field.selectedIndex = 1;
                    },
                    cancel: function () {
                        field.selectedIndex = 0;
                    }
                },
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        magentoOrdersStatusMappingModeChange: function() {
            // Reset dropdown selected values to default
            $('magento_orders_status_mapping_processing').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING');
            $('magento_orders_status_mapping_shipped').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED');

            var disabled = $('magento_orders_status_mapping_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT');
            $('magento_orders_status_mapping_processing').disabled = disabled;
            $('magento_orders_status_mapping_shipped').disabled = disabled;
        },

        changeVisibilityForOrdersModesRelatedBlocks: function() {
            var self = AmazonAccountObj;

            if ($('magento_orders_listings_mode').value == 0 && $('magento_orders_listings_other_mode').value == 0) {

                $('magento_block_amazon_accounts_magento_orders_number-wrapper').hide();
                $('magento_orders_number_source').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO');

                $('magento_block_amazon_accounts_magento_orders_fba-wrapper').hide();
                $('magento_orders_fba_mode').value = 1;
                $('magento_orders_fba_store_mode').value = 0;
                $('magento_orders_fba_stock_mode').value = 1;

                $('magento_block_amazon_accounts_magento_orders_refund_and_cancellation-wrapper').hide();

                $('magento_block_amazon_accounts_magento_orders_customer-wrapper').hide();
                $('magento_orders_customer_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST');
                self.magentoOrdersCustomerModeChange();

                $('magento_block_amazon_accounts_magento_orders_status_mapping-wrapper').hide();
                $('magento_orders_status_mapping_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT');
                self.magentoOrdersStatusMappingModeChange();

                $('magento_block_amazon_accounts_magento_orders_rules-wrapper').hide();
                $('magento_orders_qty_reservation_days').value = 1;

                $('magento_block_amazon_accounts_magento_orders_tax-wrapper').hide();
                $('magento_orders_tax_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::MAGENTO_ORDERS_TAX_MODE_MIXED');

                $('magento_orders_customer_billing_address_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::USE_SHIPPING_ADDRESS_AS_BILLING_IF_SAME_CUSTOMER_AND_RECIPIENT');
            } else {
                $('magento_block_amazon_accounts_magento_orders_number-wrapper').show();
                $('magento_block_amazon_accounts_magento_orders_fba-wrapper').show();
                $('magento_block_amazon_accounts_magento_orders_refund_and_cancellation-wrapper').show();
                $('magento_block_amazon_accounts_magento_orders_customer-wrapper').show();
                $('magento_block_amazon_accounts_magento_orders_status_mapping-wrapper').show();
                $('magento_block_amazon_accounts_magento_orders_tax-wrapper').show();
                $('magento_block_amazon_accounts_magento_orders_rules-wrapper').show();
            }
        },

        autoInvoicingModeChange: function() {
            var invoiceGenerationContainer = $('invoice_generation_container');
            var createMagentoInvoice = $('create_magento_invoice');

            invoiceGenerationContainer.hide();

            if ($('auto_invoicing').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::AUTO_INVOICING_VAT_CALCULATION_SERVICE')) {
                invoiceGenerationContainer.show();
                createMagentoInvoice.value = 0;
            }
        },

        fbaInventoryModeChange: function() {
            if ($('fba_inventory_mode').value == 1) {
                $('fba_inventory_source_tr').show();
            } else {
                $('fba_inventory_source_tr').hide();
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
        }
    });
});
