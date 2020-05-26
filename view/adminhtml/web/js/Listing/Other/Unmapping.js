define([
    'jquery',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (jQuery, MessagesObj) {

    window.ListingOtherUnmapping = Class.create(Action, {

        // ---------------------------------------

        run: function()
        {
            this.unmappingProducts(
                this.gridHandler.getSelectedProductsString()
            );
        },

        unmappingProducts: function(productsString)
        {
            new Ajax.Request(M2ePro.url.get('unmappingProducts'), {
                method: 'post',
                parameters: {
                    componentMode: M2ePro.customData.componentMode,
                    product_ids: productsString
                },
                onSuccess: (function(transport) {

                    MessagesObj.clear();

                    if (transport.responseText == '1') {
                        MessagesObj.addSuccessMessage(
                            M2ePro.translator.translate('successfully_unmapped')
                        );
                    } else {
                        MessagesObj.addErrorMessage(
                            M2ePro.translator.translate('not_enough_data')
                        );
                    }

                    this.gridHandler.unselectAllAndReload();
                }).bind(this)
            });
        }

        // ---------------------------------------
    });
});