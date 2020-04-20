define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (modal, MessageObj) {

    window.AmazonListingProductTemplateProductTaxCode = Class.create(Action, {

        // ---------------------------------------

        initialize: function($super,gridHandler)
        {
            var self = this;
            $super(gridHandler);
        },

        // ---------------------------------------

        options: {},

        setOptions: function(options)
        {
            this.options = Object.extend(this.options,options);
            return this;
        },

        // ---------------------------------------

        openPopUp: function(productsIds)
        {
            var self = this;
            self.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productTaxCode/viewPopup'), {
                method: 'post',
                parameters: {
                    products_ids:  productsIds
                },
                onSuccess: function(transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return
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

                    var popupElId = '#template_productTaxCode_pop_up_content';

                    var popupEl = jQuery(popupElId);

                    if (popupEl.length) {
                        popupEl.remove();
                    }

                    $('html-body').insert({bottom: response.html});

                    self.templateProductTaxCodePopup = jQuery(popupElId);

                    modal({
                        title: M2ePro.translator.translate('templateProductTaxCodePopupTitle'),
                        type: 'slide',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            click: function () {
                                self.templateProductTaxCodePopup.modal('closeModal');
                            }
                        }, {
                            text: M2ePro.translator.translate('Add New Product Tax Code Policy'),
                            class: 'action primary ',
                            click: function () {
                                self.createInNewTab(self.newTemplateUrl);
                            }
                        }]
                    }, self.templateProductTaxCodePopup);

                    self.templateProductTaxCodePopup.productsIds = response.products_ids;

                    self.templateProductTaxCodePopup.modal('openModal');

                    var grid = $('template_productTaxCode_grid');

                    grid.observe('click', function(event) {
                        if (!event.target.hasClassName('assign-productTaxCode-template')) {
                            return;
                        }

                        self.assign(event.target.getAttribute('templateProductTaxCodeId'));
                    });

                    grid.on('click', '.new-productTaxCode-template', function() {
                        self.createInNewTab(self.newTemplateUrl);
                    });

                    self.loadGrid();
                }
            });
        },

        // ---------------------------------------

        assign: function(templateId)
        {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productTaxCode/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids:  self.templateProductTaxCodePopup.productsIds,
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

                        self.templateProductTaxCodePopup.modal('closeModal');
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        unassign: function(productsIds)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productTaxCode/unassign'), {
                method: 'post',
                parameters: {
                    products_ids: productsIds
                },
                onSuccess: function(transport) {

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

        loadGrid: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productTaxCode/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.templateProductTaxCodePopup.productsIds
                },
                onSuccess: function(transport) {
                    var grid = $('template_productTaxCode_grid');
                    grid.update(transport.responseText);
                    grid.show();
                }
            });
        },

        // ---------------------------------------

        createInNewTab: function(stepWindowUrl)
        {
            var self = this;
            var win = window.open(stepWindowUrl);

            var intervalId = setInterval(function() {
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