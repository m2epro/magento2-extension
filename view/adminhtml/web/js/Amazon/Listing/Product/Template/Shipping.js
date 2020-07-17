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

        openPopUp: function (productsIds) {
            var self = this;
            self.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/viewPopup'), {
                method: 'post',
                parameters: {
                    products_ids:  productsIds
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
                                MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
                            });
                        }

                        return;
                    }

                    var popupElId = '#template_shipping_pop_up_content';

                    var popupEl = jQuery(popupElId);

                    if (popupEl.length) {
                        popupEl.remove();
                    }

                    $('html-body').insert({bottom: response.html});

                    self.templateShippingPopup = jQuery(popupElId);

                    var title             = M2ePro.translator.translate('templateShippingPopupTitle');
                    var addNewPolicyTitle = M2ePro.translator.translate('Add New Shipping Policy');

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
                                self.createInNewTab(self.newTemplateUrl);
                            }
                        }]
                    }, self.templateShippingPopup);

                    self.templateShippingPopup.productsIds = response.products_ids;

                    self.templateShippingPopup.modal('openModal');

                    $('template_shipping_grid').observe('click', function (event) {
                        if (!event.target.hasClassName('assign-shipping-template')) {
                            return;
                        }

                        self.assign(event.target.getAttribute('templateShippingId'));
                    });

                    $('template_shipping_grid').on('click', '.new-shipping-template', function() {
                        self.createInNewTab(self.newTemplateUrl);
                    });

                    self.loadGrid();
                }
            });
        },

        loadGrid: function () {

            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.templateShippingPopup.productsIds,
                },
                onSuccess: function (transport) {
                    $('template_shipping_grid').update(transport.responseText);
                    $('template_shipping_grid').show();
                }
            });
        },

        // ---------------------------------------

        assign: function (templateId) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids:  self.templateShippingPopup.productsIds,
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
                                    MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
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

        unassign: function (productsIds) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_shipping/unassign'), {
                method: 'post',
                parameters: {
                    products_ids:  productsIds
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
                        MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
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
