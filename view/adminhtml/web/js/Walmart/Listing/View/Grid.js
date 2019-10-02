define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Listing/Moving',
    'M2ePro/Walmart/Listing/View/Action',
    'M2ePro/Walmart/Listing/Product/Template/Category',
    'M2ePro/Walmart/Listing/Product/Variation/Manage',
    'M2ePro/Walmart/Listing/Product/EditChannelData'
], function (MessageObj) {

    window.WalmartListingViewGrid = Class.create(ListingViewGrid, {

        MessageObj: null,

        // ---------------------------------------

        getLogViewUrl: function (rowId) {
            var idField = M2ePro.php.constant('\\Ess\\M2ePro\\Block\\Adminhtml\\Log\\Listing\\Product\\AbstractGrid::LISTING_PRODUCT_ID_FIELD');

            var params = {};
            params[idField] = rowId;

            return M2ePro.url.get('walmart_log_listing_product/index', params);
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 10;
        },

        // ---------------------------------------

        prepareActions: function($super)
        {
            this.MessageObj = MessageObj;

            this.actionHandler = new WalmartListingViewAction(this);

            this.actions = {
                listAction: this.actionHandler.listAction.bind(this.actionHandler),
                relistAction: this.actionHandler.relistAction.bind(this.actionHandler),
                reviseAction: this.actionHandler.reviseAction.bind(this.actionHandler),
                stopAction: this.actionHandler.stopAction.bind(this.actionHandler),
                stopAndRemoveAction: this.actionHandler.stopAndRemoveAction.bind(this.actionHandler),
                previewItemsAction: this.actionHandler.previewItemsAction.bind(this.actionHandler),
                startTranslateAction: this.actionHandler.startTranslateAction.bind(this.actionHandler),
                stopTranslateAction: this.actionHandler.stopTranslateAction.bind(this.actionHandler)
            };

            this.movingHandler = new ListingMoving(this);

            this.templateCategoryHandler    = new WalmartListingProductTemplateCategory(this);

            this.variationProductManageHandler = new WalmartListingProductVariationManage(this);
            this.editChannelDataHandler = new WalmartListingProductEditChannelData(this);

            this.actions = Object.extend(this.actions, {
                duplicateAction: this.duplicateProducts.bind(this),
                movingAction: this.movingHandler.run.bind(this.movingHandler),
                deleteAndRemoveAction: this.actionHandler.deleteAndRemoveAction.bind(this.actionHandler),
                resetProductsAction: this.actionHandler.resetProductsAction.bind(this.actionHandler),

                changeTemplateCategoryIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateCategoryHandler.validateProductsForTemplateCategoryAssign(id, null)
                }).bind(this)
            });
        },

        // ---------------------------------------

        duplicateProducts: function () {
            this.scrollPageToTop();
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('walmart_listing/duplicateProducts'), {
                method: 'post',
                parameters: {
                    ids: this.getSelectedProductsString()
                },
                onSuccess: (function (transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);

                        if (response.type != 'error') {
                            this.unselectAllAndReload();
                        }

                    } catch (e) {
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }).bind(this)
            });
        },

        // ---------------------------------------
    });

});