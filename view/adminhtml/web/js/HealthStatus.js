define([
    'M2ePro/Plugin/Messages'
], function (MessagesObj) {
    window.HealthStatus = Class.create();
    HealthStatus.prototype = {

        notificationModeElement: null,
        notificationModeValue: null,

        notificationEmailElement: null,
        notificationEmailValue: null,

        notificationLevelElement: null,
        notificationLevelValue: null,

        // ---------------------------------------

        initialize: function()
        {
            var self = this;

            //-- Notification Mode
            self.notificationModeElement = $('notification_mode');
            self.notificationModeValue = self.notificationModeElement.value;

            self.notificationEmailElement = $('notification_email');
            self.notificationEmailValue = self.notificationEmailElement.value;

            self.notificationModeElement.observe('change', self.notificationModeChanged.bind(self));
            self.notificationEmailElement.observe('input', self.notificationModeChanged.bind(self));

            self.notificationModeElement.simulate('change');
            //--

            //-- Notification Level
            self.notificationLevelElement = $('notification_level');
            self.notificationLevelValue = self.notificationLevelElement.value;

            self.notificationLevelElement.observe('change', function() {
                var currentValue = self.notificationLevelElement.value;

                if (currentValue != self.notificationLevelValue) {
                    $('save_notification_level').show();
                } else {
                    $('save_notification_level').hide();
                }
            });
            //--
        },

        notificationModeChanged: function()
        {
            var self = this;

            var currentModeValue  = self.notificationModeElement.value,
                currentEmailValue = self.notificationEmailElement.value;

            if (currentModeValue != self.notificationModeValue || currentEmailValue != self.notificationEmailValue) {
                $('save_notification_mode').show();
            } else {
                $('save_notification_mode').hide();
            }

            if (currentModeValue == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\HealthStatus\\Notification\\Settings::MODE_EMAIL')) {
                $('notification_email_value_container').show();
            } else {
                $('notification_email_value_container').hide();
            }
        },

        // ---------------------------------------

        saveNotificationMode: function()
        {
            var self     = this,
                settings = [];

            var currentModeValue = self.notificationModeElement.value;
            if (currentModeValue == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\HealthStatus\\Notification\\Settings::MODE_EMAIL') &&
               (!Validation.validate(self.notificationEmailElement) || self.notificationEmailElement.value == '')) {

                return;
            }

            settings.push({
                'group': '/health_status/notification/',
                'key':   'mode',
                'value': self.notificationModeElement.value
            });

            settings.push({
                'group': '/health_status/notification/',
                'key':   'email',
                'value': self.notificationEmailElement.value
            });

            self.saveSettings(
                settings,
                function(response) {
                    $('save_notification_mode').hide();
                    self.notificationModeValue  = self.notificationModeElement.value;
                    self.notificationEmailValue = self.notificationEmailElement.value;
                }
            );
        },

        saveNotificationLevel: function()
        {
            var self     = this,
                settings = [];

            settings.push({
                'group': '/health_status/notification/',
                'key':   'level',
                'value': self.notificationLevelElement.value
            });

            self.saveSettings(
                settings,
                function(response) {
                    $('save_notification_level').hide();
                    self.notificationLevelValue = self.notificationLevelElement.value;
                }
            );
        },

        // ---------------------------------------

        saveSettings: function (settings, successCallback)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('healthStatus/save'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    settings: Object.toJSON(settings)
                },
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    MessagesObj.clear();
                    if (!result.isJSON()) {
                        MessagesObj.addErrorMessage(result);
                    }

                    result = JSON.parse(result);

                    if (result.success) {
                        MessagesObj.addSuccessMessage(M2ePro.translator.translate('Settings successfully saved'));
                        successCallback.call(self, result);
                    } else {
                        MessagesObj.addErrorMessage(M2ePro.translator.translate('Error'));
                    }
                }
            });
        }

        // ---------------------------------------
    }
});