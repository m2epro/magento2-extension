define([
    'mage/translate',
    'M2ePro/Listing/Other/Grid',
    'M2ePro/Ebay/Listing/Moving',
    'M2ePro/Ebay/Listing/Removing'
], function ($t) {
    window.EbayListingOtherGrid = Class.create(ListingOtherGrid, {

        prepareActions: function ($super) {
            $super();
            this.createProductHandler = new ListingOtherCreateProduct(this);

            this.actions['createProductAction'] = this.createProductHandler.run.bind(this.createProductHandler)
        },

        // ---------------------------------------

        afterPrepareAction: function()
        {
            this.movingHandler = new EbayListingMoving(this);
            this.removingHandler = new EbayListingOtherRemoving(this);
        },

        // ---------------------------------------

        tryToMove: function(listingId)
        {
            this.movingHandler.submit(listingId, this.onSuccess);
        },

        onSuccess: function(wizardId)
        {
            const refererUrl = M2ePro.url.get('categorySettings', {id: wizardId});

            setLocation(refererUrl);
        },

        // ---------------------------------------

        getComponent: function()
        {
            return 'ebay';
        },

        // ---------------------------------------

        getSelectedItemsParts: function()
        {
            var selectedProductsArray = this.getSelectedProductsArray();

            if (this.getSelectedProductsString() == '' || selectedProductsArray.length == 0) {
                return [];
            }

            var maxProductsInPart = this.getMaxProductsInPart();

            var result = [];
            for (var i=0;i<selectedProductsArray.length;i++) {
                if (result.length == 0 || result[result.length-1].length == maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length-1][result[result.length-1].length] = selectedProductsArray[i];
            }

            return result;
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 10;
        },

        // ---------------------------------------

        massActionSubmitClick: function()
        {
            if (this.validateItemsForMassAction() === false) {
                return;
            }

            const self = this;
            let selectAction = true;
            $$('select#'+self.gridId+'_massaction-select option').each(function(o) {
                if (o.selected && o.value == '') {
                    self.alert($t('Please select Action.'));
                    selectAction = false;
                    return;
                }
            });

            if (!selectAction) {
                return;
            }

            self.scrollPageToTop();

            const selectedAction = $('ebayListingUnmanagedGrid_massaction-select').value;

            if (selectedAction === 'removing') {
                self.confirm({
                    title: $t('Remove Item(s) from eBay'),
                    content: '<p>' + $t('You are about to permanently remove the selected item(s) from your eBay account. This action will delete the item(s) from the eBay channel and cannot be undone.') + '</p>'
                            + '<br><p>' + $t('Are you sure you want to proceed?') + '</p>',
                    actions: {
                        confirm: () => {
                            self.actions['removingAction']();
                        },
                        cancel: function () {}
                    }
                });

                return;
            }

            self.confirm({
                actions: {
                    confirm: function () {
                        $$('select#'+self.gridId+'_massaction-select option').each(function(o) {

                            if (!o.selected) {
                                return;
                            }

                            if (!o.value || !self.actions[o.value + 'Action']) {
                                self.alert($t('Please select Action.'));
                                return;
                            }

                            self.actions[o.value + 'Action']();

                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },
    });
});
