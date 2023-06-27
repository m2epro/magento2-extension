define([
    'M2ePro/Common'
], function () {

    window.EbayListingAllItemsAction = Class.create(Common, {

        // ---------------------------------------

        initialize: function($super, actionProcessor)
        {
            this.actionProcessor = actionProcessor;
        },

        // ---------------------------------------

        listAction: function () {
            this.processActions(
                    M2ePro.translator.translate('listing_selected_items_message'),
                    M2ePro.url.get('runListProducts'),
            );
        },

        relistAction: function () {
            this.processActions(
                    M2ePro.translator.translate('relisting_selected_items_message'),
                    M2ePro.url.get('runRelistProducts'),
            );
        },

        reviseAction: function () {
            this.processActions(
                    M2ePro.translator.translate('revising_selected_items_message'),
                    M2ePro.url.get('runReviseProducts'),
            );
        },

        stopAction: function()
        {
            this.processActions(
                    M2ePro.translator.translate('stopping_selected_items_message'),
                    M2ePro.url.get('runStopProducts'),
            );
        },

        stopAndRemoveAction: function()
        {
            this.processActions(
                    M2ePro.translator.translate('stopping_and_removing_selected_items_message'),
                    M2ePro.url.get('runStopAndRemoveProducts'),
            );
        },

        // ---------------------------------------

        processActions: function (title, url) {

            var requestParams = {
                'is_realtime': (this.actionProcessor.gridHandler.getSelectedProductsArray().length <= 10)
            };
            this.actionProcessor.processActions(title, url, requestParams);
        },

        // ---------------------------------------
    });
});
