define([
    'M2ePro/SynchProgress'
], function () {
    EbayListingCreateGeneralMarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        end: function ($super)
        {
            $super();

            var self = this;
            if (self.result == self.resultTypeError) {
                self.printFinalMessage();
                CommonObj.scrollPageToTop();
                return;
            }

            this.saveClick(M2ePro.url.get('ebay_listing_create/index'), true)
        }

        // ---------------------------------------
    });
});
