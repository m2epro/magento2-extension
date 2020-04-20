define([
    'jquery',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (jQuery, MessagesObj) {

    window.ListingOtherUnmapping = Class.create(Action, {

        // ---------------------------------------

        options: {},

        setOptions: function(options)
        {
            this.options = Object.extend(this.options, options);
            return this;
        },

        // ---------------------------------------

        run: function()
        {
            this.unmappingProducts(
                this.gridHandler.getSelectedProductsString()
            );
        },

        unmappingProducts: function(productsString)
        {
            new Ajax.Request(this.options.url.get('unmappingProducts'), {
                method: 'post',
                parameters: {
                    componentMode: this.options.customData.componentMode,
                    product_ids: productsString
                },
                onSuccess: (function(transport) {

                    MessagesObj.clear();

                    if (transport.responseText == '1') {
                        MessagesObj.addSuccessMessage(
                            this.options.translator.translate('successfully_unmapped')
                        );
                    } else {
                        MessagesObj.addErrorMessage(
                            this.options.translator.translate('not_enough_data')
                        );
                    }

                    this.gridHandler.unselectAllAndReload();
                }).bind(this)
            });
        }

        // ---------------------------------------
    });
});