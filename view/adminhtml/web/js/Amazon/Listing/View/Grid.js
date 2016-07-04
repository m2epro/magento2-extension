define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Listing/Moving',
    'M2ePro/Amazon/Listing/View/Action',
    'M2ePro/Amazon/Listing/View/Fulfillment',
    'M2ePro/Amazon/Listing/Product/Search',
    'M2ePro/Amazon/Listing/Product/Template/Description',
    'M2ePro/Amazon/Listing/Product/Variation/Manage'
], function (MessageObj) {

    window.AmazonListingViewGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getLogViewUrl: function (rowId) {
            return M2ePro.url.get('amazon_listing_product_log/index', {
                listing_product_id: rowId
            });
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 10;
        },

        // ---------------------------------------

        prepareActions: function($super)
        {
            this.actionHandler = new AmazonListingViewAction(this);

            this.actions = {
                listAction: this.actionHandler.listAction.bind(this.actionHandler),
                relistAction: this.actionHandler.relistAction.bind(this.actionHandler),
                reviseAction: this.actionHandler.reviseAction.bind(this.actionHandler),
                stopAction: this.actionHandler.stopAction.bind(this.actionHandler),
                stopAndRemoveAction: this.actionHandler.stopAndRemoveAction.bind(this.actionHandler),
                removeAction: this.actionHandler.removeAction.bind(this.actionHandler),
                previewItemsAction: this.actionHandler.previewItemsAction.bind(this.actionHandler),
                startTranslateAction: this.actionHandler.startTranslateAction.bind(this.actionHandler),
                stopTranslateAction: this.actionHandler.stopTranslateAction.bind(this.actionHandler)
            };

            this.movingHandler = new ListingMoving(this);
            this.productSearchHandler = new AmazonListingProductSearch(this);
            this.templateDescriptionHandler = new AmazonListingProductTemplateDescription(this);
            // TODO
            // this.templateShippingOverrideHandler = new CommonAmazonListingTemplateShippingOverrideHandler(this);
            this.variationProductManageHandler = new AmazonListingProductVariationManage(this);
            this.fulfillmentHandler = new AmazonListingViewFulfillment(this);
            // this.repricingHandler = new CommonAmazonRepricingHandler(this);

            this.actions = Object.extend(this.actions, {
                duplicateAction: this.duplicateProducts.bind(this),
                movingAction: this.movingHandler.run.bind(this.movingHandler),
                deleteAndRemoveAction: this.actionHandler.deleteAndRemoveAction.bind(this.actionHandler),

                assignTemplateDescriptionIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateDescriptionHandler.validateProductsForTemplateDescriptionAssign(id)
                }).bind(this),
                unassignTemplateDescriptionIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateDescriptionHandler.unassignFromTemplateDescrition(id)
                }).bind(this),

                // TODO
                // assignTemplateShippingOverrideIdAction: (function(id) {
                //     id = id || this.getSelectedProductsString();
                //     this.templateShippingOverrideHandler.openPopUp(id)
                // }).bind(this),
                // unassignTemplateShippingOverrideIdAction: (function(id) {
                //     id = id || this.getSelectedProductsString();
                //     this.templateShippingOverrideHandler.unassign(id)
                // }).bind(this),

                switchToAfnAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.fulfillmentHandler.switchToAFN(id);
                }).bind(this),
                switchToMfnAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.fulfillmentHandler.switchToMFN(id);
                }).bind(this),

                // TODO
                // addToRepricingAction: (function(id) {
                //     id = id || this.getSelectedProductsString();
                //     this.repricingHandler.addToRepricing(id);
                // }).bind(this),
                // showDetailsAction: (function(id) {
                //     id = id || this.getSelectedProductsString();
                //     this.repricingHandler.showDetails(id);
                // }).bind(this),
                // editRepricingAction: (function(id) {
                //     id = id || this.getSelectedProductsString();
                //     this.repricingHandler.editRepricing(id);
                // }).bind(this),
                // removeFromRepricingAction: (function(id) {
                //     id = id || this.getSelectedProductsString();
                //     this.repricingHandler.removeFromRepricing(id);
                // }).bind(this),

                assignGeneralIdAction: (function() { this.productSearchHandler.searchGeneralIdAuto(this.getSelectedProductsString())}).bind(this),
                newGeneralIdAction: (function() { this.productSearchHandler.addNewGeneralId(this.getSelectedProductsString())}).bind(this),
                unassignGeneralIdAction: (function() { this.productSearchHandler.unmapFromGeneralId(this.getSelectedProductsString())}).bind(this)

            });
        },

        // ---------------------------------------

        duplicateProducts: function () {
            this.scrollPageToTop();
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing/duplicateProducts'), {
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

        unassignTemplateDescriptionIdActionConfrim: function (id)
        {
            if (!this.confirm()) {
                return;
            }

            this.templateDescriptionHandler.unassignFromTemplateDescrition(id)
        },

        // ---------------------------------------

        unassignTemplateShippingOverrideIdActionConfrim: function (id)
        {
            if (!this.confirm()) {
                return;
            }

            this.templateShippingOverrideHandler.unassign(id)
        }

        // ---------------------------------------
    });

});