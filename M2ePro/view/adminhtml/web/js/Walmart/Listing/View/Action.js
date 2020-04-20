define([
    'M2ePro/Listing/View/Action'
], function () {

    window.WalmartListingViewAction = Class.create(ListingViewAction, {

        // ---------------------------------------

        deleteAndRemoveAction: function () {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('deleting_and_removing_selected_items_message'),
                M2ePro.url.get('runDeleteAndRemoveProducts'),
                selectedProductsParts
            );
        },

        resetProductsAction: function()
        {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('reset_blocked_products_message'),
                M2ePro.url.get('runResetProducts'),
                selectedProductsParts,
                {"is_realtime": true}
            );
        }

        // ---------------------------------------
    });

});