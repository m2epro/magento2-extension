define([
    'M2ePro/Plugin/Messages'
], function(MessageObj) {

    window.Wizard = Class.create(Common, {

        // ---------------------------------------

        initialize: function(currentStatus, currentStep, hiddenSteps) {
            this.currentStatus = currentStatus;

            this.steps = {};
            this.steps.current = currentStep;
            this.steps.hidden = hiddenSteps || [];
            this.steps.nicks = [];
        },

        // ----------------)-----------------------

        skip: function(url) {
            setLocation(url);
        },

        complete: function() {
            window.location.reload();
        },

        // ---------------------------------------

        setStatus: function(status, callback) {
            new Ajax.Request(M2ePro.url.get('setStatus'), {
                method: 'get',
                parameters: {
                    status: status
                },
                asynchronous: false,
                onSuccess: (function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response.type == 'error') {
                        CommonObj.scrollPageToTop();
                        return MessageObj.addError(response.message);
                    }

                    this.currentStatus = status;

                    if (typeof callback == 'function') {
                        callback();
                    }

                }).bind(this)
            });
        },

        setStep: function(step, callback) {
            new Ajax.Request(M2ePro.url.get('setStep'), {
                method: 'get',
                parameters: {
                    step: step
                },
                asynchronous: true,
                onSuccess: (function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response.type == 'error') {
                        CommonObj.scrollPageToTop();
                        return MessageObj.addError(response.message);
                    }

                    this.steps.current = step;

                    if (typeof callback == 'function') {
                        callback();
                    }

                }).bind(this)
            });
        },

        // ---------------------------------------

        getNextStep: function() {
            var stepIndex = this.steps.all.indexOf(this.steps.current);

            if (stepIndex == -1) {
                return null;
            }

            var nextStepNick = this.steps.all[stepIndex + 1];

            if (typeof nextStepNick == 'undefined') {
                return null;
            }

            return nextStepNick;
        },

        // ---------------------------------------

        disableContinueButton: function() {
            jQuery('#continue').prop('disabled', true);
        },

        // Steps
        // ---------------------------------------

        registrationStep: function(url) {
            this.initFormValidation();

            if (!this.isValidForm()) {
                return false;
            }

            MessageObj.clear();

            new Ajax.Request(url, {
                method: 'post',
                parameters: $('edit_form').serialize(),
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (!response.status) {
                        MessageObj.addError(response.message);
                        return CommonObj.scrollPageToTop();
                    }

                    WizardObj.complete();
                }
            });
        }

    });

});
