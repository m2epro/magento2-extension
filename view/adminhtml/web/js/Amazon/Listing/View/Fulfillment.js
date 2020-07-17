define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (MessageObj) {

    window.AmazonListingViewFulfillment = Class.create(Action, {

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            var self = this;

            $super(gridHandler);

        },

        // ---------------------------------------

        switchToAFN: function (productsIds) {
            var self = this;
            self.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_fulfillment/switchToAFN'), {
                method: 'post',
                parameters: {
                    selected_products: productsIds
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    self.gridHandler.unselectAllAndReload();

                    MessageObj.clear();
                    response.messages.each(function (msg) {
                        MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
                    });
                }
            });
        },

        // ---------------------------------------

        switchToMFN: function (productsIds) {
            var self = this;
            self.gridHandler.unselectAll();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_fulfillment/switchToMFN'), {
                method: 'post',
                parameters: {
                    selected_products: productsIds
                },
                onSuccess: function (transport) {
                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    self.gridHandler.unselectAllAndReload();

                    MessageObj.clear();
                    response.messages.each(function (msg) {
                        MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
                    });
                }
            });
        }
    });

});
