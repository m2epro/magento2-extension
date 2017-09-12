define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
    'extjs/ext-tree-checkbox',
    'mage/adminhtml/form'
], function(modal){

    window.EbayAccount = Class.create(Common, {

    // ---------------------------------------

    initialize: function()
    {
        this.setValidationCheckRepetitionValue('M2ePro-account-title',
            M2ePro.translator.translate('The specified Title is already used for other Account. Account Title must be unique.'),
            'Account', 'title', 'id',
            M2ePro.formData.id,
            M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay::NICK')
        );

        jQuery.validator.addMethod('M2ePro-account-token-session', function(value) {
            return value != '';
        }, M2ePro.translator.translate('You must get token.'));

        jQuery.validator.addMethod('M2ePro-account-customer-id', function(value) {

            var checkResult = false;

            if ($('magento_orders_customer_id_container').getStyle('display') == 'none') {
                return true;
            }

            new Ajax.Request(M2ePro.url.get('general/checkCustomerId'), {
                method: 'post',
                asynchronous: false,
                parameters: {
                    customer_id : value,
                    id          : M2ePro.formData.id
                },
                onSuccess: function(transport) {
                    checkResult = transport.responseText.evalJSON()['ok'];
                }
            });

            return checkResult;
        }, M2ePro.translator.translate('No Customer entry is found for specified ID.'));

        jQuery.validator.addMethod('M2ePro-account-order-number-prefix', function(value) {

            if ($('magento_orders_number_prefix_mode').value == 0) {
                return true;
            }

            return value.length <= 5;
        }, M2ePro.translator.translate('Prefix length should not be greater than 5 characters.'));

        jQuery.validator.addMethod('M2ePro-account-feedback-templates', function(value) {

            if (value == 0) {
                return true;
            }

            var checkResult = false;

            new Ajax.Request(M2ePro.url.get('ebay_account_feedback/templateCheck'), {
                method: 'post',
                asynchronous: false,
                parameters: {
                    id: M2ePro.formData.id
                },
                onSuccess: function(transport) {
                    checkResult = transport.responseText.evalJSON()['ok'];
                }
            });

            return checkResult;
        }, M2ePro.translator.translate('You should create at least one Response Template.'));

        jQuery.validator.addMethod('M2ePro-require-select-attribute', function(value, el) {

            if ($('other_listings_mapping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_MODE_NO')) {
                return true;
            }

            var isAttributeSelected = false;

            $$('.attribute-mode-select').each(function(obj) {
                if (obj.value != 0) {
                    isAttributeSelected = true;
                }
            });

            return isAttributeSelected;
        }, M2ePro.translator.translate('If Yes is chosen, you must select at least one Attribute for Product Mapping.'));
    },

    initObservers: function()
    {
        if (!M2ePro.formData.id) {
            return;
        }

        //tab listingOther
        $('other_listings_synchronization')
            .observe('change', this.other_listings_synchronization_change)
            .simulate('change');
        $('other_listings_mapping_mode')
            .observe('change', this.other_listings_mapping_mode_change)
            .simulate('change');
        $('mapping_sku_mode')
            .observe('change', this.mapping_sku_mode_change)
            .simulate('change');
        $('mapping_title_mode')
            .observe('change', this.mapping_title_mode_change)
            .simulate('change');

        //$('ebayAccountEditTabs_listingOther').removeClassName('changed');

        //tab orders
        $('magento_orders_listings_mode').observe('change', this.magentoOrdersListingsModeChange).simulate('change');
        $('magento_orders_listings_store_mode').observe('change', this.magentoOrdersListingsStoreModeChange).simulate('change');

        $('magento_orders_listings_other_mode').observe('change', this.magentoOrdersListingsOtherModeChange).simulate('change');
        $('magento_orders_listings_other_product_mode').observe('change', this.magentoOrdersListingsOtherProductModeChange);

        $('magento_orders_number_source').observe('change', this.magentoOrdersNumberSourceChange);
        $('magento_orders_number_prefix_mode').observe('change', this.magentoOrdersNumberPrefixModeChange).simulate('change');
        $('magento_orders_number_prefix_prefix').observe('keyup', this.magentoOrdersNumberPrefixPrefixChange).simulate('change');
        EbayAccountObj.renderOrderNumberExample();

        $('magento_orders_customer_mode').observe('change', this.magentoOrdersCustomerModeChange).simulate('change');

        $('magento_orders_status_mapping_mode').observe('change', this.magentoOrdersStatusMappingModeChange);

        $('magento_orders_creation_mode').observe('change', this.magentoOrdersCreationModeChange).simulate('change');

        $('order_number_example-note').previous().remove();
    },

    // ---------------------------------------

    saveAndClose: function()
    {
        var self = this,
            url = typeof M2ePro.url.urls.formSubmit == 'undefined' ?
                M2ePro.url.formSubmit + 'back/'+Base64.encode('list')+'/' :
                M2ePro.url.get('formSubmit', {'back': Base64.encode('list')});

        if (!this.isValidForm()) {
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
                }
            }
        });
    },

    // ---------------------------------------

    deleteClick: function()
    {
        this.confirm({
            content: M2ePro.translator.translate('Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. This will cause inappropriate work of all Accounts\' copies.'),
            actions: {
                confirm: function () {
                    setLocation(M2ePro.url.get('deleteAction'));
                },
                cancel: function () {
                    return false;
                }
            }
        });
    },

    // ---------------------------------------

    get_token: function()
    {
        if ($('token_session').value == '') {
            $('token_session').value = '0';
        }
        if ($('token_expired_date').value == '') {
            $('token_expired_date').value = '0';
        }

        if (!jQuery('#edit_form').valid()) {
            return;
        }

        this.submitForm(M2ePro.url.get('ebay_account/beforeGetToken', {'id': M2ePro.formData.id}));
    },

    // ---------------------------------------

    feedbacksReceiveChange: function()
    {
        var self = EbayAccountObj ;

        if ($('feedbacks_receive').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::FEEDBACKS_RECEIVE_YES')) {
            $('feedbacks_auto_response_container').show();
        } else {
            $('feedbacks_auto_response_container').hide();

        }
        $('feedbacks_auto_response').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::FEEDBACKS_AUTO_RESPONSE_NONE');
        self.feedbacksAutoResponseChange();
    },

    feedbacksAutoResponseChange: function()
    {
        if ($('feedbacks_auto_response').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::FEEDBACKS_AUTO_RESPONSE_NONE')) {
            $('feedbacks_auto_response_only_positive_container').hide();
            $('feedback_templates_grid_container').hide();
        } else {
            $('feedbacks_auto_response_only_positive_container').show();
            $('feedback_templates_grid_container').show();
        }
    },

    // ---------------------------------------

    openFeedbackTemplatePopup: function(templateId)
    {
        var self = this;

        new Ajax.Request(M2ePro.url.get('ebay_account_feedback_template/getForm'), {
            method: 'GET',
            parameters: {
                id: templateId
            },
            onSuccess: function(transport) {

                var response = transport.responseText.evalJSON();

                var container = $('edit_feedback_template_form_container');

                if (container) {
                    container.remove();
                }

                $('html-body').insert({
                    bottom: '<div id="edit_feedback_template_form_container">' + response.html + '</div>'
                });

                self.initFormValidation('#edit_feedback_template_form');

                self.feedbackTemplatePopup = jQuery('#edit_feedback_template_form_container');

                modal({
                    title: response.title,
                    type: 'popup',
                    modalClass: 'width-50',
                    buttons: [{
                        text: M2ePro.translator.translate('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function () {
                            self.feedbackTemplatePopup.modal('closeModal');
                        }
                    },{
                        text: M2ePro.translator.translate('Save'),
                        class: 'action-primary',
                        click: function () {
                            if (!jQuery('#edit_feedback_template_form').valid()) {
                                return false;
                            }

                            new Ajax.Request(M2ePro.url.get('ebay_account_feedback_template/save'), {
                                parameters: $('edit_feedback_template_form').serialize(true),
                                onSuccess: function() {
                                    self.feedbackTemplatePopup.modal('closeModal');
                                    $('add_feedback_template_button_container').hide();
                                    $('feedback_templates_grid').show();
                                    window['ebayAccountEditTabsFeedbackGridJsObject'].reload();
                                }
                            });
                        }
                    }]
                }, self.feedbackTemplatePopup);

                self.feedbackTemplatePopup.modal('openModal');
            }
        });
    },

    // ---------------------------------------

    feedbacksDeleteAction: function(id)
    {
        this.confirm({
            actions: {
                confirm: function () {
                    new Ajax.Request(M2ePro.url.get('ebay_account_feedback_template/delete'), {
                        method: 'post',
                        parameters: {
                            id: id
                        },
                        onSuccess: function() {
                            if ($('ebayAccountEditTabsFeedbackGrid').select('tbody tr').length == 1) {
                                $('add_feedback_template_button_container').show();
                                $('feedback_templates_grid').hide();
                            }

                            window['ebayAccountEditTabsFeedbackGridJsObject'].reload();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            }
        });
    },

    // ---------------------------------------

    ebayStoreUpdate: function()
    {
        var self = EbayAccountObj ;
        self.submitForm(M2ePro.url.get('formSubmit', {'update_ebay_store': 1, 'back': Base64.encode('edit')}));
    },

    ebayStoreSelectCategory: function(id)
    {
        $('ebay_store_categories_selected_container').show();
        $('ebay_store_categories_selected').value = id;
    },

    ebayStoreSelectCategoryHide: function()
    {
        $('ebay_store_categories_selected_container').hide();
        $('ebay_store_categories_selected').value = '';
    },

    ebayStoreInitExtTree: function(categoriesTreeArray)
    {
        var tree = new Ext.tree.TreePanel('tree-div', {
            animate: true,
            enableDD: false,
            containerScroll: true,
            rootUIProvider: Ext.tree.CheckboxNodeUI,
            rootVisible: false
        });

        tree.on('check', function(node, checked) {
            varienElementMethods.setHasChanges(node.getUI().checkbox);
            tree.getRootNode().cascade(function(n) {
                var ui = n.getUI();
                if(node !== n && ui.checkbox !== undefined) {
                    ui.checkbox.checked = false;
                }
            });
            EbayAccountObj.ebayStoreSelectCategory(node.attributes.id);
        }, tree);

        var root = new Ext.tree.TreeNode({
            text: 'root',
            draggable: false,
            checked: 'false',
            id: '__root__',
            uiProvider: Ext.tree.CheckboxNodeUI
        });

        tree.setRootNode(root);

        var buildCategoryTree = function (parent, config) {
            if (!config) return null;

            if (parent && config && config.length){

                for (var i = 0; i < config.length; i++) {
                    config[i].uiProvider = Ext.tree.CheckboxNodeUI;
                    var node = new Ext.tree.TreeNode(config[i]);
                    parent.appendChild(node);
                    if(config[i].children) {
                        buildCategoryTree(node, config[i].children);
                    }
                }
            }
        };

        buildCategoryTree(root, categoriesTreeArray);

        tree.addListener('click', function(node){
            node.getUI().check(!node.getUI().checked());
        });

        tree.render();
        root.expand();
    },

    // ---------------------------------------

    magentoOrdersListingsModeChange: function()
    {
        var self = EbayAccountObj ;

        if ($('magento_orders_listings_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_MODE_YES')) {
            $('magento_orders_listings_store_mode_container').show();
        } else {
            $('magento_orders_listings_store_mode_container').hide();
            $('magento_orders_listings_store_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT');
        }

        self.magentoOrdersListingsStoreModeChange();
        self.changeVisibilityForOrdersModesRelatedBlocks();
    },

    magentoOrdersListingsStoreModeChange: function()
    {
        if ($('magento_orders_listings_store_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM')) {
            $('magento_orders_listings_store_id_container').show();
        } else {
            $('magento_orders_listings_store_id_container').hide();
            $('magento_orders_listings_store_id').value = '';
        }
    },

    magentoOrdersListingsOtherModeChange: function()
    {
        var self = EbayAccountObj ;

        if ($('magento_orders_listings_other_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES')) {
            $('magento_orders_listings_other_product_mode_container').show();
            $('magento_orders_listings_other_store_id_container').show();
        } else {
            $('magento_orders_listings_other_product_mode_container').hide();
            $('magento_orders_listings_other_store_id_container').hide();
            $('magento_orders_listings_other_product_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE');
            $('magento_orders_listings_other_store_id').value = '';
        }

        self.magentoOrdersListingsOtherProductModeChange();
        self.changeVisibilityForOrdersModesRelatedBlocks();
    },

    magentoOrdersListingsOtherProductModeChange: function()
    {
        if ($('magento_orders_listings_other_product_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE')) {
            $('magento_orders_listings_other_product_mode_note').hide();
            $('magento_orders_listings_other_product_tax_class_id_container').hide();
        } else {
            $('magento_orders_listings_other_product_mode_note').show();
            $('magento_orders_listings_other_product_tax_class_id_container').show();
        }
    },

    magentoOrdersNumberSourceChange: function()
    {
        var self = EbayAccountObj ;
        self.renderOrderNumberExample();
    },

    magentoOrdersNumberPrefixModeChange: function()
    {
        var self = EbayAccountObj ;

        if ($('magento_orders_number_prefix_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_YES')) {
            $('magento_orders_number_prefix_container').show();
        } else {
            $('magento_orders_number_prefix_container').hide();
            $('magento_orders_number_prefix_prefix').value = '';
        }

        self.renderOrderNumberExample();
    },

    magentoOrdersNumberPrefixPrefixChange: function()
    {
        var self = EbayAccountObj ;
        self.renderOrderNumberExample();
    },

    renderOrderNumberExample: function()
    {
        var orderNumber = $('sample_magento_order_id').value;
        if ($('magento_orders_number_source').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL')) {
            orderNumber = $('sample_ebay_order_id').value;
        }

        if ($('magento_orders_number_prefix_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_YES')) {
            orderNumber = $('magento_orders_number_prefix_prefix').value + orderNumber;
        }

        $('order_number_example_container').update(orderNumber);
    },

    magentoOrdersCustomerModeChange: function()
    {
        var customerMode = $('magento_orders_customer_mode').value;

        if (customerMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED')) {
            $('magento_orders_customer_id_container').show();
            $('magento_orders_customer_id').addClassName('M2ePro-account-product-id');
        } else {  // M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::ORDERS_CUSTOMER_MODE_GUEST') || M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::ORDERS_CUSTOMER_MODE_NEW')
            $('magento_orders_customer_id_container').hide();
            $('magento_orders_customer_id').value = '';
            $('magento_orders_customer_id').removeClassName('M2ePro-account-product-id');
        }

        var action = (customerMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_CUSTOMER_MODE_NEW')) ? 'show' : 'hide';
        $('magento_orders_customer_new_website_id_container')[action]();
        $('magento_orders_customer_new_group_id_container')[action]();
        $('magento_orders_customer_new_notifications_container')[action]();

        if(action == 'hide') {
            $('magento_orders_customer_new_website_id').value = '';
            $('magento_orders_customer_new_group_id').value = '';
            $('magento_orders_customer_new_notifications').value = '';
        }
    },

    magentoOrdersInStorePickupStatusesModeChange: function()
    {
        if ($('magento_orders_in_store_pickup_statuses_mode').value == 1) {
            $('magento_orders_in_store_pickup_statuses_ready_for_pickup_tr').show();
            $('magento_orders_in_store_pickup_statuses_picked_up_tr').show();
        } else {
            $('magento_orders_in_store_pickup_statuses_ready_for_pickup_tr').hide();
            $('magento_orders_in_store_pickup_statuses_picked_up_tr').hide();
        }
    },

    magentoOrdersStatusMappingModeChange: function()
    {
        // Reset dropdown selected values to default
        $('magento_orders_status_mapping_new').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_STATUS_MAPPING_NEW');
        $('magento_orders_status_mapping_paid').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_STATUS_MAPPING_PAID');
        $('magento_orders_status_mapping_shipped').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED');
        // Default auto create invoice & shipping
        $('magento_orders_invoice_mode').checked = true;
        $('magento_orders_shipment_mode').checked = true;
        var disabled = $('magento_orders_status_mapping_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT');
        $('magento_orders_status_mapping_new').disabled = disabled;
        $('magento_orders_status_mapping_paid').disabled = disabled;
        $('magento_orders_status_mapping_shipped').disabled = disabled;
        $('magento_orders_invoice_mode').disabled = disabled;
        $('magento_orders_shipment_mode').disabled = disabled;
    },

    magentoOrdersCreationModeChange: function()
    {
        var creationMode = $('magento_orders_creation_mode').value;

        if (creationMode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_CREATE_IMMEDIATELY')) {
            $('magento_orders_creation_mode_immediately_warning').show();
            $('magento_orders_creation_reservation_days_container').show();
            $('magento_orders_qty_reservation_days').value = 1;
            $('magento_orders_qty_reservation_days_container').hide();
        } else {
            $('magento_orders_creation_mode_immediately_warning').hide();
            $('magento_orders_creation_reservation_days').value = 0;
            $('magento_orders_creation_reservation_days_container').hide();
            $('magento_orders_qty_reservation_days_container').show();
        }
    },

    changeVisibilityForOrdersModesRelatedBlocks: function()
    {
        var self = EbayAccountObj ;

        if ($('magento_orders_listings_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_MODE_NO') &&
            $('magento_orders_listings_other_mode').value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_NO')) {

            $('magento_block_ebay_accounts_magento_orders_number-wrapper').hide();
            $('magento_orders_number_source').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO');
            $('magento_orders_number_prefix_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO');
            self.magentoOrdersNumberPrefixModeChange();

            $('magento_block_ebay_accounts_magento_orders_customer-wrapper').hide();
            $('magento_orders_customer_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST');
            self.magentoOrdersCustomerModeChange();

            $('magento_block_ebay_accounts_magento_orders_status_mapping-wrapper').hide();
            $('magento_orders_status_mapping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT');
            self.magentoOrdersStatusMappingModeChange();

            $('magento_block_ebay_accounts_magento_orders_rules-wrapper').hide();
            $('magento_orders_creation_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID');
            $('magento_orders_creation_reservation_days').value = 0;
            $('magento_orders_qty_reservation_days').value = 1;

            $('magento_block_ebay_accounts_magento_orders_tax-wrapper').hide();
            $('magento_orders_tax_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::MAGENTO_ORDERS_TAX_MODE_MIXED');
        } else {
            $('magento_block_ebay_accounts_magento_orders_number-wrapper').show();
            $('magento_block_ebay_accounts_magento_orders_customer-wrapper').show();
            $('magento_block_ebay_accounts_magento_orders_status_mapping-wrapper').show();
            $('magento_block_ebay_accounts_magento_orders_rules-wrapper').show();
            $('magento_block_ebay_accounts_magento_orders_tax-wrapper').show();
        }
    },

    // ---------------------------------------

    other_listings_synchronization_change: function()
    {
        var relatedStoreViews = $('magento_block_ebay_accounts_other_listings_related_store_views-wrapper');

        if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_SYNCHRONIZATION_YES')) {
            $('other_listings_mapping_mode_tr').show();
            $('other_listings_mapping_mode').simulate('change');
            if (relatedStoreViews) {
                relatedStoreViews.show();
            }
        } else {
            $('other_listings_mapping_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_MODE_NO');
            $('other_listings_mapping_mode').simulate('change');
            $('other_listings_mapping_mode_tr').hide();
            if (relatedStoreViews) {
                relatedStoreViews.hide();
            }
        }
    },

    other_listings_mapping_mode_change: function()
    {
        if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_MODE_YES')) {
            $('magento_block_ebay_accounts_other_listings_product_mapping-wrapper').show();
        } else {
            $('magento_block_ebay_accounts_other_listings_product_mapping-wrapper').hide();

            $('mapping_sku_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE');
            $('mapping_title_mode').value = M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE');
        }

        $('mapping_sku_mode').simulate('change');
        $('mapping_title_mode').simulate('change');
    },

    synchronization_mapped_change: function()
    {
       if (this.value == 0) {
           $('settings_button').hide();
       } else {
           $('settings_button').show();
       }
    },

    mapping_sku_mode_change: function()
    {
        var self        = EbayAccountObj ,
            attributeEl = $('mapping_sku_attribute');

        $('mapping_sku_priority').hide();
        if (this.value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE')) {
            $('mapping_sku_priority').show();
        }

        attributeEl.value = '';
        if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE')) {
            self.updateHiddenValue(this, attributeEl);
        }
    },

    mapping_title_mode_change: function()
    {
        var self        = EbayAccountObj ,
            attributeEl = $('mapping_title_attribute');

        $('mapping_title_priority').hide();
        if (this.value != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE')) {
            $('mapping_title_priority').show();
        }

        attributeEl.value = '';
        if (this.value == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE')) {
            self.updateHiddenValue(this, attributeEl);
        }
    }

    // ---------------------------------------
    });

});