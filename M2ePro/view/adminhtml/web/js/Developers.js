define([
    'M2ePro/Plugin/Messages'
], function (MessagesObj) {
    window.Developers = Class.create();
    Developers.prototype = {

        inspectionModeElement: null,
        inspectionModeValue: null,

        // ---------------------------------------

        initialize: function()
        {
            var self = this;

            self.inspectionModeElement = $('inspector_mode');
            self.inspectionModeValue = self.inspectionModeElement.value;

            self.inspectionModeElement.observe('change', function() {
                var currentValue = self.inspectionModeElement.value;

                if (currentValue != self.inspectionModeValue) {
                    $('save_inspector_mode').show();
                } else {
                    $('save_inspector_mode').hide();
                }
            });
        },

        // ---------------------------------------

        saveDirectDatabaseChanges: function ()
        {
            var self  = this;

            new Ajax.Request(M2ePro.url.get('developers/save'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    inspector_mode: $('inspector_mode').value
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
                        $('save_inspector_mode').hide();
                        self.inspectionModeValue = self.inspectionModeElement.value;
                    } else {
                        MessagesObj.addErrorMessage(M2ePro.translator.translate('Error'));
                    }
                }
            });
        }

        // ---------------------------------------
    }
});