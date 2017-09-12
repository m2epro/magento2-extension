define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (modal, MessageObj) {

    window.AmazonListingProductTemplateShipping = Class.create(Action, {

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            var self = this;
            $super(gridHandler);
        },

        // ---------------------------------------

        options: {},

        setOptions: function (options) {
            this.options = Object.extend(this.options, options);
            return this;
        },

        // ---------------------------------------

        openPopUp: function (productsIds, shippingMode) {
            var self = this;
            self.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/viewPopup'), {
                method: 'post',
                parameters: {
                    products_ids:  productsIds,
                    shipping_mode: shippingMode
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    if (!response.html) {
                        if (response.messages.length > 0) {
                            MessageObj.clear();
                            response.messages.each(function (msg) {
                                MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1) + 'Message'](msg.text);
                            });
                        }

                        return;
                    }

                    var popupElId = (shippingMode == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::SHIPPING_MODE_OVERRIDE'))
                        ? '#template_shippingOverride_pop_up_content'
                        : '#template_shippingTemplate_pop_up_content';

                    var popupEl = jQuery(popupElId);

                    if (popupEl.length) {
                        popupEl.remove();
                    }

                    $('html-body').insert({bottom: response.html});

                    self.templateShippingPopup = jQuery(popupElId);

                    var title             = M2ePro.translator.translate('templateShippingTemplatePopupTitle');
                    var addNewPolicyTitle = M2ePro.translator.translate('Add New Shipping Template Policy');

                    if (shippingMode == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Account::SHIPPING_MODE_OVERRIDE')) {

                        title             = M2ePro.translator.translate('templateShippingOverridePopupTitle');
                        addNewPolicyTitle = M2ePro.translator.translate('Add New Shipping Override Policy');
                    }

                    modal({
                        title: title,
                        type: 'slide',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            click: function () {
                                self.templateShippingPopup.modal('closeModal');
                            }
                        }, {
                            text: addNewPolicyTitle,
                            class: 'action primary ',
                            click: function () {
                                self.createInNewTab(self.newTemplateUrl, shippingMode);
                            }
                        }]
                    }, self.templateShippingPopup);

                    self.templateShippingPopup.productsIds = response.products_ids;

                    self.templateShippingPopup.modal('openModal');

                    $('template_shipping_grid').observe('click', function (event) {
                        if (!event.target.hasClassName('assign-shipping-template')) {
                            return;
                        }

                        self.assign(event.target.getAttribute('templateShippingId'), shippingMode);
                    });

                    $('template_shipping_grid').on('click', '.new-shipping-template', function() {
                        self.createInNewTab(self.newTemplateUrl, shippingMode);
                    });

                    self.loadGrid(shippingMode);
                }
            });
        },

        loadGrid: function (shippingMode) {

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.templateShippingPopup.productsIds,
                    shipping_mode: shippingMode
                },
                onSuccess: function (transport) {
                    $('template_shipping_grid').update(transport.responseText);
                    $('template_shipping_grid').show();
                }
            });
        },

        // ---------------------------------------

        assign: function (templateId, shippingMode) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids:  self.templateShippingPopup.productsIds,
                                shipping_mode: shippingMode,
                                template_id:   templateId
                            },
                            onSuccess: function (transport) {

                                if (!transport.responseText.isJSON()) {
                                    self.alert(transport.responseText);
                                    return;
                                }

                                self.gridHandler.unselectAllAndReload();

                                var response = transport.responseText.evalJSON();

                                MessageObj.clear();
                                response.messages.each(function (msg) {
                                    MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1) + 'Message'](msg.text);
                                });
                            }
                        });

                        self.templateShippingPopup.modal('closeModal');
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        unassign: function (productsIds, shippingMode) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/unassign'), {
                method: 'post',
                parameters: {
                    products_ids:  productsIds,
                    shipping_mode: shippingMode
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    self.gridHandler.unselectAllAndReload();

                    var response = transport.responseText.evalJSON();

                    MessageObj.clear();
                    response.messages.each(function (msg) {
                        MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1) + 'Message'](msg.text);
                    });
                }
            });
        },

        // ---------------------------------------

        createInNewTab: function (url, shippingMode) {
            var self = this;
            var win = window.open(url);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                self.loadGrid(shippingMode);
            }, 1000);
        }

        // ---------------------------------------
    });
});