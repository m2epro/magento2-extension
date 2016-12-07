define([
    'M2ePro/Listing/View/Grid'
], function () {

    window.EbayListingPickupStoreStepStoresGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getCheckedValues: function()
        {
            return this.getGridMassActionObj().getCheckedValues();
        }

        // ---------------------------------------
    });
});