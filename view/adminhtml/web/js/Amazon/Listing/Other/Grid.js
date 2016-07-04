define([
    'M2ePro/Listing/Other/Grid'
], function () {
    window.AmazonListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        getLogViewUrl: function(rowId)
        {
            return M2ePro.url.get('amazon_listing_other_log/index', {
                id: rowId
            });
        }

        // ---------------------------------------
    });
});

