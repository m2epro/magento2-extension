define([
    'M2ePro/Grid',
    'M2ePro/Listing/View/Action'
], function () {

    window.ListingViewGrid = Class.create(Grid, {

        // ---------------------------------------

        productIdCellIndex: 1,
        productTitleCellIndex: 2,

        // ---------------------------------------

        initialize: function($super,gridId,listingId)
        {
            this.listingId = listingId;

            $super(gridId);
        },

        // ---------------------------------------

        getProductIdByRowId: function(rowId)
        {
            return this.getCellContent(rowId,this.productIdCellIndex);
        },

        // ---------------------------------------

        getSelectedItemsParts: function(maxProductsInPart)
        {
            var selectedProductsArray = this.getSelectedProductsArray();

            if (this.getSelectedProductsString() == '' || selectedProductsArray.length == 0) {
                return [];
            }

            maxProductsInPart = maxProductsInPart || this.getMaxProductsInPart();

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
            alert('abstract getMaxProductsInPart');
        },

        //########################################

        prepareActions: function()
        {
            this.actionHandler = new ListingViewAction(this);

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
        }

        // ---------------------------------------
    });

});