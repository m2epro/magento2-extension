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
                        MessagesObj.addSuccess(M2ePro.translator.translate('Product(s) was Removed.'));
                    } else {
                        MessagesObj.addError(M2ePro.translator.translate('Not enough data'));
                    }

                    this.gridHandler.unselectAllAndReload();
                }).bind(this)
            });
        }

        // ---------------------------------------
    });
});
