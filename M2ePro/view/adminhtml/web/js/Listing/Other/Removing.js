define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (MessagesObj) {
    window.ListingOtherRemoving = Class.create(Action, {

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
            this.removingProducts(
                this.gridHandler.getSelectedProductsString()
            );
        },

        removingProducts: function(productsString)
        {
            new Ajax.Request(this.options.url.get('removingProducts'), {
                method: 'post',
                parameters: {
                    componentMode: this.options.customData.componentMode,
                    product_ids: productsString
                },
                onSuccess: (function(transport) {

                    MessagesObj.clear();

                    if (transport.responseText == '1') {
                        MessagesObj.addSuccessMessage(this.options.translator.translate('successfully_removed'));
                    } else {
                        MessagesObj.addErrorMessage(this.options.translator.translate('not_enough_data'));
                    }

                    this.gridHandler.unselectAllAndReload();
                }).bind(this)
            });
        }

        // ---------------------------------------
    });
});