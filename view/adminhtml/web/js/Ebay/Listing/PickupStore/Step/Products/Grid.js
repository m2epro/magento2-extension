define([
    'M2ePro/Listing/View/Grid'
], function () {

    window.EbayListingPickupStoreStepProductsGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getCheckedValues: function()
        {
            return this.getGridMassActionObj().getCheckedValues();
        }

        // ---------------------------------------
    });
});