define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (modal, MessageObj) {

    window.AmazonListingProductTemplateShippingOverride = Class.create(Action, {

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

        openPopUp: function (productsIds) {
            var self = this;
            self.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shippingOverride/viewPopup'), {
                method: 'post',
                parameters: {
                    products_ids: productsIds
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
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

                    var popupEl = $('template_shippingOverride_pop_up_content');

                    if (popupEl) {
                        popupEl.remove();
                    }

                    $('html-body').insert({bottom: response.html});

                    self.templateShippingOverridePopup = jQuery('#template_shippingOverride_pop_up_content');

                    modal({
                        title: M2ePro.translator.translate('templateShippingOverridePopupTitle'),
                        type: 'slide',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            click: function () {
                                self.templateShippingOverridePopup.modal('closeModal');
                            }
                        }, {
                            text: M2ePro.translator.translate('Add New Shipping Override Policy'),
                            class: 'action primary ',
                            click: function () {
                                self.createInNewTab(self.newTemplateUrl);
                            }
                        }]
                    }, self.templateShippingOverridePopup);

                    self.templateShippingOverridePopup.productsIds = response.products_ids;

                    self.templateShippingOverridePopup.modal('openModal');

                    $('template_shippingOverride_grid').observe('click', function (event) {
                        if (!event.target.hasClassName('assign-shipping-override-template')) {
                            return;
                        }

                        self.assign(event.target.getAttribute('templateShippingOverrideId'));
                    });

                    $('template_shippingOverride_grid').on('click', '.new-shipping-override-template', function() {
                        self.createInNewTab(self.newTemplateUrl);
                    });

                    self.loadGrid();
                }
            });
        },

        loadGrid: function () {

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shippingOverride/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.templateShippingOverridePopup.productsIds
                },
                onSuccess: function (transport) {
                    $('template_shippingOverride_grid').update(transport.responseText);
                    $('template_shippingOverride_grid').show();
                }
            });
        },

        // ---------------------------------------

        assign: function (templateId) {
            var self = this;

            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shippingOverride/assign'), {
                method: 'post',
                parameters: {
                    products_ids: self.templateShippingOverridePopup.productsIds,
                    template_id: templateId
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
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

            self.templateShippingOverridePopup.modal('closeModal');
        },

        // ---------------------------------------

        unassign: function (productsIds) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shippingOverride/unassign'), {
                method: 'post',
                parameters: {
                    products_ids: productsIds
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
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

        createInNewTab: function (url) {
            var self = this;
            var win = window.open(url);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                self.loadGrid();
            }, 1000);
        }

        // ---------------------------------------
    });
});