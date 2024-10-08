define([
    'jquery',
    'M2ePro/Marketplace',
    'prototype'
], function (jQuery) {

    window.WalmartMarketplace = Class.create(Marketplace, {
        runEnabledSynchronization: function ()
        {
            var currentStatuses = this.getCurrentStatuses();
            var storedStatuses = this.getStoredStatuses();
            var changedStatuses = new Array();
            this.marketplacesForUpdate = new Array();

            var needRunNextMarketplaceNow = false;
            for (var i = 0; i < storedStatuses.length; i++) {

                if ((storedStatuses[i].marketplace_id == currentStatuses[i].marketplace_id)
                        && (storedStatuses[i].status != currentStatuses[i].status)) {

                    this.storedStatuses[i].status = currentStatuses[i].status;
                    changedStatuses.push({
                        marketplace_id: currentStatuses[i].marketplace_id,
                        status: currentStatuses[i].status
                    });

                    if (this.storedStatuses[i].is_need_sync_after_save === false) {
                        continue;
                    }

                    this.changeStatusInfo(currentStatuses[i].marketplace_id, currentStatuses[i].status);

                    if (currentStatuses[i].status) {
                        this.marketplacesForUpdate[this.marketplacesForUpdate.length] = currentStatuses[i].marketplace_id;
                        needRunNextMarketplaceNow = true;
                    }
                }
            }

            if (needRunNextMarketplaceNow) {
                this.marketplacesForUpdateCurrentIndex = 0;
                this.runNextMarketplaceNow();
            }

            return changedStatuses;
        },
    });
});
