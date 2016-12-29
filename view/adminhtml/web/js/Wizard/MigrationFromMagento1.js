define(function () {

    window.MigrationFromMagento1 = Class.create(Common, {

        continueStep: function ()
        {
            if (WizardObj.steps.current.length) {
                this[WizardObj.steps.current + 'Step']();
            }
        },

        // Steps
        // ---------------------------------------

        synchronizationStep: function ()
        {
            jQuery('#continue').prop('disabled', true);

            var nextMarketplace = jQuery('.marketplace-id:not([synchronized]):first');

            if (!nextMarketplace.length) {
                MarketplaceSynchProgressObj.end();
                return;
            }

            nextMarketplace.attr('synchronized', 1);
            this.synchronizeMarketplace(nextMarketplace.attr('component'), nextMarketplace.val());
        },

        congratulationStep: function ()
        {
            WizardObj.setStatus(M2ePro.php.constant('Ess_M2ePro_Helper_Module_Wizard::STATUS_COMPLETED'), function() {
                setLocation(M2ePro.url.get('complete'));
            });
        },

        // ---------------------------------------

        synchronizeMarketplace: function(component, marketplaceId)
        {
            var title = component + ' ' + $('marketplace_' + marketplaceId).innerHTML;

            MarketplaceSynchProgressObj.runTask(
                title,
                M2ePro.url.get('wizard_migrationFromMagento1/runSynchNow') + 'marketplace_id/' + marketplaceId,
                '', 'MigrationFromMagento1Obj.synchronizationStep()'
            );
        }
    });

});