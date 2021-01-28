define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
    'M2ePro/Plugin/ProgressBar'
], function(jQuery) {

    window.AmazonListingTransferring = Class.create(Common, {

        progressBarObj: null,
        listingId: null,

        // ---------------------------------------

        initialize: function(listingId) {
            this.listingId = listingId;
        },

        //----------------------------------------

        getSourceAccount: function() {
            return $('from_account_id').value;
        },

        getTargetAccount: function() {
            return $('to_account_id').value;
        },

        getSourceMarketplace: function() {
            return $('from_marketplace_id').value;
        },

        getSourceListing: function() {
            return $('from_listing_id').value;
        },

        //----------------------------------------

        getTargetMarketplace: function() {
            return $('to_marketplace_id').value;
        },

        getTargetStore: function() {
            return $('to_store_id').value;
        },

        getTargetListing: function() {
            return $('to_listing_id').value;
        },

        //----------------------------------------

        popupShow: function(selectedProductsIds) {
            new Ajax.Request(M2ePro.url.get('amazon_listing/transferring/index', {step: 1}), {
                method: 'post',
                asynchronous: true,
                showLoader: true,
                parameters: {
                    products_ids: selectedProductsIds.join(',')
                },
                onSuccess: function(transport) {

                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON();
                        if (response.error) {
                            this.alert(response.message);

                            return;
                        }
                    }

                    modalDialogMessage = new Element('div', {
                        id: 'modal_dialog_message'
                    });

                    jQuery(modalDialogMessage).modal({
                        title: M2ePro.translator.translate('Sell on Another Marketplace'),
                        type: 'slide',
                        buttons: [
                            {
                                text: 'Cancel',
                                class: 'action-secondary action-dismiss',
                                click: function(event) {
                                    this.closeModal(event);
                                }
                            },
                            {
                                text: 'Continue',
                                class: 'action-primary action-accept',
                                click: function() {
                                    AmazonListingTransferringObj.popupContinue();
                                }
                            }
                        ],
                        closed: function() {
                            return false;
                        }
                    }).modal('openModal');

                    modalDialogMessage.innerHTML = transport.responseText;
                    modalDialogMessage.innerHTML.evalScripts();

                }.bind(this)
            });
        },

        popupContinue: function() {
            if (!Validation.validate($('to_account_id')) ||
                !Validation.validate($('to_marketplace_id')) ||
                !Validation.validate($('to_store_id')) ||
                !Validation.validate($('to_listing_id'))
            ) {
                return;
            }

            setLocation(
                M2ePro.url.get(
                    'amazon_listing/transferring/index',
                    {
                        step: 2,
                        account_id: this.getTargetAccount(),
                        marketplace_id: this.getTargetMarketplace(),
                        store_id: this.getTargetStore(),
                        to_listing_id: this.getTargetListing()
                    }
                )
            );
        },

        // ---------------------------------------

        accountIdChange: function() {
            this.refreshMarketplaces();
        },

        storeIdChange: function() {
            this.refreshListings();
        },

        //----------------------------------------

        refreshMarketplaces: function() {
            new Ajax.Request(M2ePro.url.get('amazon_listing_transferring/getMarketplace'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    account_id: this.getTargetAccount()
                },
                onSuccess: function(transport) {
                    var marketplace = transport.responseText.evalJSON();

                    $('to_marketplace_id').value = marketplace.id;
                    $('to_marketplace_title').innerText = marketplace.title;

                    this.refreshListings();
                }.bind(this)
            });
        },

        refreshListings: function() {
            if (!this.getTargetAccount() || !this.getTargetMarketplace() || !this.getTargetStore()) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('amazon_listing_transferring/getListings'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    account_id: this.getTargetAccount(),
                    marketplace_id: this.getTargetMarketplace(),
                    store_id: this.getTargetStore(),
                    listing_id: this.getSourceListing()
                },
                onSuccess: function(transport) {

                    $('to_listing_id').update();

                    var listings = transport.responseText.evalJSON(),
                        listingsSelector = $('to_listing_id');

                    listingsSelector.appendChild(new Element('option', {value: '', class: 'empty', selected: true}));
                    listings.each(function(listing) {
                        listingsSelector.appendChild(new Element('option', {value: listing.id}))
                            .update(listing.title);
                    });
                    listingsSelector.appendChild(new Element('option', {value: 'create-new', style: 'color: brown;'}))
                        .update(M2ePro.translator.translate('Create new'));
                }.bind(this)
            });
        },

        //----------------------------------------

        addProducts: function(progressBatId, products, callback) {
            var parts = this.makeProductsParts(products, 100);

            this.progressBarObj = new ProgressBar(progressBatId);

            this.progressBarObj.reset();
            this.progressBarObj.setTitle(M2ePro.translator.translate('Sell on Another Marketplace'));
            this.progressBarObj.setStatus(M2ePro.translator.translate('Adding Products in process. Please wait...'));
            this.progressBarObj.show();

            this.sendPartsProducts(parts, parts.length, callback);
        },

        makeProductsParts: function(products, partSize) {
            var productsArray = products;
            var parts = new Array();

            if (productsArray.length < partSize) {
                parts[0] = productsArray;
                return parts;
            }

            var result = new Array();
            for (var i = 0; i < productsArray.length; i++) {
                if (result.length == 0 || result[result.length - 1].length == partSize) {
                    result[result.length] = new Array();
                }
                result[result.length - 1][result[result.length - 1].length] = productsArray[i];
            }

            return result;
        },

        sendPartsProducts: function(parts, partsCount, callback) {
            if (partsCount == 0) {
                return;
            }

            var isLastPart = partsCount === 1 ? 1 : 0;
            var part = parts.splice(0, 1)[0];

            new Ajax.Request(M2ePro.url.get('amazon_listing_transferring/addProducts'), {
                method: 'post',
                onCreate: function() {
                    Ajax.Responders.unregister(varienLoaderHandler.handler);
                },
                parameters: {
                    listing_id: this.listingId,
                    products: implode(',', part),
                    is_last_part: isLastPart
                },
                onSuccess: function() {
                    Ajax.Responders.register(varienLoaderHandler.handler);

                    var percents = ((100 - this.progressBarObj.getPercents()) / partsCount) + this.progressBarObj.getPercents();
                    if (percents >= 100) {
                        this.progressBarObj.setPercents(100, 0);
                        this.progressBarObj.setStatus('Adding has been completed');
                        callback();
                        return;
                    } else {
                        this.progressBarObj.setPercents(percents, 1);
                    }

                    setTimeout(function() {
                        this.sendPartsProducts(parts, parts.length, callback);
                    }.bind(this), 500);
                }.bind(this)
            });
        }
    });
});
