define([
    'M2ePro/Plugin/Messages',
], function (MessageObj) {
    window.WizardAmazonMigrationToProductTypes = Class.create(Common, {
        proceed: function ()
        {
            new Ajax.Request(M2ePro.url.get('wizard_amazonMigrationToProductTypes/proceed'), {
                method: 'post',
                asynchronous: true,
                parameters: [],
                onSuccess: function(transport) {
                    MessageObj.clear();
                    var response = transport.responseText.evalJSON();

                    if (response && !response['success'] && response['message']) {
                        MessageObj.addError(response['message']);
                        return CommonObj.scrollPageToTop();
                    }

                    if (!response['url']) {
                        MessageObj.addError(
                            M2ePro.translator.translate('An error during of marketplace synchronization.')
                        );
                        return CommonObj.scrollPageToTop();
                    }

                    return setLocation(response['url']);
                }
            });
        }
    });
});
