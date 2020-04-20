define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (jQuery, modal, messageObj) {
    window.EbayListingVariationProductManage = Class.create(Action,{

        // ---------------------------------------

        initialize: function($super,gridHandler)
        {
            var self = this;

            $super(gridHandler);

        },

        // ---------------------------------------

        options: {},

        setOptions: function(options)
        {
            this.options = Object.extend(this.options,options);
            return this;
        },

        // ---------------------------------------

        parseResponse: function(response)
        {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        openPopUp: function(productId, title, filter)
        {
            var self = this;

            messageObj.clear();

            new Ajax.Request(M2ePro.url.get('variationProductManage'), {
                method: 'post',
                parameters: {
                    product_id : productId,
                    filter: filter
                },
                onSuccess: function (transport) {

                    var modalDialog = $('modal_variation_product_manage');
                    if (!modalDialog) {
                        modalDialog = new Element('div', {
                            id: 'modal_variation_product_manage'
                        });
                    } else {
                        modalDialog.innerHTML = '';
                    }

                    window.variationProductManagePopup = jQuery(modalDialog).modal({
                        title: title.escapeHTML(),
                        type: 'slide',
                        buttons: []
                    });
                    variationProductManagePopup.modal('openModal');

                    modalDialog.insert(transport.responseText);
                    modalDialog.innerHTML.evalScripts();

                    variationProductManagePopup.productId = productId;
                }
            });
        },

        closeManageVariationsPopup: function()
        {
            variationProductManagePopup.modal('closeModal');
        }

        // ---------------------------------------
    });

});