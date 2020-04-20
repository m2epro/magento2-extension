define([
    'M2ePro/Plugin/Messages',
    'Magento_Ui/js/modal/alert'
], function (MessagesObj, alert) {

    window.WizardInstallationWalmart = Class.create(Common, {

        WizardWalmartMarketplaceSynchProgressObj: null,

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

            if (WizardWalmartMarketplaceSynchProgressObj.runningNow) {
                alert({
                    content: M2ePro.translator.translate('Please wait while Synchronization is finished.')
                });
                return;
            }

            this.synchronizeMarketplace($('marketplace_id').value);
        },

        accountStepContinue: function ()
        {
            new Ajax.Request(M2ePro.url.get('wizard_installationWalmart/accountContinue'), {
                method       : 'post',
                parameters   : $('edit_form').serialize(true),
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    MessagesObj.clear();

                    if (response && response['message']) {
                        MessagesObj.addErrorMessage(response['message']);
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

            $('consumer_id').removeClassName('M2ePro-validate-consumer-id');
            $$('label[for="consumer_id"] > span').first().innerHTML = M2ePro.translator.translate('Consumer ID');
            if (marketplaceId == M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Walmart::MARKETPLACE_US')) {
                $('consumer_id').addClassName('M2ePro-validate-consumer-id');
                $$('label[for="consumer_id"] > span').first().innerHTML = M2ePro.translator.translate('Consumer ID / Partner ID');
            }

            $$('.marketplace-required-field-id' + marketplaceId, '.marketplace-required-field-id-not-null').each(function(obj) {
                obj.show();
            });
        },

        // ---------------------------------------

        synchronizeMarketplace: function (marketplaceId)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('wizard_installationWalmart/enableMarketplace'), {
                method: 'get',
                parameters: { marketplace_id: marketplaceId },
                onSuccess: function(transport) {

                    var result = transport.responseText.evalJSON();
                    if (!result.success) {
                        return;
                    }

                    var  title = 'Walmart ' + $$('#marketplace_id option').find(function(el){return !!el.selected}).innerHTML;

                    WizardWalmartMarketplaceSynchProgressObj.runTask(
                        title,
                        M2ePro.url.get('walmart_marketplace/runSynchNow', {marketplace_id: marketplaceId}),
                        function () {
                            WizardWalmartMarketplaceSynchProgressObj.end();
                            self.accountStepContinue();
                        }
                    );
                }
            });
        }

        // ---------------------------------------
    });

});