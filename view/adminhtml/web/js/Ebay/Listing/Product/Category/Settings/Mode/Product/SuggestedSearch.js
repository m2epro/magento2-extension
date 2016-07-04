define([
    'M2ePro/Common'
], function () {

    window.EbayListingProductCategorySettingsModeProductSuggestedSearch = Class.create(Common, {

        // ---------------------------------------

        searchResult: {
            failed: 0,
            succeeded: 0
        },

        // ---------------------------------------

        initialize: function () {
        },

        // ---------------------------------------

        resetSearchResult: function () {
            this.searchResult = {
                failed: 0,
                succeeded: 0
            };
        },

        // ---------------------------------------

        search: function (products, onComplete) {
            var parts = this.makeProductsParts(products);

            ProgressBarObj.reset();
            ProgressBarObj.setTitle('Getting Suggested Categories');
            ProgressBarObj.setStatus('Getting Suggested Categories in process. Please wait...');
            ProgressBarObj.show();

            this.scrollPageToTop();

            WrapperObj.lock();

            this.resetSearchResult();
            this.sendPartsProducts(parts, parts.length, onComplete);
        },

        makeProductsParts: function (products) {
            var productsInPart = 5;
            var productsArray = explode(',', products);
            var parts = [];

            if (productsArray.length < productsInPart) {
                return parts[0] = productsArray;
            }

            var result = [];
            for (var i = 0; i < productsArray.length; i++) {
                if (result.length == 0 || result[result.length - 1].length == productsInPart) {
                    result[result.length] = [];
                }
                result[result.length - 1][result[result.length - 1].length] = productsArray[i];
            }

            return result;
        },

        sendPartsProducts: function (parts, partsCount, onComplete) {
            if (parts.length == 0) {
                if (typeof onComplete == 'function') {
                    onComplete(this.searchResult);
                }
                
                $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

                this.resetSearchResult();
                return;
            }

            var part = parts.splice(0, 1)[0];
            var partString = implode(',', part);

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoGetSuggestedCategory'), {
                method: 'get',
                parameters: {
                    products_ids: partString
                },
                onSuccess: function (transport) {

                    var percents = (100 / partsCount) * (partsCount - parts.length);
                    var response = transport.responseText.evalJSON();

                    this.searchResult.failed += response['failed'];
                    this.searchResult.succeeded += response['succeeded'];

                    if (percents <= 0) {
                        ProgressBarObj.setPercents(0, 0);
                    } else if (percents >= 100) {
                        ProgressBarObj.setPercents(100, 0);
                        ProgressBarObj.setStatus('Suggested Categories has been received.');
                        ProgressBarObj.hide();
                        ProgressBarObj.reset();

                        WrapperObj.unlock();
                    } else {
                        ProgressBarObj.setPercents(percents, 1);
                    }

                    setTimeout(function () {
                        this.sendPartsProducts(parts, partsCount, onComplete);
                    }.bind(this), 500);
                }.bind(this)
            });
            
            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        }

        // ---------------------------------------
    });

});