define([
    'M2ePro/SynchProgress'
], function () {
    AmazonListingCreateGeneralMarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        runningNow: false,

        // ---------------------------------------

        start: function ($super, title, status) {
            $super(title, status);
            this.runningNow = true;
        },

        end: function ($super) {
            $super();
            this.runningNow = false;
            this.saveClick(M2ePro.url.get('amazon_listing_create/index'), true)
        },

        runTask: function (title, url, callBackWhenEnd) {
            title = title || '';
            url = url || '';
            callBackWhenEnd = callBackWhenEnd || '';

            if (url == '') {
                return;
            }

            var self = this;
            new Ajax.Request(M2ePro.url.get('general/synchCheckState'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function (transport) {

                    if (transport.responseText == self.stateExecuting) {

                        self.start(
                            M2ePro.translator.translate('Another Synchronization Is Already Running.'),
                            M2ePro.translator.translate('Getting information. Please wait ...')
                        );

                        setTimeout(function () {
                            self.startGetExecutingInfo('self.runTask(\'' + title + '\',\'' + url + '\',"' + callBackWhenEnd + '");');
                        }, 2000);

                    } else {

                        self.start(title, M2ePro.translator.translate('Preparing to start. Please wait ...'));

                        new Ajax.Request(url, {
                            method: 'get', asynchronous: true
                        });

                        setTimeout(function () {
                            self.startGetExecutingInfo(callBackWhenEnd);
                        }, 2000);
                    }
                }
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

                        self.wrapperObj.lock();
                        $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

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