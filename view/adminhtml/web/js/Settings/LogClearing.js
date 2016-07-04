define([
    'M2ePro/Common'
], function () {
    window.SettingsLogClearing = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-logs-clearing-interval', function(value, el) {

                if (isNaN(parseInt(value))) {
                    return false;
                }

                if (parseInt(value) < 14) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Please enter a valid value greater than 14 days.'));
        },

        // ---------------------------------------

        clearAllLog: function(log, el)
        {
            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            var form = el.up('form'),
                formData = form.serialize(true);
            SettingsObj.submitTab(M2ePro.url.get('formSubmit', {'task': 'clear_all','log': log}), formData);
        },

        // ---------------------------------------

        changeModeLog: function(log)
        {
            var value = $(log+'_log_mode').value,
                button = $$('.clear_all_'+log).shift();

            if (value == '1') {
                $(log+'_log_days_container').style.display = '';
                button && button.show();
            } else {
                $(log+'_log_days_container').style.display = 'none';
                button && button.hide();
            }
        }

        // ---------------------------------------
    });
});