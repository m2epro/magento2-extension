define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (jQuery, modal, MessagesObj) {
    window.ListingOtherMapping = Class.create(Common, {

        // ---------------------------------------

        initialize: function(gridHandler,component)
        {
            this.gridHandler = gridHandler;
            this.component = component;

            this.attachEvents();
        },

        // ---------------------------------------

        openPopUp: function(productTitle, otherProductId)
        {
            this.attachEvents();
            this.gridHandler.unselectAll();

            var modalDialogMessage = $('map_modal_dialog_message');

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('div', {
                    id: 'map_modal_dialog_message'
                });
            }

            this.popUp = jQuery(modalDialogMessage).modal({
                title: M2ePro.translator.translate('Map Item "%product_title%" with Magento Product', productTitle),
                type: 'slide',
                buttons: []
            });
            this.popUp.modal('openModal');

            var content = $('pop_up_content');
            modalDialogMessage.insert(content.show());

            $('other_product_id').value = otherProductId;
        },

        // ---------------------------------------

        attachEvents: function()
        {
            var self = this;

            $('mapping_submit_button').stopObserving('click').observe('click',function(event) {
                self.map();
            });
            $('product_id').stopObserving('keypress').observe('keypress',function(event) {
                event.keyCode == Event.KEY_RETURN && self.map();
            });
            $('sku').stopObserving('keypress').observe('keypress',function(event) {
                event.keyCode == Event.KEY_RETURN && self.map();
            });
        },

        // ---------------------------------------

        map: function()
        {
            var self = this;
            var productId = $('product_id').value;
            var sku = $('sku').value;
            var otherProductId = $('other_product_id').value;

            MessagesObj.clear();

            if (otherProductId == '' || (/^\s*(\d)*\s*$/i).test(otherProductId) == false) {
                return;
            }

            if ((sku == '' && productId == '')) {
                $('product_id').focus();
                self.alert(M2ePro.translator.translate('Please enter correct Product ID or SKU'));
                return;
            }
            if (((/^\s*(\d)*\s*$/i).test(productId) == false)) {
                self.alert(M2ePro.translator.translate('Please enter correct Product ID.'));
                $('product_id').focus();
                $('product_id').value = '';
                $('sku').value = '';
                return;
            }

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('listing_other_mapping/map', {}), {
                            method: 'post',
                            parameters: {
                                componentMode: self.component,
                                productId: productId,
                                sku: sku,
                                otherProductId: otherProductId
                            },
                            onSuccess: function(transport) {

                                if (transport.responseText == 0) {
                                    self.gridHandler.unselectAllAndReload();
                                    self.popUp.modal('closeModal');
                                    self.scrollPageToTop();
                                    MessagesObj.addSuccessMessage(
                                        M2ePro.translator.translate('Product(s) was successfully Mapped.')
                                    );
                                } else if (transport.responseText == 1) {
                                    self.alert(M2ePro.translator.translate('Product does not exist.'));
                                } else if (transport.responseText == 2) {
                                    self.alert(M2ePro.translator.translate('Current version only supports Simple Products. Please, choose Simple Product.'));
                                } else if (transport.responseText == 3) {
                                    self.popUp.modal('closeModal');
                                    self.scrollPageToTop();
                                    MessagesObj.addErrorMessage(
                                        M2ePro.translator.translate('Item was not Mapped as the chosen %product_id% Simple Product has Custom Options.', productId)
                                    );
                                }
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        }

        // ---------------------------------------
    });
});