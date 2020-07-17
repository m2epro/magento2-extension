define([
    'M2ePro/SynchProgress'
], function () {
    MigrationFromMagento1MarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        end: function ($super)
        {
            $super();

            WizardObj.setStep(WizardObj.getNextStep(), function () {
                WizardObj.complete();
            });
        }

        // ---------------------------------------
    });
});
