define([
    'knockout',
    'jquery',
    'M2ePro/Common'
], function (ko, jquery) {
    window.EbayTemplateReturnPolicy = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {},

        // ---------------------------------------

        initObservers: function() {
            jquery('#return_accepted').off('change');
            jquery('#return_accepted').on('change', this.acceptedChange).change();
        },

        // ---------------------------------------

        acceptedChange: function()
        {
            var columns = [
                'return_option',
                'return_within',
                'return_shipping_cost',
                'return_restocking_fee'
            ];

            if (this.value == 'ReturnsAccepted') {
                columns.forEach(function(value) {
                    var el = $$('.field-'+value).first();
                    el && el[$(value).options.length ? 'show' : 'hide']();
                });
                $$('.field-return_description').invoke('show');

                if ($$('.field-return_holiday_mode').length) {
                    $$('.field-return_holiday_mode').invoke('show');
                }
            } else {
                columns.forEach(function(value) {
                    var el = $$('.field-'+value).first();
                    el && el.hide();
                });

                $$('.field-return_description').invoke('hide');
                $$('.field-return_holiday').invoke('hide');

                //$('return_holiday_mode').selectedIndex = 0;
                //jquery('#return_holiday_mode').trigger('change');
                $$('.field-return_holiday_mode').invoke('hide');
            }
        }

        // ---------------------------------------
    });
});