define([
    'M2ePro/Plugin/Messages',
    'M2ePro/SynchProgress'
], function (MessageObj) {
    WalmartMarketplaceWithProductTypeSyncProgress = Class.create(SynchProgress, {
        printFinalMessage: function ()
        {
            if (this.result == this.resultTypeError) {
                MessageObj.addError(M2ePro.translator.translate('marketplace_sync_error_message'));
            } else if (this.result == this.resultTypeWarning) {
                MessageObj.addWarning(M2ePro.translator.translate('marketplace_sync_warning_message'));
            } else {
                MessageObj.addSuccess(M2ePro.translator.translate('marketplace_sync_success_message'));
            }

            this.result = null;
        },
    });
});
