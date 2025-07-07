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
            WizardObj.registrationStep(M2ePro.url.get('wizard_registration/createLicense'));
        },

        accountStep: function ()
        {
            this.initFormValidation();

            if (!this.isValidForm()) {
                return false;
            }

            let params = $('edit_form').serialize(true);
            params.marketplace_id = jQuery('#marketplace_id').val()

            new Ajax.Request(M2ePro.url.get('wizard_installationWalmart/accountContinue'), {
                method       : 'post',
                parameters   : params,
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

        changeMarketplace: function (marketplaceId) {
            $('edit_form').hide();
            $('account_us_connect').hide();
            if (marketplaceId == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::MARKETPLACE_CA')) {
                $('edit_form').show();
                jQuery('#continue').removeAttr('disabled')
            }

            if (marketplaceId == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::MARKETPLACE_US')) {
                $('account_us_connect').show();
                jQuery('#continue').prop('disabled', true);
            }
        }

        // ---------------------------------------
    });

});
