define([
    'M2ePro/Common'
], function () {
    window.SettingsLogClearing = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-logs-clearing-interval', function(value, el) {

                if (jQuery.validator.methods['M2ePro-required-when-visible'](null, el)) {
                    return true;
                }

                if (isNaN(parseInt(value))) {
                    return false;
                }

                if (parseInt(value) < 14) {
                    return false;
                }

                if (parseInt(value) > 90) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('logs_clearing_keep_for_days_validation_message'));
        },

        // ---------------------------------------

        clearAllLog: function(log, el)
        {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        var form = el.up('form'),
                            formData = form.serialize(true);
                        SettingsObj.submitTab(M2ePro.url.get('formSubmit', {'task': 'clear_all','log': log}), formData);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
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