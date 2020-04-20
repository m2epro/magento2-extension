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
            $('return_accepted')
                .observe('change', this.acceptedChange)
                .simulate('change');

            $('return_international_accepted')
                .observe('change', this.internationalAcceptedChange)
                .simulate('change');
        },

        // ---------------------------------------

        acceptedChange: function()
        {
            var fields = [
                'return_option',
                'return_within',
                'return_shipping_cost'
            ];

            var internationalFieldset = $('return_policy_international_returns_fieldset'),
                additionalFieldset = $('return_policy_additional_fieldset');

            internationalFieldset && internationalFieldset.hide();
            additionalFieldset && additionalFieldset.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_ReturnPolicy::RETURNS_ACCEPTED')) {
                fields.forEach(function(value) {
                    var el = $$('.field-'+value).first();
                    el && el[$(value).childElements().length ? 'show' : 'hide']();
                });

                internationalFieldset && internationalFieldset.show();
                additionalFieldset && additionalFieldset.show();
            } else {
                fields.forEach(function(value) {
                    var el = $$('.field-'+value).first();
                    el && el.hide();
                });
            }

            internationalFieldset && internationalFieldset.simulate('change');
        },

        internationalAcceptedChange: function()
        {
            var fields = [
                'return_international_option',
                'return_international_within',
                'return_international_shipping_cost'
            ];

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_ReturnPolicy::RETURNS_ACCEPTED')) {
                fields.forEach(function(value) {
                    var el = $$('.field-'+value).first();
                    el && el[$(value).childElements().length ? 'show' : 'hide']();
                });

            } else {
                fields.forEach(function(value) {
                    var el = $$('.field-'+value).first();
                    el && el.hide();
                });
            }
        }

        // ---------------------------------------
    });
});