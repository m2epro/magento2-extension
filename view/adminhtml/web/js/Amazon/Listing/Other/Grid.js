define([
    'M2ePro/Listing/Other/Grid'
], function () {
    window.AmazonListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        tryToMove: function (listingId)
        {
            this.movingHandler.submit(listingId, this.onSuccess)
        },

        onSuccess: function () {
            this.unselectAllAndReload();
        }

        // ---------------------------------------
    });
});