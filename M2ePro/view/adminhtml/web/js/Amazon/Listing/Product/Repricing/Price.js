define([
], function () {

    window.AmazonListingProductRepricingPrice = Class.create();
    AmazonListingProductRepricingPrice.prototype = {

        // ---------------------------------------

        initialize: function (containerId) {
        },

        // ---------------------------------------

        showRepricingPrice: function () {

            var groupedSkus = {};

            $$('.m2epro-repricing-price-value').each(function (el) {
                var accountId = el.getAttribute('account_id');

                if (typeof groupedSkus[accountId] == 'undefined') {
                    groupedSkus[accountId] = []
                }

                groupedSkus[accountId][groupedSkus[accountId].length] = el.getAttribute('sku');
            });

            groupedSkus = JSON.stringify(groupedSkus);

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_repricing/getUpdatedPriceBySkus'), {
                method: 'post',
                parameters: {
                    grouped_skus: groupedSkus
                },
                onSuccess: function (transport) {
                    var resultData = transport.responseText.evalJSON();

                    $H(resultData).each(function (item) {
                        $H(item.value).each(function (priceItem) {
                            $('m2epro_repricing_price_value_' + priceItem.key).update(priceItem.value)
                        });
                    });
                }
            });
        }

    };

});