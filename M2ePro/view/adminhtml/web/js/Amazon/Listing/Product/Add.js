define([
    'M2ePro/Common'
], function () {

    window.AmazonListingProductAdd = Class.create();
    AmazonListingProductAdd.prototype = Object.extend(new Common(), {

        // ---------------------------------------

        initialize: function (ProgressBarObj, WrapperObj) {
            this.categories = '';
            this.products = '';
            this.listing_id = null;
            this.is_list = null;
            this.back = '';
            this.emptyListing = 0;

            this.categoriesAddAction = null;
            this.categoriesDeleteAction = null;

            this.progressBarObj = ProgressBarObj;
            this.wrapperObj = WrapperObj;
        },

        // ---------------------------------------

        add: function (items, back, isList) {
            var self = this;
            self.is_list = isList;
            self.back = back;

            self.getListingId(items);

            if (self.emptyListing == 1) {
                return;
            }

            self.products = items;

            var parts = self.makeProductsParts();

            self.progressBarObj.reset();
            self.progressBarObj.setTitle('Adding Products to Listing');
            self.progressBarObj.setStatus('Adding in process. Please wait...');
            self.progressBarObj.show();
            self.scrollPageToTop();

            self.wrapperObj.lock();

            self.sendPartsProducts(parts, parts.length);
        },

        setCategoriesActions: function (addAction, deleteAction) {
            this.categoriesAddAction = addAction;
            this.categoriesDeleteAction = deleteAction;
        },

        getListingId: function (items) {
            var self = this;

            if (self.listing_id) {
                return;
            }

            var hrefParts = explode('/', window.location.href);

            for (var i = 0; i < hrefParts.length; i++) {
                if (hrefParts[i] == 'id') {
                    self.listing_id = hrefParts[i + 1];
                    break;
                }
            }
        },

        makeProductsParts: function () {
            var self = this;

            var productsInPart = 50;
            var productsArray = explode(',', self.products);
            var parts = new Array();

            if (productsArray.length < productsInPart) {
                return parts[0] = productsArray;
            }

            var result = new Array();
            for (var i = 0; i < productsArray.length; i++) {
                if (result.length == 0 || result[result.length - 1].length == productsInPart) {
                    result[result.length] = new Array();
                }
                result[result.length - 1][result[result.length - 1].length] = productsArray[i];
            }

            return result;
        },

        sendPartsProducts: function (parts, partsCount) {
            var self = this;

            if (parts.length == 0) {
                return;
            }

            var part = parts.splice(0, 1);
            part = part[0];
            var partString = implode(',', part);

            var isLastPart = '';
            if (parts.length <= 0) {
                isLastPart = 'yes';
            }

            new Ajax.Request(M2ePro.url.get('add_products'), {
                method: 'post',
                parameters: {
                    listing_id: self.listing_id,
                    is_last_part: isLastPart,
                    do_list: self.is_list,
                    back: self.back,
                    products: partString
                },
                onSuccess: function (transport) {

                    var percents = (100 / partsCount) * (partsCount - parts.length);

                    if (percents <= 0) {
                        self.progressBarObj.setPercents(0, 0);
                    } else if (percents >= 100) {
                        self.progressBarObj.setPercents(100, 0);
                        self.progressBarObj.setStatus('Adding has been completed.');

                        return setLocation(transport.responseText.evalJSON()['redirect']);
                    } else {
                        self.progressBarObj.setPercents(percents, 1);
                    }

                    setTimeout(function () {
                        self.sendPartsProducts(parts, partsCount);
                    }, 500);
                }
            });

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        },

        // ---------------------------------------

        setHideProductsPresentedInOtherListings: function (hideProductsOthersListings) {
            this.hideProductsOthersListings = hideProductsOthersListings;
        }

        // ---------------------------------------
    });
});