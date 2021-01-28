define([
    'M2ePro/Listing/Other/Grid'
], function () {
    window.EbayListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        tryToMove: function(listingId)
        {
            this.movingHandler.submit(listingId, this.onSuccess)
        },

        onSuccess: function(listingId, hasOnlineCategory = false)
        {
            var refererUrl = M2ePro.url.get('categorySettings', {id: listingId});

            if (hasOnlineCategory) {
                refererUrl = M2ePro.url.get('ebay_listing/view', {id: listingId});
            }

            setLocation(refererUrl);
        },

        // ---------------------------------------

        getComponent: function()
        {
            return 'ebay';
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
        }

        // ---------------------------------------
    });
});
