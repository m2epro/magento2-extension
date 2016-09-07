define([
    'M2ePro/Action'
], function () {

    window.AmazonListingProductRepricing = Class.create(Action, {

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            $super(gridHandler);
        },

        // ---------------------------------------

        options: {},

        setOptions: function (options) {
            this.options = Object.extend(this.options, options);
            return this;
        },

        // ---------------------------------------

        openManagement: function () {
            window.open(M2ePro.url.get('amazon_listing_product_repricing/openManagement'));
        },

        // ---------------------------------------

        addToRepricing: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openAddProducts'), {'products_ids': productsIds});
        },

        showDetails: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openShowDetails'), {'products_ids': productsIds});
        },

        editRepricing: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openEditProducts'), {'products_ids': productsIds});
        },

        removeFromRepricing: function (productsIds) {
            return this.postForm(M2ePro.url.get('amazon_listing_product_repricing/openRemoveProducts'), {'products_ids': productsIds});
        }

        // ---------------------------------------
    });
});