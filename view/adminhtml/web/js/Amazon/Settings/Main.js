define([
    'M2ePro/Common'
], function () {
    window.AmazonSettingsMain = Class.create(Common, {

        initialize: function () {},

        initObservers: function () {
            $('general_id').observe('change', function () {
                AmazonSettingsMainObj.updateHiddenValue(this, $('general_id_custom_attribute'));
            }).simulate('change');

            $('worldwide_id').observe('change', function () {
                AmazonSettingsMainObj.updateHiddenValue(this, $('worldwide_id_custom_attribute'));
            }).simulate('change')
        }
    })
})
