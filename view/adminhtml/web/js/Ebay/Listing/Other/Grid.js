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
            var idField = M2ePro.php.constant('\\Ess\\M2ePro\\Block\\Adminhtml\\Log\\Listing\\Other\\AbstractGrid::LISTING_ID_FIELD');

            var params = {};
            params[idField] = rowId;

            return M2ePro.url.get('ebay_log_listing_other/index', params);
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