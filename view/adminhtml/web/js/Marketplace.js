define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (MessageObj) {

    window.Marketplace = Class.create(Common, {

        // ---------------------------------------

        initialize: function (synchProgressObj, storedStatuses)
        {
            this.synchProgressObj = synchProgressObj;

            this.marketplacesForUpdate = new Array();
            this.marketplacesForUpdateCurrentIndex = 0;
            this.storedStatuses = storedStatuses || [];
        },

        // ---------------------------------------

        getStoredStatuses: function ()
        {
            return this.storedStatuses;
        },

        getStoredStatusByMarketplaceId: function (marketplaceId)
        {
            if (marketplaceId == '') {
                return;
            }

            for (var i = 0; i < this.storedStatuses.length; i++) {
                if (this.storedStatuses[i].marketplace_id == marketplaceId) {
                    return this.storedStatuses[i].status;
                }
            }
        },

        getCurrentStatuses: function ()
        {
            var allStatuses = [];
            $$('select.marketplace_status_select').each(function (element) {
                var elementId = element.getAttribute('marketplace_id');
                allStatuses.push({
                    marketplace_id: elementId,
                    status: parseInt(element.value)
                });
            });

            return allStatuses;
        },

        // ---------------------------------------

        saveAction: function ()
        {
            MessageObj.clear();
            CommonObj.scrollPageToTop();

            var changedStatuses = this.runEnabledSynchronization();
            this.saveSettings();

            for (var i = 0; i < changedStatuses.length; i++) {
                $('changed_' + changedStatuses[i].marketplace_id).style.display = 'none';
                this.changeStatus($('status_' + changedStatuses[i].marketplace_id));
            }

            MessageObj.addSuccess(M2ePro.translator.translate('Settings have been saved.'));
        },

        updateAction: function ()
        {
            MessageObj.clear();
            CommonObj.scrollPageToTop();
            this.runAllSynchronization();
        },

        // ---------------------------------------

        saveSettings: function ()
        {
            new Ajax.Request(M2ePro.url.get('formSubmit', $('edit_form').serialize(true)), {
                method: 'get',
                asynchronous: true,
                onSuccess: function (transport) {
                }
            });
        },

        // ---------------------------------------

        runSingleSynchronization: function (runNowButton)
        {
            MessageObj.clear();
            CommonObj.scrollPageToTop();

            var self = this;
            var marketplaceStatusSelect = $(runNowButton).up('div.admin__field').select('.marketplace_status_select')[0];

            self.marketplacesForUpdate = [marketplaceStatusSelect.readAttribute('marketplace_id')];
            self.marketplacesForUpdateCurrentIndex = 0;

            self.runNextMarketplaceNow();
            return true;
        },

        runEnabledSynchronization: function ()
        {
            var currentStatuses = this.getCurrentStatuses();
            var storedStatuses = this.getStoredStatuses();
            var changedStatuses = new Array();
            this.marketplacesForUpdate = new Array();

            for (var i = 0; i < storedStatuses.length; i++) {

                if ((storedStatuses[i].marketplace_id == currentStatuses[i].marketplace_id)
                    && (storedStatuses[i].status != currentStatuses[i].status)) {

                    this.storedStatuses[i].status = currentStatuses[i].status;
                    changedStatuses.push({
                        marketplace_id: currentStatuses[i].marketplace_id,
                        status: currentStatuses[i].status
                    });

                    this.changeStatusInfo(currentStatuses[i].marketplace_id, currentStatuses[i].status);

                    if (currentStatuses[i].status) {
                        this.marketplacesForUpdate[this.marketplacesForUpdate.length] = currentStatuses[i].marketplace_id;
                    }
                }
            }
            this.marketplacesForUpdateCurrentIndex = 0;

            this.runNextMarketplaceNow();
            return changedStatuses;
        },

        runAllSynchronization: function (statuses)
        {
            var statusesForSynch = statuses || this.getStoredStatuses();

            this.marketplacesForUpdate = new Array();
            this.marketplacesForUpdateCurrentIndex = 0;

            for (var i = 0; i < statusesForSynch.length; i++) {

                var marketplaceId = statusesForSynch[i].marketplace_id;
                var marketplaceState = statusesForSynch[i].status;

                if (!marketplaceId) {
                    continue;
                }

                this.changeStatusInfo(marketplaceId, marketplaceState);

                if (marketplaceState == 1) {
                    this.marketplacesForUpdate[this.marketplacesForUpdate.length] = marketplaceId;
                }
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

            if (self.marketplacesForUpdateCurrentIndex > 0) {

                $('synch_info_wait_' + self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex - 1]).hide();
                $('synch_info_process_' + self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex - 1]).hide();
                $('synch_info_complete_' + self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex - 1]).show();
            }

            if (self.marketplacesForUpdateCurrentIndex >= self.marketplacesForUpdate.length) {

                self.marketplacesForUpdate = new Array();
                self.marketplacesForUpdateCurrentIndex = 0;
                self.marketplacesUpdateFinished = true;

                self.synchProgressObj.end();
                self.synchProgressObj.printFinalMessage();

                return;
            }

            var marketplaceId = self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex];
            self.marketplacesForUpdateCurrentIndex++;

            $('synch_info_wait_' + marketplaceId).hide();
            $('synch_info_process_' + marketplaceId).show();
            $('synch_info_complete_' + marketplaceId).hide();

            var titleProgressBar = $('marketplace_title_' + marketplaceId).innerHTML;
            var componentTitle = $('status_'+marketplaceId).readAttribute('component_title');
            var component      = $('status_'+marketplaceId).readAttribute('component_name');

            if (componentTitle != '') {
                titleProgressBar = componentTitle + ' ' + titleProgressBar;
            }

            self.runNextMarketplaceTask(titleProgressBar, marketplaceId, component);
            return true;
        },

        runNextMarketplaceTask: function(titleProgressBar, marketplaceId, component)
        {
            this.synchProgressObj.runTask(
                titleProgressBar,
                M2ePro.url.get('runSynchNow', {'marketplace_id': marketplaceId}),
                M2ePro.url.get(component + '_marketplace/synchGetExecutingInfo'),
                'MarketplaceObj.runNextMarketplaceNow();'
            );
        },

        // ---------------------------------------

        changeStatus: function (element)
        {
            var marketplaceId = element.readAttribute('marketplace_id');
            var runSingleButton = $('run_single_button_' + marketplaceId);

            this.markChangedStatus(marketplaceId, element.value);

            if (element.value == '1') {
                if (this.getStoredStatusByMarketplaceId(marketplaceId) == element.value) {
                    runSingleButton && runSingleButton.show();
                }

            } else {
                $('synch_info_complete_' + marketplaceId).hide();
                runSingleButton && runSingleButton.hide();
            }
        },

        markChangedStatus: function (marketplaceId, status)
        {
            var storedStatus = this.getStoredStatusByMarketplaceId(marketplaceId);
            var changedStatus = $('changed_' + marketplaceId);

            if (storedStatus != status) {
                changedStatus.style.display = 'inline-block';
            } else {
                changedStatus.style.display = 'none';
            }
        },

        changeStatusInfo: function (marketplaceId, status)
        {
            if (status == 1) {
                $('synch_info_wait_' + marketplaceId).show();
                $('synch_info_process_' + marketplaceId).hide();
                $('synch_info_complete_' + marketplaceId).hide();
            } else {
                $('synch_info_wait_' + marketplaceId).hide();
                $('synch_info_process_' + marketplaceId).hide();
                $('synch_info_complete_' + marketplaceId).hide();
            }
        },

        completeWithError: function()
        {
            var self = this;

            $('synch_info_wait_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).hide();
            $('synch_info_process_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).hide();
            $('synch_info_complete_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).hide();
            $('synch_info_error_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).show();

            for (var i = self.marketplacesForUpdateCurrentIndex; i < self.marketplacesForUpdate.length; i++) {
                $('synch_info_wait_'+self.marketplacesForUpdate[i]).hide();
                $('synch_info_process_'+self.marketplacesForUpdate[i]).hide();
                $('synch_info_complete_'+self.marketplacesForUpdate[i]).hide();
                $('synch_info_skip_'+self.marketplacesForUpdate[i]).show();
            }

            self.marketplacesForUpdate = new Array();
            self.marketplacesForUpdateCurrentIndex = 0;
            self.marketplacesUpdateFinished = true;

            self.synchProgressObj.end();
            self.synchProgressObj.printFinalMessage();
        }

        // ---------------------------------------
    });
});
