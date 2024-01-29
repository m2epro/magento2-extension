define([
    'M2ePro/Listing/Product/AdvancedFilter',
], function () {
    window.ListingProductAdvancedFilterSelect = Class.create({

        init: function () {
            this.advancedFilter = new ListingProductAdvancedFilterObj();
        },

        initEvents: function () {
            const self = this;
            jQuery('#advanced_filter_list').change(function (e) {
               self.advancedFilter.submitForm()
            });
        },

        createNewFilter: function () {
            this.advancedFilter.addCreateNewFilterInput();
            this.advancedFilter.submitForm();
        },

        updateFilter: function () {
            this.advancedFilter.addUpdateFilterInput();
            this.advancedFilter.submitForm();
        },
    });
});
