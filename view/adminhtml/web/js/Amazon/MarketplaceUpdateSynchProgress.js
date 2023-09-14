define([
    'M2ePro/Plugin/Messages',
    'M2ePro/SynchProgress'
], function (MessageObj) {
    MarketplaceUpdateSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        printFinalMessage: function ()
        {
            var self = this;

            if (self.result == self.resultTypeError) {
                MessageObj.addError(str_replace(
                        '%url%',
                        M2ePro.url.get('logViewUrl'),
                        M2ePro.translator.translate('Amazon Data Update was completed with errors. <a target="_blank" href="%url%">View Log</a> for the details.')
                ));
            } else if (self.result == self.resultTypeWarning) {
                MessageObj.addWarning(str_replace(
                        '%url%',
                        M2ePro.url.get('logViewUrl'),
                        M2ePro.translator.translate('Amazon Data Update was completed with warnings. <a target="_blank" href="%url%">View Log</a> for the details.')
                ));
            } else {
                MessageObj.addSuccess(M2ePro.translator.translate('Amazon Data Update was completed.'));
            }

            self.result = null;
        },

        // ---------------------------------------
    });
});
