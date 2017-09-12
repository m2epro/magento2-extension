define([
    'M2ePro/Plugin/Messages',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Action'
], function (MessageObj, alert) {

    window.AmazonListingProductRepricing = Class.create(Action, {

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            $super(gridHandler);
        },

        // ---------------------------------------

        options: {},

        setOptions: function (options) {
            this.options = Object.extend(this.options, options);
            return this;
        },

        // ---------------------------------------

        openManagement: function () {
            window.open(M2ePro.url.get('amazon_listing_product_repricing/openManagement'));
        },

        // ---------------------------------------

        addToRepricing: function (productsIds) {
            var self = this;
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_repricing/validateProductsBeforeAdd'), {
                method: 'post',
                parameters: {
                    products_ids: productsIds
                },
                onSuccess: function(transport) {
                    if (!transport.responseText.isJSON()) {
                        alert({
                            content: transport.responseText
                        });
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    if(response.products_ids.length === 0) {
                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);
                        return;
                    }

                    if (response.products_ids.length === productsIds.split(',').length) {
                        self.addToRepricingConfirm(productsIds);
                        return;
                    }

                    var popupEl = $('regular_price_popup');

                    if (popupEl) {
                        popupEl.remove();
                    }

                    $('html-body').insert({bottom: response});

                    self.regularPricePopup = jQuery('#regular_price_popup');

                    modal({
                        title: title,
                        type: 'slide',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            click: function () {
                                self.regularPricePopup.modal('closeModal');
                            }
                        }, {
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'action primary',
                            click: function () {
                                self.addToRepricingConfirm(productsIds);
                            }
                        }]
                    }, self.regularPricePopup);

                    self.regularPricePopup.modal('openModal');
                }
            });
        },

        addToRepricingConfirm: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openAddProducts'), {'products_ids': productsIds});
        },

        showDetails: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openShowDetails'), {'products_ids': productsIds});
        },

        editRepricing: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openEditProducts'), {'products_ids': productsIds});
        },

        removeFromRepricing: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openRemoveProducts'), {'products_ids': productsIds});
        }

        // ---------------------------------------
    });
});