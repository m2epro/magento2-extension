define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (MessageObj) {

    window.AmazonMarketplacesBuildUpdate = Class.create(Common, {

        // ---------------------------------------

        initialize: function (synchProgressObj, storedStatuses)
        {
            this.synchProgressObj = synchProgressObj;

            this.marketplacesForUpdate = [];
            this.marketplacesForUpdateCurrentIndex = 0;
            this.storedStatuses = storedStatuses || [];
        },

        // ---------------------------------------

        getStoredStatuses: function ()
        {
            return this.storedStatuses;
        },

        // ---------------------------------------

        updateAction: function ()
        {
            MessageObj.clear();
            CommonObj.scrollPageToTop();
            this.runAllSynchronization();
        },

        // ---------------------------------------

        runAllSynchronization: function (statuses)
        {
            var statusesForSynch = statuses || this.getStoredStatuses();

            this.marketplacesForUpdate = [];
            this.marketplacesForUpdateCurrentIndex = 0;

            for (var i = 0; i < statusesForSynch.length; i++) {

                var marketplaceId = statusesForSynch[i].marketplace_id;

                if (!marketplaceId) {
                    continue;
                }
                    this.marketplacesForUpdate[this.marketplacesForUpdate.length] = marketplaceId;
            }

            if (this.marketplacesForUpdate.length == 0) {
                return false;
            }

            this.marketplacesForUpdateCurrentIndex = 0;

            this.runNextMarketplaceNow();
            return true;
        },

        // ---------------------------------------

        runNextMarketplaceNow: function ()
        {
            var self = this;

            if (self.synchProgressObj.result == self.synchProgressObj.resultTypeError) {
                self.completeWithError();
                return;
            }

            if (self.marketplacesForUpdateCurrentIndex >= self.marketplacesForUpdate.length) {

                self.marketplacesForUpdate = [];
                self.marketplacesForUpdateCurrentIndex = 0;
                self.marketplacesUpdateFinished = true;

                self.synchProgressObj.end();
                self.synchProgressObj.printFinalMessage();

                return;
            }

            var marketplaceId = self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex];
            self.marketplacesForUpdateCurrentIndex++;
            var currentMarketplace = self.storedStatuses[self.marketplacesForUpdateCurrentIndex - 1];

            var titleProgressBar = currentMarketplace.title;
            var componentTitle = 'Amazon';
            var component      = 'amazon';

            titleProgressBar = componentTitle + ' ' + titleProgressBar;

            self.runNextMarketplaceTask(titleProgressBar, marketplaceId, component);
            return true;
        },

        runNextMarketplaceTask: function(titleProgressBar, marketplaceId, component)
        {
            this.synchProgressObj.runTask(
                titleProgressBar,
                M2ePro.url.get('runSynchNow', {'marketplace_id': marketplaceId}),
                M2ePro.url.get('amazon_marketplace/synchGetExecutingInfo'),
                'MarketplaceObj.runNextMarketplaceNow();'
            );
        },

        // ---------------------------------------

        completeWithError: function()
        {
            var self = this;

            self.marketplacesForUpdate = [];
            self.marketplacesForUpdateCurrentIndex = 0;
            self.marketplacesUpdateFinished = true;

            self.synchProgressObj.end();
            self.synchProgressObj.printFinalMessage();
        }

        // ---------------------------------------
    });
});
