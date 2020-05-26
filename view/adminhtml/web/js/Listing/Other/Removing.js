define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (MessagesObj) {
    window.ListingOtherRemoving = Class.create(Action, {

        // ---------------------------------------

        run: function()
        {
            this.removingProducts(
                this.gridHandler.getSelectedProductsString()
            );
        },

        removingProducts: function(productsString)
        {
            new Ajax.Request(M2ePro.url.get('removingProducts'), {
                method: 'post',
                parameters: {
                    componentMode: M2ePro.customData.componentMode,
                    product_ids: productsString
                },
                onSuccess: (function(transport) {

                    MessagesObj.clear();

                    if (transport.responseText == '1') {
                        MessagesObj.addSuccessMessage(M2ePro.translator.translate('successfully_removed'));
                    } else {
                        MessagesObj.addErrorMessage(M2ePro.translator.translate('not_enough_data'));
                    }

                    this.gridHandler.unselectAllAndReload();
                }).bind(this)
            });
        }

        // ---------------------------------------
    });
});