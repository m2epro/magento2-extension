define([
    'M2ePro/Listing/Other/Grid'
], function () {
    window.EbayListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        getComponent: function()
        {
            return 'ebay';
        },

        // ---------------------------------------

        getLogViewUrl: function(rowId)
        {
            return M2ePro.url.get('ebay_listing_other_log/index', {
                id: rowId
            });
        },

        // ---------------------------------------

        getSelectedItemsParts: function()
        {
            var selectedProductsArray = this.getSelectedProductsArray();

            if (this.getSelectedProductsString() == '' || selectedProductsArray.length == 0) {
                return [];
            }

            var maxProductsInPart = this.getMaxProductsInPart();

            var result = [];
            for (var i=0;i<selectedProductsArray.length;i++) {
                if (result.length == 0 || result[result.length-1].length == maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length-1][result[result.length-1].length] = selectedProductsArray[i];
            }

            return result;
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 10;
        },

        // ---------------------------------------

        prepareActions: function($super)
        {
            $super();

            this.actionHandler = new EbayListingOtherAction(this);

            this.actions = Object.extend(this.actions, {
                relistAction: this.actionHandler.relistAction.bind(this.actionHandler),
                reviseAction: this.actionHandler.reviseAction.bind(this.actionHandler),
                stopAction: this.actionHandler.stopAction.bind(this.actionHandler)
            });
        }

        // ---------------------------------------
    });
});