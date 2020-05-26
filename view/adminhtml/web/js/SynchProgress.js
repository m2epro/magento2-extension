define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(MessageObj) {

    window.SynchProgress = Class.create(Common, {

        // ---------------------------------------

        initialize: function (progressBarObj, wrapperObj)
        {
            this.stateExecuting = 'executing';
            this.stateInactive = 'inactive';

            this.resultTypeError = 'error';
            this.resultTypeWarning = 'warning';
            this.resultTypeSuccess = 'success';
            this.result = null;

            this.progressBarObj = progressBarObj;
            this.wrapperObj = wrapperObj;
        },

        // ---------------------------------------

        start: function (title, status)
        {
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

            self.wrapperObj.lock();
            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        },

        end: function ()
        {
            var self = this;

            self.progressBarObj.reset();
            self.progressBarObj.hide();

            self.wrapperObj.unlock();
            $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});
        },

        // ---------------------------------------

        runTask: function (title, url, components, callBackWhenEnd)
        {
            title = title || '';
            url = url || '';
            components = components || '';
            callBackWhenEnd = callBackWhenEnd || '';

            if (url == '') {
                return;
            }

            var self = this;
            self.start(title, M2ePro.translator.translate('Preparing to start. Please wait ...'));
            new Ajax.Request(url, {
                parameters: {components: components},
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response && response['result']) {
                        self.result = response['result'];
                    }
                }
            });

            setTimeout(function () {
                self.startGetExecutingInfo(callBackWhenEnd);
            }, 2000);
        },

        // ---------------------------------------

        printFinalMessage: function (resultType)
        {
            var self = this;
            var finalResult = self.result !== null ? self.result : resultType;

            if (finalResult == self.resultTypeError) {
                MessageObj.addErrorMessage(str_replace(
                    '%url%',
                    M2ePro.url.get('logViewUrl'),
                    M2ePro.translator.translate('Marketplace synchronization was completed with errors. <a target="_blank" href="%url%">View Log</a> for the details.')
                ));
            } else if (finalResult == self.resultTypeWarning) {
                MessageObj.addWarningMessage(str_replace(
                    '%url%',
                    M2ePro.url.get('logViewUrl'),
                    M2ePro.translator.translate('Marketplace synchronization was completed with warnings. <a target="_blank" href="%url%">View Log</a> for the details.')
                ));
            } else {
                MessageObj.addSuccessMessage(M2ePro.translator.translate('Marketplace synchronization was completed successfully.'));
            }
        },

        // ---------------------------------------
    });
});