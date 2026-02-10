define([
    'mage/translate',
    'M2ePro/Common'
], function ($t) {

    window.AmazonListingAllItemsAction = Class.create(Common, {

        // ---------------------------------------

        /**
         * @param $super
         * @param {ListingActionProcessor} actionProcessor
         */
        initialize: function($super, actionProcessor)
        {
            this.actionProcessor = actionProcessor;
        },

        // ---------------------------------------

        listAction: function () {
            this.actionProcessor.processActions(
                    $t('Listing Selected Items On Amazon'),
                    M2ePro.url.get('runListProducts'),
            );
        },

        relistAction: function () {
            this.actionProcessor.processActions(
                    $t('Relisting Selected Items On Amazon'),
                    M2ePro.url.get('runRelistProducts'),
            );
        },

        reviseAction: function () {
            this.actionProcessor.processActions(
                    $t('Revising Selected Items On Amazon'),
                    M2ePro.url.get('runReviseProducts'),
            );
        },

        stopAction: function()
        {
            this.actionProcessor.processActions(
                    $t('Stopping Selected Items On Amazon'),
                    M2ePro.url.get('runStopProducts'),
            );
        },

        stopAndRemoveAction: function()
        {
            this.actionProcessor.processActions(
                    $t('Stopping On Amazon And Removing From Listing Selected Items'),
                    M2ePro.url.get('runStopAndRemoveProducts'),
            );
        },

        deleteAndRemoveAction: function()
        {
            this.actionProcessor.processActions(
                    $t('Removing From Amazon And Listing Selected Items'),
                    M2ePro.url.get('runDeleteAndRemoveProducts'),
            );
        }
    });
});
