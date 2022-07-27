define([
    'M2ePro/Listing/Other/Grid'
], function () {
    window.WalmartListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        tryToMove: function (listingId)
        {
            this.movingHandler.submit(listingId, this.onSuccess);
        },

        onSuccess: function (listingId)
        {
            setLocation(M2ePro.url.get('categorySettings', {id: listingId}));
        }

        // ---------------------------------------
    });
});