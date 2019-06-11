define([
    'M2ePro/Listing/Other/Grid'
], function () {
    window.WalmartListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        getLogViewUrl: function(rowId)
        {
            var idField = M2ePro.php.constant('\\Ess\\M2ePro\\Block\\Adminhtml\\Log\\Listing\\Other\\AbstractGrid::LISTING_ID_FIELD');

            var params = {};
            params[idField] = rowId;

            return M2ePro.url.get('walmart_log_listing_other/index', params);
        }

        // ---------------------------------------
    });
});