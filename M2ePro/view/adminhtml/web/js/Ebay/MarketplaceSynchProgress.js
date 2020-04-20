define([
    'M2ePro/Plugin/Messages',
    'M2ePro/SynchProgress'
], function(MessageObj) {

    window.EbayMarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        printFinalMessage: function (resultType) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_marketplace/isExistDeletedCategories'), {
                method: 'post',
                asynchronous: true,
                onSuccess: function (transport) {

                    if (transport.responseText == 1) {
                        MessageObj.addWarningMessage(str_replace(
                            '%url%',
                            M2ePro.url.get('ebay_category/index', {filter: base64_encode('state=0')}),
                            M2ePro.translator.translate('Some eBay Categories were deleted from eBay. Click <a target="_blank" href="%url%">here</a> to check.')
                        ));
                    }

                    if (resultType == self.resultTypeError) {
                        MessageObj.addErrorMessage(str_replace(
                            '%url%',
                            M2ePro.url.get('logViewUrl'),
                            M2ePro.translator.translate('Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.')
                        ));
                    } else if (resultType == self.resultTypeWarning) {
                        MessageObj.addWarningMessage(str_replace(
                            '%url%',
                            M2ePro.url.get('logViewUrl'),
                            M2ePro.translator.translate('Synchronization ended with warnings. <a target="_blank" href="%url%">View Log</a> for details.')
                        ));
                    } else {
                        MessageObj.addSuccessMessage(M2ePro.translator.translate('Synchronization has successfully ended.'));
                    }
                }
            });
        }

        // ---------------------------------------
    });
});