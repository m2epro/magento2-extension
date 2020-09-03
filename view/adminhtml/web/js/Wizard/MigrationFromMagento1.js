define(function () {

    window.MigrationFromMagento1 = Class.create(Common, {

        continueStep: function ()
        {
            if (WizardObj.steps.current.length) {
                this[WizardObj.steps.current + 'Step']();
            }
        },

        // Steps

        congratulationStep: function ()
        {
            this.initFormValidation();

            if (!this.isValidForm()) {
                return;
            }

            this.submitForm(M2ePro.url.get('migrationFromMagento1/finish'));
        },

        complete: function ()
        {
            if ($('edit_form') !== null) {
                this.initFormValidation();

                if (!this.isValidForm()) {
                    return;
                }

                this.submitForm(M2ePro.url.get('migrationFromMagento1/complete'));
            } else {
                setLocation(M2ePro.url.get('migrationFromMagento1/complete'));
            }
        }
    });
});
