define([
    'M2ePro/Common'
], function () {

    window.AmazonListingCreateSearch = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {

        },

        // ---------------------------------------

        general_id_mode_change: function () {
            var self = AmazonListingCreateSearchObj;

            $('general_id_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::GENERAL_ID_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('general_id_custom_attribute'));
            }
        },

        // ---------------------------------------

        worldwide_id_mode_change: function () {
            var self = AmazonListingCreateSearchObj;

            $('worldwide_id_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('worldwide_id_custom_attribute'));
            }
        },

        // ---------------------------------------
    });

});