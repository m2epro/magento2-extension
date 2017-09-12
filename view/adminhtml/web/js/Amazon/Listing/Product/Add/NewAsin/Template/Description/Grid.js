define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Amazon/Listing/View/Action',
    'M2ePro/Amazon/Listing/Product/Template/Description'
], function (modal, MessageObj) {

    window.AmazonListingProductAddNewAsinTemplateDescriptionGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getComponent: function () {
            return 'amazon';
        },

        // ---------------------------------------

        getMaxProductsInPart: function () {
            return 1000;
        },

        // ---------------------------------------

        prepareActions: function ($super) {
            $super();
            this.actionHandler = new AmazonListingViewAction(this);
            this.templateDescriptionHandler = new AmazonListingProductTemplateDescription(this);

            this.actions = Object.extend(this.actions, {

                setDescriptionTemplateAction: (function () {
                    this.mapToNewAsin(this.getSelectedProductsString())
                }).bind(this),
                resetDescriptionTemplateAction: (function () {
                    this.unmapFromNewAsin(this.getSelectedProductsString())
                }).bind(this),

                setDescriptionTemplateByCategoryAction: (function () {
                    this.mapToNewAsin(this.getSelectedProductsStringFromCategory())
                }).bind(this),
                resetDescriptionTemplateByCategoryAction: (function () {
                    this.unmapFromNewAsin(this.getSelectedProductsStringFromCategory())
                }).bind(this)
            });
        },

        // ---------------------------------------

        parseResponse: function (response) {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        afterInitPage: function ($super) {
            $super();
        },

        setDescriptionTemplateRowAction: function (id) {
            this.mapToNewAsin(id);
        },

        resetDescriptionTemplateRowAction: function (id) {
            this.unmapFromNewAsin(id);
        },

        // ---------------------------------------

        setDescriptionTemplateByCategoryRowAction: function (id) {
            this.mapToNewAsin(this.getSelectedProductsStringFromCategory(id));
        },

        resetDescriptionTemplateByCategoryRowAction: function (id) {
            this.unmapFromNewAsin(this.getSelectedProductsStringFromCategory(id));
        },

        // ---------------------------------------

        getSelectedProductsStringFromCategory: function (categoryIds) {
            var productsIdsStr = '';

            categoryIds = categoryIds || this.getGridMassActionObj().checkedString;
            categoryIds = explode(',', categoryIds);

            categoryIds.each(function (categoryId) {

                if (productsIdsStr != '') {
                    productsIdsStr += ',';
                }
                productsIdsStr += $('products_ids_' + categoryId).value;
            });

            return productsIdsStr;
        },

        // ---------------------------------------

        mapToNewAsin: function (listingProductIds) {
            var self = this;
            new Ajax.Request(M2ePro.url.get('amazon_listing_product/mapToNewAsin'), {
                method: 'post',
                parameters: {
                    products_ids: listingProductIds
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    self.templateDescriptionHandler.gridHandler.unselectAllAndReload();

                    if (response.products_ids.length > 0) {
                        ListingGridHandlerObj.templateDescriptionHandler.openPopUp(
                            0, M2ePro.translator.translate('templateDescriptionPopupTitle'),
                            response.products_ids, response.html, 1);
                    } else {
                        if (response.messages.length > 0) {
                            MessageObj.clear();
                            response.messages.each(function (msg) {
                                MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1) + 'Message'](msg.text);
                            });
                        }
                    }
                }
            });
        },

        unmapFromNewAsin: function (productsIds) {
            var self = this;

            self.templateDescriptionHandler.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product/unmapFromAsin'), {
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

                    if (response.type == 'success') {
                        self.templateDescriptionHandler.unassignFromTemplateDescription(productsIds);
                    }
                }
            });
        },

        mapToTemplateDescription: function (el, templateId, mapToGeneralId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_description/assign'), {
                method: 'post',
                parameters: {
                    products_ids: ListingGridHandlerObj.templateDescriptionHandler.templateDescriptionPopup.productsIds,
                    template_id: templateId
                },
                onSuccess: function (transport) {
                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();
                    self.mapToNewAsin(response.products_ids);
                }
            });

            ListingGridHandlerObj.templateDescriptionHandler.templateDescriptionPopup.modal('closeModal');
        },

        checkCategoryProducts: function (url) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_add/checkNewAsinCategoryProducts'), {
                method: 'post',
                onSuccess: function (transport) {

                    if (transport.responseText == 1) {
                        setLocation(url);
                    } else {
                        if (!transport.responseText.isJSON()) {
                            self.alert(transport.responseText);
                            return;
                        }

                        var response = transport.responseText.evalJSON();

                        MessageObj.clear();
                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.text);
                    }
                }
            });
        },

        checkManualProducts: function (url) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_add/checkNewAsinManualProducts'), {
                method: 'post',
                onSuccess: function (transport) {

                    if (transport.responseText == 1) {
                        setLocation(url);
                    } else {
                        if (!transport.responseText.isJSON()) {
                            self.alert(transport.responseText);
                            return;
                        }

                        var response = transport.responseText.evalJSON();

                        if ($('new_asin_skip_popup_content')) {
                            $('new_asin_skip_popup_content').remove();
                        }
                        $('html-body').insert({bottom: response.html});

                        $('total_count').innerHTML = response.total_count;
                        $('failed_count').update(response.failed_count);

                        self.skipPopup = jQuery('#new_asin_skip_popup_content');

                        modal({
                            title: M2ePro.translator.translate('setDescriptionPolicy'),
                            type: 'popup',
                            buttons: [ {
                                text: M2ePro.translator.translate('Cancel'),
                                class: 'action-secondary action-dismiss',
                                click: function () {
                                    self.skipPopup.modal('closeModal');
                                }
                            }, {
                                text: M2ePro.translator.translate('Continue'),
                                class: 'action-primary action-accept',
                                click: function () {
                                    setLocation(response.continueUrl);
                                    self.skipPopup.modal('closeModal');
                                }
                            }]
                        }, self.skipPopup);

                        self.skipPopup.modal('openModal');
                    }
                }
            });
        }

        // ---------------------------------------
    });

});