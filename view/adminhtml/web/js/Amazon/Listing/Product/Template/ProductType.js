define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (modal, MessageObj) {

    window.AmazonListingProductProductType = Class.create(Action, {

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            this.listingGrid = gridHandler
            $super(gridHandler);
        },

        // ---------------------------------------

        validateProductsAndAssign: function (productsIds) {
            var self = this;
            self.flagSuccess = false;

            productsIds = productsIds || self.listingGrid.productSearchHandler.params.productId;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productType/validateProductsForAssign'), {
                method: 'post',
                parameters: {
                    products_ids: productsIds
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    if (response.messages.length > 0) {
                        MessageObj.clear();
                        response.messages.each(function (msg) {
                            MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
                        });
                    }

                    if (!response.html) {
                        return;
                    }

                    self.openProductTypePopUp(
                        response.products_ids,
                        M2ePro.translator.translate('productTypePopupTitle'),
                        response.html
                    );
                }
            });
        },

        mapToProductType: function (el, templateId, mapToGeneralId) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productType/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids: self.productTypePopup.productsIds,
                                product_type_id: templateId
                            },
                            onSuccess: function (transport) {

                                if (!transport.responseText.isJSON()) {
                                    self.alert(transport.responseText);
                                    return;
                                }

                                var response = transport.responseText.evalJSON();

                                if (mapToGeneralId) {
                                    ListingGridObj.productSearchHandler.addNewGeneralId(response.products_ids);
                                } else {
                                    self.gridHandler.unselectAllAndReload();

                                    if (response.messages.length > 0) {
                                        MessageObj.clear();
                                        response.messages.each(function (msg) {
                                            MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
                                        });
                                    }
                                }
                            }
                        });

                        self.productTypePopup.modal('closeModal');
                        ProductTypeValidatorPopup.open(self.productTypePopup.productsIds)
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        openProductTypePopUp: function (productsIds, title, contentData, checkIsNewAsinAccepted) {
            var self = this;
            self.gridHandler.unselectAll();

            MessageObj.clear();

            var popupEl = $('product_type_pop_up_content');

            if (popupEl) {
                popupEl.remove();
            }

            $('html-body').insert({bottom: contentData});

            self.productTypePopup = jQuery('#product_type_pop_up_content');

            modal({
                title: title,
                type: 'slide',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    click: function () {
                        self.productTypePopup.modal('closeModal');
                    }
                }, {
                    text: M2ePro.translator.translate('Add New Product Type'),
                    class: 'action primary ',
                    click: function () {
                        self.createInNewTab(M2ePro.url.get('createProductTypeUrl'));
                    }
                }]
            }, self.productTypePopup);

            self.productTypePopup.modal('openModal');

            self.productTypePopup.productsIds = productsIds;
            self.productTypePopup.checkIsNewAsinAccepted = checkIsNewAsinAccepted || 0;

            self.loadGrid();
        },

        loadGrid: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productType/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.productTypePopup.productsIds,
                    check_is_new_asin_accepted: self.productTypePopup.checkIsNewAsinAccepted
                },
                onSuccess: function (transport) {
                    const productTypeGrid = $('product_type_grid');
                    productTypeGrid.update(transport.responseText);
                    productTypeGrid.show();
                }
            });
        },

        // ---------------------------------------

        assign: function (productTypeId) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productType/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids:  self.productTypePopup.productsIds,
                                product_type_id:   productTypeId
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

                        self.productTypePopup.modal('closeModal');
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

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_productType/unassign'), {
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
