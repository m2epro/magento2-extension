define([
], function () {
    window.ProductTypeValidatorPopupClass = Class.create({
        closePopupCallback: undefined,
        closePopupCallbackArguments: [],

        open:  function (listingProductIds) {
            var self = this;
            new Ajax.Request(M2ePro.url.get('product_type_validation_modal_open'), {
                method: 'post',
                parameters: {
                    listing_product_ids: listingProductIds
                },
                onSuccess: function (transport) {
                    if (transport.responseText === '') {
                        self.executeClosePopupCallback();
                        return;
                    }

                    var modalDialog = $('modal_amazon_product_type_validation');
                    if (!modalDialog) {
                        modalDialog = new Element('div', {
                            id: 'modal_amazon_product_type_validation'
                        });
                    } else {
                        modalDialog.innerHTML = '';
                    }

                    window.amazonProductTypeValidation = jQuery(modalDialog).modal({
                        title: M2ePro.translator.translate('modal_title'),
                        type: 'slide',
                        buttons: [],
                        modalCloseBtnHandler: function() {
                            new Ajax.Request(M2ePro.url.get('product_type_validation_modal_close'), {
                                method: 'post',
                            });

                            if (typeof window['productTypeValidatorObjectName'] === 'string') {
                                window[window['productTypeValidatorObjectName']] = undefined;
                            }

                            self.executeClosePopupCallback();
                            window.amazonProductTypeValidation.modal('closeModal');
                        }
                    });
                    window.amazonProductTypeValidation.modal('openModal');

                    modalDialog.insert(transport.responseText);
                }
            });
        },

        openForProductType: function (productTypeId) {
            var self = this;
            new Ajax.Request(M2ePro.url.get('product_type_validation_listing_product_ids_by_product_type_id'), {
                method: 'post',
                parameters: {
                    product_type_id: productTypeId
                },
                onSuccess: function (transport) {
                    var listingProductIds = transport.responseText.evalJSON();

                    self.open(listingProductIds.join(','));
                }
            });


        },

        executeClosePopupCallback: function () {
            var self = this;
            if (typeof this.closePopupCallback !== 'undefined') {
                setTimeout(function () {
                    self.closePopupCallback(...self.closePopupCallbackArguments);
                }, 1)
            }
        }
    });

    window.ProductTypeValidatorPopup = new ProductTypeValidatorPopupClass();
});
