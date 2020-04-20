define([
    'M2ePro/Plugin/Messages',
], function (MessageObj) {

    window.WizardInstallationEbay = Class.create(Common, {

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
            WizardObj.registrationStep(M2ePro.url.get('wizard_installationEbay/createLicense'));
        },

        accountStep: function ()
        {
            if (!this.isValidForm()) {
                return false;
            }

            new Ajax.Request(M2ePro.url.get('wizard_installationEbay/beforeToken'), {
                method       : 'post',
                asynchronous : true,
                parameters   : $('edit_form').serialize(),
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response && response['message']) {
                        MessageObj.addErrorMessage(response['message']);
                        return CommonObj.scrollPageToTop();
                    }

                    if (!response['url']) {
                        MessageObj.addErrorMessage(M2ePro.translator.translate('An error during of account creation.'));
                        return CommonObj.scrollPageToTop();
                    }

                    return setLocation(response['url']);
                }
            });
        },

        listingTutorialStep: function ()
        {
            WizardObj.setStep(WizardObj.getNextStep(), function (){
                WizardObj.complete();
            });
        }

        // ---------------------------------------
    });
});