define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(modal, MessageObj) {
    window.WalmartListingProductEditChannelData = Class.create(Common, {

        gridHandler: null,

        editIdentifierPopup: null,
        editSkuPopup: null,

        frameObj: null,

        // ---------------------------------------

        initialize: function(gridHandler) {
            this.gridHandler = gridHandler;

            jQuery.validator.addMethod('M2ePro-validate-walmart-sku', function(value, el) {

                if (!el.up('tr').visible()) {
                    return true;
                }

                return value.length < 50;
            }, M2ePro.translator.translate('The length of SKU must be less than 50 characters.'));
        },

        //########################################

        showIdentifiersPopup: function(productId) {
            if (window.top !== window) {
                window.top.ListingGridObj.editChannelDataHandler.frameObj = window;
                window.top.ListingGridObj.editChannelDataHandler.showIdentifiersPopup(productId);

                return;
            }

            var self = this;
            new Ajax.Request(M2ePro.url.get('walmart_listing_product/getEditIdentifiersPopup'), {
                method: 'get',
                onSuccess: function(transport) {

                    var responseData = transport.responseText;

                    self.editIdentifierPopup = self.showPopup(responseData, 'edit_identifiers_popup', {
                        title: M2ePro.translator.translate('Edit Product ID'),
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary action-dismiss',
                            click: function(event) {
                                ListingGridObj.editChannelDataHandler.cancelEditIdentifier();
                            }
                        }, {
                            text: M2ePro.translator.translate('Submit'),
                            class: 'action-primary action-accept',
                            click: function(event) {
                                ListingGridObj.editChannelDataHandler.editIdentifier();
                            }
                        }]
                    });
                    self.editIdentifierPopup.productId = productId;
                }
            });
        },

        editIdentifier: function() {
            var self = this,
                identifier = $('identifier'),
                identifierName = identifier.selectedOptions[0].textContent;

            if (!self.validateForm()) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('walmart_listing_product/editIdentifier'), {
                method: 'post',
                parameters: {
                    product_id: self.editIdentifierPopup.productId,
                    type: identifier.value,
                    value: $('new_identifier_value').value
                },
                onSuccess: function(transport) {

                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    self.getAppropriateMessageObj().clear();
                    if (response.message) {
                        self.getAppropriateMessageObj().addError(response.message);
                    }

                    if (!response.result) {
                        return;
                    }

                    self.cancelEditIdentifier();

                    self.getAppropriateMessageObj().addSuccess(
                        M2ePro.translator.translate("Updating " + identifierName + " has submitted to be processed.")
                    );
                    self.getAppropriateGridObj().reload();
                }
            });
        },

        cancelEditIdentifier: function() {
            var self = this;

            if (typeof self.editIdentifierPopup == null) {
                return;
            }

            self.editIdentifierPopup.modal('closeModal');
            self.editIdentifierPopup = null;
        },

        // ---------------------------------------

        showEditSkuPopup: function(productId) {
            if (window.top !== window) {
                window.top.ListingGridObj.editChannelDataHandler.frameObj = window;
                window.top.ListingGridObj.editChannelDataHandler.showEditSkuPopup(productId);

                return;
            }

            var self = this;
            new Ajax.Request(M2ePro.url.get('walmart_listing_product/getEditSkuPopup'), {
                method: 'get',
                onSuccess: function(transport) {

                    var responseData = transport.responseText;

                    self.editSkuPopup = self.showPopup(responseData, 'edit_sku_popup', {
                        title: M2ePro.translator.translate('Edit SKU'),
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary action-dismiss',
                            click: function(event) {
                                ListingGridObj.editChannelDataHandler.cancelEditSku();
                            }
                        }, {
                            text: M2ePro.translator.translate('Submit'),
                            class: 'action-primary action-accept',
                            click: function(event) {
                                ListingGridObj.editChannelDataHandler.editSku();
                            }
                        }]
                    });
                    self.editSkuPopup.productId = productId;
                }
            });
        },

        editSku: function() {
            var self = this;

            if (!self.validateForm()) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('walmart_listing_product/editSku'), {
                method: 'post',
                parameters: {
                    product_id: self.editSkuPopup.productId,
                    value: $('new_sku_value').value
                },
                onSuccess: function(transport) {

                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    self.getAppropriateMessageObj().clear();
                    if (response.message) {
                        self.getAppropriateMessageObj().addError(response.message);
                    }

                    if (!response.result) {
                        return;
                    }

                    self.cancelEditSku();

                    self.getAppropriateMessageObj().addSuccess(
                        M2ePro.translator.translate('Updating SKU has submitted to be processed.')
                    );

                    self.getAppropriateGridObj().reload();
                }
            });
        },

        cancelEditSku: function() {
            var self = this;

            if (typeof self.editSkuPopup == null) {
                return;
            }

            self.editSkuPopup.modal('closeModal');
            self.editSkuPopup = null;
        },

        // ---------------------------------------

        showPopup: function(html, id, options) {
            var containerEl = $(id);

            if (containerEl) {
                containerEl.remove();
            }

            var modalPopup = new Element('div', {
                id: id
            }).insert({bottom: html});

            var popup = jQuery(modalPopup).modal({
                title: '',
                type: 'popup',
                buttons: options.buttons
            });

            popup.modal('openModal');

            return popup;
        },

        validateForm: function() {
            var validationResult = [];

            if ($('popup-edit-form')) {
                validationResult = Form.getElements('popup-edit-form').collect(Validation.validate);
            }

            if (validationResult.indexOf(false) != -1) {
                return false;
            }

            return true;
        },

        // ---------------------------------------

        getAppropriateGridObj: function() {
            return this.frameObj ? this.frameObj.ListingGridObj.editChannelDataHandler.gridHandler.getGridObj()
                : this.gridHandler.getGridObj();
        },

        getAppropriateMessageObj: function() {
            return this.frameObj ? this.frameObj.ListingGridObj.MessageObj : MessageObj;
        }

        // ---------------------------------------
    });
});
