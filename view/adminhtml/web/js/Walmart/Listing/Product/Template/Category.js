define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (modal, MessageObj) {

    window.WalmartListingProductTemplateCategory = Class.create(Action, {

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

        mapToTemplateCategory: function (el, templateId) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('walmart_listing_product_template_category/assign'), {
                            method: 'post',
                            parameters: {
                                products_ids: self.templateCategoryPopup.productsIds,
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
                                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](msg);
                                    });
                                }
                            }
                        });

                        self.templateCategoryPopup.modal('closeModal');
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        validateProductsForTemplateCategoryAssign: function (productsIds, magentoCategoriesIds) {
            var self = this;
            self.flagSuccess = false;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_template_category/validateProductsForAssign'), {
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
                            MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1) + 'Message'](msg.text);
                        });
                    }

                    if (!response.html) {
                        return;
                    }

                    if (typeof popUp != 'undefined') {
                        self.templateCategoryPopup.modal('closeModal');
                    }

                    self.openPopUp(0, M2ePro.translator.translate('templateCategoryPopupTitle'), response.products_ids, magentoCategoriesIds, response.html);
                }
            });
        },

        // ---------------------------------------

        openPopUp: function (mode, title, productsIds, magentoCategoriesIds, contentData) {
            var self = this;
            self.gridHandler.unselectAll();

            MessageObj.clear();

            var popupEl = $('template_category_pop_up_content');

            if (popupEl) {
                popupEl.remove();
            }

            $('html-body').insert({bottom: contentData});

            self.templateCategoryPopup = jQuery('#template_category_pop_up_content');

            modal({
                title: title,
                type: 'slide',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    click: function () {
                        self.templateCategoryPopup.modal('closeModal');
                    }
                }, {
                    text: M2ePro.translator.translate('Add New Category Policy'),
                    class: 'action primary ',
                    click: function () {
                        self.createTemplateCategoryInNewTab(M2ePro.url.get('newTemplateCategoryUrl'));
                    }
                }]
            }, self.templateCategoryPopup);

            self.templateCategoryPopup.modal('openModal');

            self.templateCategoryPopup.productsIds = productsIds;
            self.templateCategoryPopup.magentoCategoriesIds = magentoCategoriesIds;

            self.loadTemplateCategoryGrid();
        },

        loadTemplateCategoryGrid: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_template_category/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.templateCategoryPopup.productsIds,
                    magento_categories_ids: self.templateCategoryPopup.magentoCategoriesIds
                },
                onSuccess: function (transport) {
                    $('template_category_grid').update(transport.responseText);
                    $('template_category_grid').show();
                }
            });
        },

        // ---------------------------------------

        createTemplateCategoryInNewTab: function (stepWindowUrl) {
            var self = this;
            var win = window.open(stepWindowUrl);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                walmartTemplateCategoryGridJsObject.reload();
            }, 1000);
        }

        // ---------------------------------------
    });

});