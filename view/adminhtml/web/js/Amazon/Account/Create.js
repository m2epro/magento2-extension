define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(MessageObj) {
    window.AmazonAccountCreate = Class.create(Common, {
        initialize: function() {
            this.setValidationCheckRepetitionValue('M2ePro-account-title',
                M2ePro.translator.translate('The specified Title is already used for other Account. Account Title must be unique.'),
                'Account', 'title', 'id',
                '',
                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'));
        },

        continueClick: function () {
            var self = this,
                url = M2ePro.url.urls.formSubmit;

            MessageObj.clear();

            if (!self.isValidForm()) {
                return;
            }

            new Ajax.Request(url, {
                method: 'post',
                parameters: Form.serialize($('edit_form')),
                onSuccess: function(transport) {
                    transport = transport.responseText.evalJSON();

                    if (transport.success) {
                        window.location = transport.url;
                    } else {
                        MessageObj.addError(transport.message);
                    }
                }
            });
        }
    });
});
