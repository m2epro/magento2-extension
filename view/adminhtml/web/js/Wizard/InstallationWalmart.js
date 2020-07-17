define([
    'M2ePro/Plugin/Messages',
    'Magento_Ui/js/modal/alert'
], function (MessagesObj, alert) {

    window.WizardInstallationWalmart = Class.create(Common, {

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
            WizardObj.registrationStep(M2ePro.url.get('wizard_installationWalmart/createLicense'));
        },

        accountStep: function ()
        {
            this.initFormValidation();

            if (!this.isValidForm()) {
                return false;
            }

            new Ajax.Request(M2ePro.url.get('wizard_installationWalmart/accountContinue'), {
                method       : 'post',
                parameters   : $('edit_form').serialize(true),
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    MessagesObj.clear();

                    if (response && response['message']) {
                        MessagesObj.addError(response['message']);
                        return CommonObj.scrollPageToTop();
                    }

                    window.location.reload();
                }
            });
        },

        settingsStep: function ()
        {
            this.initFormValidation();

            if (!this.isValidForm()) {
                return false;
            }

            this.submitForm(M2ePro.url.get('wizard_installationWalmart/settingsContinue'));
        },

        listingTutorialStep: function ()
        {
            setLocation(M2ePro.url.get('wizard_installationWalmart/listingTutorialContinue'));
        },

        // ---------------------------------------

        changeMarketplace: function(marketplaceId)
        {
            $$('.marketplace-required-field').each(function(obj) {
                obj.hide();
            });

            if (marketplaceId === '') {
                return;
            }

            $$('.marketplace-required-field-id' + marketplaceId, '.marketplace-required-field-id-not-null').each(function(obj) {
                obj.show();
            });
        }

        // ---------------------------------------
    });

});
