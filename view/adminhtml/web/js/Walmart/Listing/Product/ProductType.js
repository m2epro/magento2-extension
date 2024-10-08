define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (modal, MessageObj) {

    window.WalmartListingProductProductType = Class.create(Action, {

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            $super(gridHandler);
        },

        // ---------------------------------------

        mapToProductType: function (el, templateId) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('walmart_listing_product_productType/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids: self.productTypePopup.productsIds,
                                template_id: templateId
                            },
                            onSuccess: function (transport) {

                                if (!transport.responseText.isJSON()) {
                                    self.alert(transport.responseText);
                                    return;
                                }

                                var response = transport.responseText.evalJSON();

                                self.gridHandler.unselectAllAndReload();

                                if (response.messages.length > 0) {
                                    MessageObj.clear();
                                    response.messages.each(function (msg) {
                                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1)](msg);
                                    });
                                }
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

        validateProductsForProductTypeAssign: function (productsIds, magentoCategoriesIds) {
            var self = this;
            self.flagSuccess = false;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_productType/validateProductsForAssign'), {
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

                    if (typeof popUp != 'undefined') {
                        self.productTypePopup.modal('closeModal');
                    }

                    self.openPopUp(
                            0,
                            M2ePro.translator.translate('productTypePopupTitle'),
                            response.products_ids,
                            magentoCategoriesIds,
                            response.html
                    );
                }
            });
        },

        // ---------------------------------------

        openPopUp: function (mode, title, productsIds, magentoCategoriesIds, contentData) {
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
                    class: 'action primary add_new_product_type',
                    click: function () {
                        self.createInNewTab(M2ePro.url.get('createProductTypeUrl'));
                    }
                }]
            }, self.productTypePopup);

            self.productTypePopup.modal('openModal');

            self.productTypePopup.productsIds = productsIds;
            self.productTypePopup.magentoCategoriesIds = magentoCategoriesIds;

            self.loadProductTypeGrid();
        },

        loadProductTypeGrid: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_productType/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.productTypePopup.productsIds,
                    magento_categories_ids: self.productTypePopup.magentoCategoriesIds
                },
                onSuccess: function (transport) {
                    $('product_type_grid').update(transport.responseText);
                    $('product_type_grid').show();
                }
            });
        },

        // ---------------------------------------

        createInNewTab: function (stepWindowUrl) {
            var self = this;
            var win = window.open(stepWindowUrl);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                walmartProductTypeGridJsObject.reload();
            }, 1000);
        },

        loadGrid: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_productType/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.productTypePopup.productsIds,
                },
                onSuccess: function (transport) {
                    const productTypeGrid = $('product_type_grid');
                    productTypeGrid.update(transport.responseText);
                    productTypeGrid.show();
                }
            });
        },

        // ---------------------------------------
    });
});
