define([
    'M2ePro/SynchProgress'
], function() {

    window.WalmartMarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        runningNow: false,

        // ---------------------------------------

        startGetExecutingInfo: function (callBackWhenEnd)
        {
            callBackWhenEnd = callBackWhenEnd || '';

            var self = this;
            new Ajax.Request(M2ePro.url.get('walmart_marketplace/synchGetExecutingInfo'), {
                method:'get',
                asynchronous: true,
                onSuccess: function (transport) {
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
                        $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

                        setTimeout(function () {
                            self.startGetExecutingInfo(callBackWhenEnd);
                        },3000);

                    } else {

                        self.progressBarObj.setPercents(100,0);

                        // ---------------------------------------
                        setTimeout(function () {
                            eval(callBackWhenEnd);
                        },1500);
                        // ---------------------------------------
                    }
                }
            });
        }

        // ---------------------------------------
    });
});