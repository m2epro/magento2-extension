define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Marketplace'
], function (MessageObj) {

    window.WizardAmazonMarketplace = Class.create(Marketplace, {

        // ---------------------------------------

        proceedAction: function()
        {
            MessageObj.clear();

            if (!this.checkAllOptions()) {
                return;
            }

            var changedStatuses = this.saveWizardSettings(this.runAllSynchronization.bind(this));

            for (var i = 0; i < changedStatuses.length; i++) {
                $('changed_' + changedStatuses[i].marketplace_id).style.display = 'none';
                this.changeStatus($('status_' + changedStatuses[i].marketplace_id));
            }

            var waitingForEnd = setInterval(function() {
                if (this.completeStatus === true) {
                    this.disableAllOptions();

                    WizardObj.setStep(WizardObj.getNextStep());
                    WizardObj.complete();

                    clearInterval(waitingForEnd);
                }
            }.bind(this), 300);
        },

        // ---------------------------------------

        saveWizardSettings: function(callback)
        {
            var currentStatuses = this.getCurrentStatuses();
            var storedStatuses = this.getStoredStatuses();
            var changedStatuses = new Array();
            this.marketplacesForUpdate = new Array();

            for (var i =0; i < storedStatuses.length; i++) {

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

            this.synchErrors = 0;
            this.synchWarnings = 0;
            this.synchSuccess = 0;

            new Ajax.Request(M2ePro.url.get('formSubmit', $('edit_form').serialize(true)), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {
                    if (callback) {
                        callback();
                    }
                }
            });

            return changedStatuses;
        },

        runNextMarketplaceNow: function()
        {
            var self = this;

            if (self.marketplacesForUpdateCurrentIndex > 0) {

                $('synch_info_wait_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).hide();
                $('synch_info_process_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).hide();
                $('synch_info_complete_'+self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex-1]).show();

                var tempEndFlag = 0;
                if (self.marketplacesForUpdateCurrentIndex >= self.marketplacesForUpdate.length) {
                    tempEndFlag = 1;
                }

                new Ajax.Request(M2ePro.url.get('general/synchGetLastResult'), {
                    method:'get',
                    asynchronous: true,
                    onSuccess: function(transport) {

                        if (transport.responseText == self.synchProgressObj.resultTypeError) {
                            self.synchErrors++;
                        } else if (transport.responseText == self.synchProgressObj.resultTypeWarning) {
                            self.synchWarnings++;
                        } else {
                            self.synchSuccess++;
                        }

                        if (tempEndFlag == 1) {
                            if (self.synchErrors > 0) {
                                self.synchProgressObj.printFinalMessage(self.synchProgressObj.resultTypeError);
                            } else if (self.synchWarnings > 0) {
                                self.synchProgressObj.printFinalMessage(self.synchProgressObj.resultTypeWarning);
                            } else {
                                self.synchProgressObj.printFinalMessage(self.synchProgressObj.resultTypeSuccess);
                            }
                            self.synchErrors = 0;
                            self.synchWarnings = 0;
                            self.synchSuccess = 0;
                        }
                    }
                });
            }

            if (self.marketplacesForUpdateCurrentIndex >= self.marketplacesForUpdate.length) {

                self.marketplacesForUpdate = new Array();
                self.marketplacesForUpdateCurrentIndex = 0;
                self.marketplacesUpdateFinished = true;

                self.synchProgressObj.end();
                self.completeStatus = true;

                return;
            }

            var marketplaceId = self.marketplacesForUpdate[self.marketplacesForUpdateCurrentIndex];
            self.marketplacesForUpdateCurrentIndex++;

            $('synch_info_wait_'+marketplaceId).hide();
            $('synch_info_process_'+marketplaceId).show();
            $('synch_info_complete_'+marketplaceId).hide();

            var titleProgressBar = $('marketplace_title_'+marketplaceId).innerHTML;
            var marketplaceComponentName = $('status_'+marketplaceId).readAttribute('markeptlace_component_name');

            if (marketplaceComponentName != '') {
                titleProgressBar = marketplaceComponentName + ' ' + titleProgressBar;
            }

            self.synchProgressObj.runTask(
                titleProgressBar,
                M2ePro.url.get('runSynchNow', {'marketplace_id': marketplaceId}),
                '', 'WizardAmazonMarketplaceObj.runNextMarketplaceNow();'
            );

            return true;
        },

        // ---------------------------------------

        checkAllOptions: function()
        {
            var isAnyOptionsEnabled = $$('select[id*="status_"]').some(function(element) {
                return element.value == 1;
            });

            if (!isAnyOptionsEnabled) {
                MessageObj.addErrorMessage(
                    M2ePro.translator.translate('You must select at least one Marketplace you will work with.')
                );
                CommonObj.scrollPageToTop();
                return false;
            }

            return true;
        },

        disableAllOptions: function()
        {
            var allSelect = $$('select[id*="status_"]');

            if (allSelect.length) {
                allSelect.each(function(element) {
                    element.disabled = true;
                });
            }
        }

        // ---------------------------------------
    });
});