define([
    'M2ePro/SynchProgress'
], function () {
    window.WizardMigrationToInnodbMarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        end: function ($super)
        {
            $super();

            WizardObj.setStatus(M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Module\\Wizard::STATUS_COMPLETED'));
            WizardObj.complete();
        }

        // ---------------------------------------

    });
});
