define([
    'M2ePro/Listing/View/Action'
], function () {

    window.EbayListingViewAction = Class.create(ListingViewAction, {

        // ---------------------------------------

        startActions: function($super,title,url,selectedProductsParts,requestParams)
        {
            if (typeof requestParams == 'undefined') {
                requestParams = {};
            }

            if (typeof requestParams['is_realtime'] == 'undefined') {
                requestParams['is_realtime'] = (this.gridHandler.getSelectedProductsArray().length <= 10);
            }

            $super(title,url,selectedProductsParts,requestParams);
        },

        stopAction: function()
        {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts(100);
            if (selectedProductsParts.length == 0) {
                return;
            }

            var requestParams = {'is_realtime': (this.gridHandler.getSelectedProductsArray().length <= 100)};

            this.startActions(
                M2ePro.translator.translate('stopping_selected_items_message'),
                M2ePro.url.get('ebay_listing/runStopProducts'),
                selectedProductsParts,
                requestParams
            );
        },

        stopAndRemoveAction: function()
        {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts(100);
            if (selectedProductsParts.length == 0) {
                return;
            }

            var requestParams = {'is_realtime': (this.gridHandler.getSelectedProductsArray().length <= 100)};

            this.startActions(
                M2ePro.translator.translate('stopping_and_removing_selected_items_message'),
                M2ePro.url.get('ebay_listing/runStopAndRemoveProducts'),
                selectedProductsParts,
                requestParams
            );
        }

        // ---------------------------------------
    });
});