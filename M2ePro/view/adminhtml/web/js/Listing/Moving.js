define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (jQuery, modal, MessagesObj) {
    window.ListingMoving = Class.create(Action, {

        // ---------------------------------------

        options: {},

        setOptions: function (options) {
            this.options = Object.extend(this.options, options);
            return this;
        },

        // ---------------------------------------

        run: function () {
            this.getGridHtml(
                this.gridHandler.getSelectedProductsArray()
            );
        },

        // ---------------------------------------

        openPopUp: function (gridHtml, popup_title, buttons)
        {
            var self = this;

            if (typeof buttons === 'undefined') {
                buttons = [{
                    class: 'action-default action-dismiss',
                    text: M2ePro.translator.translate('Cancel'),
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Add New Listing'),
                    class: 'action-primary action-accept',
                    click: function () {
                        self.startListingCreation(M2ePro.url.get('add_new_listing_url'));
                    }
                }];
            }

            var modalDialogMessage = $('move_modal_dialog_message');

            if (modalDialogMessage) {
                modalDialogMessage.remove();
            }

            modalDialogMessage = new Element('div', {
                id: 'move_modal_dialog_message'
            });

            modalDialogMessage.update(gridHtml);

            this.popUp = jQuery(modalDialogMessage).modal({
                title: popup_title,
                type: 'slide',
                buttons: buttons
            });

            this.popUp.modal('openModal');
        },

        // ---------------------------------------

        getGridHtml: function (selectedProducts) {
            var self = this;

            MessagesObj.clear();

            self.selectedProducts = selectedProducts;
            self.gridHandler.unselectAll();

            var callback = function (response) {
                new Ajax.Request(self.options.url.get('moveToListingGridHtml'), {
                    method: 'get',
                    parameters: {
                        componentMode: self.options.customData.componentMode,
                        accountId: response.accountId,
                        marketplaceId: response.marketplaceId,
                        ignoreListings: self.options.customData.ignoreListings
                    },
                    onSuccess: function (transport) {
                        var title = selectedProducts.length == 1 ?
                        self.options.translator.translate('popup_title_single', self.gridHandler.getProductNameByRowId(selectedProducts[0])) :
                            self.options.translator.translate('popup_title');
                        self.openPopUp(transport.responseText, title);
                    }
                });
            };

            new Ajax.Request(self.options.url.get('prepareData'), {
                method: 'post',
                parameters: {
                    componentMode: self.options.customData.componentMode,
                    selectedProducts: Object.toJSON(self.selectedProducts)
                },
                onSuccess: function (transport) {

                    if (transport.responseText == 1) {
                        self.alert(self.options.translator.translate('select_only_mapped_products'));
                    } else if (transport.responseText == 2) {
                        self.alert(self.options.translator.translate('select_the_same_type_products'));
                    } else {
                        var response = transport.responseText.evalJSON();

                        if (response.offerListingCreation) {
                            return self.offerListingCreation(
                                response.accountId,
                                response.marketplaceId,
                                function () {
                                    callback.call(self, response);
                                }
                            );
                        }

                        callback.call(self, response);
                    }
                }
            });
        },

        // ---------------------------------------

        tryToSubmit: function (listingId) {
            var self = this;

            new Ajax.Request(this.options.url.get('tryToMoveToListing'), {
                method: 'post',
                parameters: {
                    componentMode: this.options.customData.componentMode,
                    selectedProducts: Object.toJSON(this.selectedProducts),
                    listingId: listingId
                },
                onSuccess: (function (transport) {

                    var response = transport.responseText.evalJSON();

                    if (response.result == 'success') {
                        return this.submit(listingId);
                    }

                    new Ajax.Request(this.options.url.get('getFailedProductsHtml'), {
                        method: 'get',
                        parameters: {
                            componentMode: this.options.customData.componentMode,
                            failed_products: Object.toJSON(response.failed_products)
                        },
                        onSuccess: (function (transport) {

                            this.popUp.modal('closeModal');
                            this.openPopUp(
                                transport.responseText,
                                this.options.translator.translate('failed_products_popup_title'),
                                [{
                                    class: 'back',
                                    text: M2ePro.translator.translate('Back'),
                                    click: function (event) {
                                        this.closeModal(event);
                                        self.getGridHtml(self.selectedProducts);
                                    }
                                }, {
                                    text: M2ePro.translator.translate('Continue'),
                                    class: 'action-primary',
                                    click: function () {
                                        self.submit(listingId);
                                    }
                                }]
                            );

                        }).bind(this)
                    });

                }).bind(this)
            });
        },

        // ---------------------------------------

        submit: function (listingId) {
            var self = this;
            new Ajax.Request(self.options.url.get('moveToListing'), {
                method: 'post',
                parameters: {
                    componentMode: self.options.customData.componentMode,
                    selectedProducts: Object.toJSON(self.selectedProducts),
                    listingId: listingId
                },
                onSuccess: function (transport) {

                    self.popUp.modal('closeModal');
                    self.scrollPageToTop();

                    var response = transport.responseText.evalJSON();

                    if (response.result == 'success') {
                        self.gridHandler.unselectAllAndReload();
                        MessagesObj.addSuccessMessage(self.options.translator.translate('successfully_moved'));
                        return;
                    }

                    var message = '';
                    if (response.errors == self.selectedProducts.length) { // all items failed
                        message = self.options.translator.translate('products_were_not_moved');
                    } else {
                        message = self.options.translator.translate('some_products_were_not_moved');
                        self.gridHandler.unselectAllAndReload();
                    }

                    MessagesObj.addErrorMessage(str_replace('%url%', self.options.url.get('logViewUrl'), message));
                }
            });
        },

        // ---------------------------------------

        offerListingCreation: function (accountId, marketplaceId, callback) {

            var self = this;

            self.confirm({
                content: self.options.translator.translate('create_listing'),
                actions: {
                    confirm: function () {
                        new Ajax.Request(self.options.url.get('createDefaultListing'), {
                            method: 'post',
                            parameters: {
                                componentMode: self.options.customData.componentMode,
                                accountId: accountId,
                                marketplaceId: marketplaceId
                            },
                            onSuccess: function (transport) {
                                callback.call(self);
                            }
                        });
                    },
                    cancel: function () {
                        return callback.call(self);
                    }
                }
            });
        },

        // ---------------------------------------

        startListingCreation: function (url, response) {
            var self = this;
            var win = window.open(url);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                listingMovingGridJsObject.reload();
            }, 1000);
        }

        // ---------------------------------------
    });
});