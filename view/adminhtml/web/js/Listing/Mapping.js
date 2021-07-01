define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(jQuery, modal, MessagesObj) {
    window.ListingMapping = Class.create(Common, {

        // ---------------------------------------

        initialize: function(gridHandler, component) {
            this.gridHandler = gridHandler;
            this.component = component;
        },

        openPopUp: function(otherProductId, productTitle) {
            this.gridHandler.unselectAll();
            let self = this;
            let title = M2ePro.translator.translate('Linking Product');

            if (productTitle) {
                title = title + ' "' + productTitle + '"';
            }

            new Ajax.Request(M2ePro.url.get('mapProductPopupHtml'), {
                method: 'post',
                parameters: {
                    component_mode: self.component
                },
                onSuccess: function(transport) {

                    var modalDialogMessage = $('map_modal_dialog_message');

                    if (modalDialogMessage) {
                        modalDialogMessage.remove();
                    }

                    modalDialogMessage = new Element('div', {
                        id: 'map_modal_dialog_message'
                    });

                    this.popUp = jQuery(modalDialogMessage).modal({
                        title: title,
                        type: 'slide',
                        buttons: []
                    });
                    this.popUp.modal('openModal');

                    modalDialogMessage.insert(transport.responseText);
                    $('other_product_id').value = otherProductId;
                }.bind(this)
            });
        },

        // ---------------------------------------

        map: function(productId) {
            let self = this;
            let otherProductId = $('other_product_id').value;

            MessagesObj.clearAll();

            if (otherProductId == '' || (/^\s*(\d)*\s*$/i).test(otherProductId) == false) {
                return;
            }

            if (productId == '' || (/^\s*(\d)*\s*$/i).test(productId) == false) {
                return;
            }

            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('listing_other_mapping/map', {}), {
                method: 'post',
                parameters: {
                    component_mode: self.component,
                    product_id: productId,
                    other_product_id: otherProductId
                },
                onSuccess: function(transport) {

                    let response = transport.responseText.evalJSON();
                    if (response.result) {
                        this.gridHandler.unselectAllAndReload();
                        this.popUp.modal('closeModal');
                        this.scrollPageToTop();
                        MessagesObj.addSuccess(M2ePro.translator.translate('Product(s) was Linked.'));
                    } else {
                        alert(M2ePro.translator.translate('Product does not exist.'));
                    }
                }.bind(this)
            });
        },

        remap: function(productId) {
            let self = this;
            let listingProductId = $('other_product_id').value;

            MessagesObj.clearAll();

            if (listingProductId == '' || (/^\s*(\d)*\s*$/i).test(listingProductId) == false) {
                return;
            }

            if (productId == '' || (/^\s*(\d)*\s*$/i).test(productId) == false) {
                return;
            }

            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('listing_mapping/remap'), {
                method: 'post',
                parameters: {
                    component_mode: self.component,
                    product_id: productId,
                    listing_product_id: listingProductId
                },
                onSuccess: function(transport) {

                    let response = transport.responseText.evalJSON();

                    this.gridHandler.unselectAllAndReload();
                    this.popUp.modal('closeModal');
                    this.scrollPageToTop();

                    if (response.result) {
                        MessagesObj.addSuccess(response.message);
                    } else {
                        MessagesObj.addError(response.message);
                    }
                }.bind(this)
            });
        }
    });

    // ---------------------------------------
});
