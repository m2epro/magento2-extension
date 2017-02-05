define([
    'M2ePro/Plugin/Messages'
], function (MessagesObj) {

    window.WizardInstallationAmazon = Class.create(Common, {

        // ---------------------------------------

        continueStep: function ()
        {
            if (WizardObj.steps.current.length) {
                this[WizardObj.steps.current + 'Step']();
            }
        },

        // Steps
        // ---------------------------------------

        registrationStep: function ()
        {
            WizardObj.registrationStep(M2ePro.url.get('wizard_installationAmazon/createLicense'));
        },

        accountStep: function ()
        {
            if (!$('marketplace_id').value) {
                this.alert(M2ePro.translator.translate('Please select Marketplace first.'));
                return;
            }

            new Ajax.Request(M2ePro.url.get('wizard_installationAmazon/beforeToken'), {
                method       : 'post',
                asynchronous : true,
                parameters   : $('edit_form').serialize(),
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response && response['message']) {
                        MessagesObj.addErrorMessage(response['message']);
                        return CommonObj.scrollPageToTop();
                    }

                    if (!response['url']) {
                        MessagesObj.addErrorMessage(M2ePro.translator.translate('An error during of account creation.'));
                        return CommonObj.scrollPageToTop();
                    }

                    return setLocation(response['url']);
                }
            });
        },

        listingTutorialStep: function ()
        {
            WizardObj.setStep(WizardObj.getNextStep(), setLocation.bind(window, location.href))
        }
    });

});