define([
    'M2ePro/Common'
], function () {
    window.EbaySettingsMain = Class.create(Common, {

        initialize: function () {},

        initObservers: function () {
            $(
                'product_identifier_ean',
                'product_identifier_upc',
                'product_identifier_epid',
                'product_identifier_isbn'
            ).each(function(element) {
                element
                    .observe('change', EbaySettingsMainObj.product_identifiers_visibility_change)
                    .simulate('change');
            });
        },

        product_identifiers_visibility_change: function () {
            var hiddenElement = $(this.id + '_custom_attribute');
            if (hiddenElement) {
                EbaySettingsMainObj.updateHiddenValue(this, hiddenElement);
            }
        }
    })
})
