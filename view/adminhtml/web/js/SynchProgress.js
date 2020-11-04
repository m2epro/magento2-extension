define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(MessageObj) {

    window.SynchProgress = Class.create(Common, {

        stateExecuting : 'executing',
        stateInactive  : 'inactive',

        resultTypeError   : 'error',
        resultTypeWarning : 'warning',
        resultTypeSuccess : 'success',

        runningNow: false,
        result : null,

        // ---------------------------------------

        initialize: function (progressBarObj, wrapperObj)
        {
            this.progressBarObj = progressBarObj;
            this.wrapperObj = wrapperObj;
            this.loadingMask = $$('.loading-mask');
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
            this.loadingMask.invoke('setStyle', {visibility: 'hidden'});

            self.runningNow = true;
        },

        end: function ()
        {
            var self = this;

            self.progressBarObj.reset();
            self.progressBarObj.hide();

            self.wrapperObj.unlock();
            self.loadingMask.invoke('setStyle', {visibility: 'visible'});

            self.runningNow = false;
        },

        // ---------------------------------------

        runTask: function (taskTitle, taskUrl, taskCheckUrl, callBackWhenEnd)
        {
            taskTitle = taskTitle || '';
            taskUrl = taskUrl || '';
            callBackWhenEnd = callBackWhenEnd || '';

            if (taskUrl == '') {
                return;
            }

            var self = this;
            self.start(taskTitle, M2ePro.translator.translate('Preparing to start. Please wait ...'));

            new Ajax.Request(taskUrl, {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response && response['result']) {
                        self.result = response['result'];

                        if (response && response['result']) {
                            if (!(self.result == self.resultTypeWarning && response['result'] == self.resultTypeSuccess)) {
                                self.result = response['result'];
                            }
                        }
                    }
                }
            });

            setTimeout(function () {
                self.startGetExecutingInfo(taskCheckUrl, callBackWhenEnd);
            }, 2000);
        },

        startGetExecutingInfo: function(taskCheckUrl, callBackWhenEnd)
        {
            callBackWhenEnd = callBackWhenEnd || '';

            var self = this;
            new Ajax.Request(taskCheckUrl, {
                method:'get',
                asynchronous: true,
                onSuccess: function(transport) {

                    var data = transport.responseText.evalJSON(true);

                    if (data.ajaxExpired && response.ajaxRedirect) {

                        alert(M2ePro.translator.translate('Unauthorized! Please login again.'));
                        setLocation(response.ajaxRedirect);
                    }

                    if (data.mode == self.stateExecuting) {

                        self.progressBarObj.setTitle(data.title);
                        if (data.percents <= 0) {
                            self.progressBarObj.setPercents(0,0);
                        } else if (data.percents >= 100) {
                            self.progressBarObj.setPercents(100,0);
                        } else {
                            self.progressBarObj.setPercents(data.percents,1);
                        }
                        self.progressBarObj.setStatus(data.status);

                        self.wrapperObj.lock();
                        self.loadingMask.invoke('setStyle', {visibility: 'hidden'});

                        setTimeout(function() {
                            self.startGetExecutingInfo(taskCheckUrl, callBackWhenEnd);
                        },3000);

                    } else {

                        self.progressBarObj.setPercents(100,0);

                        setTimeout(function() {
                            eval(callBackWhenEnd);
                        },1500);
                    }
                }
            });
        },

        // ---------------------------------------

        printFinalMessage: function ()
        {
            var self = this;

            if (self.result == self.resultTypeError) {
                MessageObj.addError(str_replace(
                    '%url%',
                    M2ePro.url.get('logViewUrl'),
                    M2ePro.translator.translate('Marketplace synchronization was completed with errors. <a target="_blank" href="%url%">View Log</a> for the details.')
                ));
            } else if (self.result == self.resultTypeWarning) {
                MessageObj.addWarning(str_replace(
                    '%url%',
                    M2ePro.url.get('logViewUrl'),
                    M2ePro.translator.translate('Marketplace synchronization was completed with warnings. <a target="_blank" href="%url%">View Log</a> for the details.')
                ));
            } else {
                MessageObj.addSuccess(M2ePro.translator.translate('Marketplace synchronization was completed.'));
            }

            self.result = null;
        },

        // ---------------------------------------
    });
});

