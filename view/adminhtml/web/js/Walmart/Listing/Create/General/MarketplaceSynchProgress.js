define([
    'M2ePro/SynchProgress'
], function () {
    WalmartListingCreateGeneralMarketplaceSynchProgress = Class.create(SynchProgress, {

        // ---------------------------------------

        end: function ($super)
        {
            $super();

            var self = this;
            if (self.result == self.resultTypeError) {
                self.printFinalMessage();
                CommonObj.scrollPageToTop();
            }
        }

        // ---------------------------------------
    });
});
