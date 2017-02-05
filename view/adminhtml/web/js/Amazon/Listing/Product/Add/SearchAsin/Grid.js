define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Amazon/Listing/View/Action',
    'M2ePro/Amazon/Listing/Product/Search'
], function (modal, MessageObj) {

    AmazonListingProductAddSearchAsinGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getComponent: function () {
            return 'amazon';
        },

        // ---------------------------------------

        getMaxProductsInPart: function () {
            return 1000;
        },

        // ---------------------------------------

        prepareActions: function ($super) {
            $super();
            this.actionHandler = new AmazonListingViewAction(this);
            this.productSearchHandler = new AmazonListingProductSearch(this);

            this.actions = Object.extend(this.actions, {

                assignGeneralIdAction: (function () {
                    this.productSearchHandler.searchGeneralIdAuto(this.getSelectedProductsString())
                }).bind(this),
                unassignGeneralIdAction: (function () {
                    this.productSearchHandler.unmapFromGeneralId(this.getSelectedProductsString())
                }).bind(this)

            });

            this.productSearchHandler.clearSearchResultsAndOpenSearchMenu = function () {
                var self = this;

                self.confirm({
                    actions: {
                        confirm: function () {
                            self.popup.modal('closeModal');
                            self.unmapFromGeneralId(self.params.productId);
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            };
        },

        // ---------------------------------------

        parseResponse: function (response) {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        afterInitPage: function ($super) {
            $super();
        },

        editSearchSettings: function (title, listingId) {
            var self = this;

            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_add/viewSearchSettings'), {
                method: 'post',
                parameters: {
                    id: listingId
                },
                onSuccess: function (transport) {
                    if (!$('edit_search_settings_popup')) {
                        $('html-body').insert({bottom: '<div id="edit_search_settings_popup"></div>'});
                    }

                    $('edit_search_settings_popup').update(transport.responseText);

                    $('breadcrumb_container').remove();

                    self.searchSettnigsPopup = jQuery('#edit_search_settings_popup');

                    modal({
                        title: title,
                        type: 'slide',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            click: function () {
                                self.searchSettnigsPopup.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'action primary',
                            click: function () {
                                ListingGridHandlerObj.saveSearchSettings();
                                self.searchSettnigsPopup.modal('closeModal');
                            }
                        }]
                    }, self.searchSettnigsPopup);

                    self.searchSettnigsPopup.modal('openModal');
                    self.searchSettnigsPopup.listingId = listingId;
                }
            });
        },

        saveSearchSettings: function () {
            var self = this,
                data;

            if (self.searchSettingsForm && !self.searchSettingsForm.validate()) {
                return;
            }

            data = $('edit_form').serialize(true);
            data.id = self.searchSettnigsPopup.listingId;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_add/saveSearchSettings'), {
                method: 'post',
                parameters: data,
                onSuccess: function (transport) {
                    self.actionHandler.gridHandler.unselectAllAndReload();
                    self.searchSettnigsPopup.modal('closeModal');
                }
            });
        },

        checkSearchResults: function (listingId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_add/checkSearchResults'), {
                method: 'post',
                parameters: {
                    id: listingId
                },
                onSuccess: function (transport) {
                    var response = self.parseResponse(transport);

                    if (response.redirect) {
                        return setLocation(response.redirect);
                    }

                    if (!$('search_asin_new_asin_popup')) {
                        $('html-body').insert({bottom: response.html});
                    }

                    self.newAsinPopup = jQuery('#search_asin_new_asin_popup');

                    modal({
                        title: M2ePro.translator.translate('new_asin_popup_title'),
                        type: 'popup',
                        buttons: [{
                            class: 'action-secondary action-dismiss',
                            text: M2ePro.translator.translate('No'),
                            click: function () {
                                self.showNewAsinPopup(0);
                            }
                        }, {
                            text: M2ePro.translator.translate('Yes'),
                            class: 'action-primary action-accept',
                            click: function () {
                                self.showNewAsinPopup(1);
                            }
                        }]
                    }, self.newAsinPopup);

                    var modalHeader = jQuery('#search_asin_new_asin_popup')
                                        .closest('.modal-inner-wrap')
                                        .find('h1.modal-title'),
                        tips = jQuery('#new_asin_title_help_tips');

                    tips.insertAfter(modalHeader);
                    tips.show();

                    self.newAsinPopup.modal('openModal');
                    self.newAsinPopup.listingId = listingId;
                }
            });
        },

        showNewAsinPopup: function (showNewAsinStep) {
            var self = this,
                remember = $('asin_search_new_asin_remember_checkbox').checked;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_add/showNewAsinStep'), {
                method: 'post',
                parameters: {
                    show_new_asin_step: +showNewAsinStep,
                    remember: +remember,
                    id: self.newAsinPopup.listingId
                },
                onSuccess: function (transport) {
                    var response = self.parseResponse(transport);

                    if (response.redirect) {
                        return setLocation(response.redirect);
                    }
                }
            });

            self.newAsinPopup.modal('closeModal');
        },

        // ---------------------------------------

        showNotCompletedPopup: function () {
            var self = this;

            if (!$('not_completed_popup')) {
                $('html-body').insert({bottom: '<div id="not_completed_popup">' + M2ePro.translator.translate('not_completed_popup_text') + '</div>'});
            }

            var popup = jQuery('#not_completed_popup');

            modal({
                title: M2ePro.translator.translate('not_completed_popup_title'),
                type: 'popup',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        popup.modal('closeModal');
                    }
                }]
            }, popup);

            popup.modal('openModal');
        }

        // ---------------------------------------
    });

});