define([
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
], function (alert, confirm) {

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
                alert({
                    content: M2ePro.translator.translate('Please select Marketplace first.')
                });
                return;
            }

            new Ajax.Request(M2ePro.url.get('wizard_installationAmazon/beforeToken'), {
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
                        MessageObj.addErrorMessage(M2ePro.translator.translate('An error during of license creation occurred.'));
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