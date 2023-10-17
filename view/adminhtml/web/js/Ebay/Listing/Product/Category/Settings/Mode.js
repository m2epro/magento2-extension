define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function (jQuery, modal) {

    window.EbayListingProductCategorySettingsMode = Class.create({
        // ---------------------------------------

        initialize: function (lastModeValue) {
            var modeElement = $$('input[value="'+lastModeValue+'"]').shift();

            modeElement.checked = true;
            modeElement.simulate('change');

        },


        // ---------------------------------------
    });
});
