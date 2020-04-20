define([
    'M2ePro/Grid',
    'prototype'
], function () {

    window.EbayListingGrid = Class.create(Grid, {

        // ---------------------------------------

        backParam: base64_encode('*/ebay_listing/index'),

        // ---------------------------------------

        prepareActions: function()
        {
            return false;
        },

        // ---------------------------------------

        addProductsSourceProductsAction: function(id)
        {
            setLocation(M2ePro.url.get('ebay_listing_product_add/index', {
                id: id,
                source: 'product',
                clear: true,
                back: this.backParam
            }));
        },

        // ---------------------------------------

        addProductsSourceCategoriesAction: function(id)
        {
            setLocation(M2ePro.url.get('ebay_listing_product_add/index', {
                id: id,
                source: 'category',
                clear: true,
                back: this.backParam
            }));
        }

        // ---------------------------------------
    });

});
