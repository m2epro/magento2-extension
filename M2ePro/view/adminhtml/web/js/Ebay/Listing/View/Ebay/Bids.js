define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function(modal, MessageObj) {

    window.EbayListingViewEbayBids = Class.create(Action, {

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

        parseResponse: function (response) {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        openPopUp: function (productId, title) {
            var self = this;

            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('ebay_listing/getListingProductBids'), {
                method: 'post',
                parameters: {
                    product_id: productId
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_listing_product_bids');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_listing_product_bids"></div>'});
                    $('ebay_listing_product_bids').update(transport.responseText);

                    self.listingProductBidsPopup = jQuery('#ebay_listing_product_bids');

                    modal({
                        title: title,
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.listingProductBidsPopup.modal('closeModal')
                            }
                        }]
                    }, self.listingProductBidsPopup);

                    self.listingProductBidsPopup.modal('openModal');
                }
            });
        }

        // ---------------------------------------
    });
});