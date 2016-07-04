define([
    'M2ePro/SynchProgress'
], function () {
    MigrationFromMagento1MarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        start: function (title, status) {
            title = title || '';
            status = status || '';

            var self = this;

            self.progressBarObj.reset();

            if (title != '') {
                self.progressBarObj.setTitle(title);
            }
            if (status != '') {
                self.progressBarObj.setStatus(status);
            }

            self.progressBarObj.show();
            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        },

        end: function () {
            var self = this;

            self.progressBarObj.reset();
            self.progressBarObj.hide();

            $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

            WizardObj.setStep(WizardObj.getNextStep(), function (){
                WizardObj.complete();
            });
        },

        startGetExecutingInfo: function (callBackWhenEnd) {
            callBackWhenEnd = callBackWhenEnd || '';

            var self = this;
            new Ajax.Request(M2ePro.url.get('general/synchGetExecutingInfo'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function (transport) {

                    var data = transport.responseText.evalJSON(true);

                    if (data.mode == self.stateExecuting) {

                        self.progressBarObj.setTitle(data.title);
                        if (data.percents <= 0) {
                            self.progressBarObj.setPercents(0, 0);
                        } else if (data.percents >= 100) {
                            self.progressBarObj.setPercents(100, 0);
                        } else {
                            self.progressBarObj.setPercents(data.percents, 1);
                        }
                        self.progressBarObj.setStatus(data.status);

                        setTimeout(function () {
                            self.startGetExecutingInfo(callBackWhenEnd);
                        }, 3000);

                    } else {

                        self.progressBarObj.setPercents(100, 0);

                        // ---------------------------------------
                        setTimeout(function () {

                            if (callBackWhenEnd != '') {
                                eval(callBackWhenEnd);
                            } else {
                                self.end();
                            }

                        }, 1500);
                        // ---------------------------------------
                    }
                }
            });
        }

        // ---------------------------------------
    });
});