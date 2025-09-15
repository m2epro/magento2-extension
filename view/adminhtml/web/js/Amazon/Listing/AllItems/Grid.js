define([
    'mage/translate',
    'M2ePro/Grid',
    'M2ePro/Amazon/Listing/AllItems/Action',
    'M2ePro/Listing/Action/Processor'
], function ($t) {

    window.AmazonListingAllItemsGrid = Class.create(Grid, {

        // ---------------------------------------

        productIdCellIndex: 1,
        productTitleCellIndex: 2,

        // ---------------------------------------

        prepareActions: function()
        {
            const actionProcessor = new ListingActionProcessor(this);
            actionProcessor.setProgressBar('all_items_progress_bar');
            actionProcessor.setGridWrapper('all_items_content_container');
            actionProcessor.sizeOfParts = 10;

            this.actionHandler = new AmazonListingAllItemsAction(actionProcessor);

            this.actions = {
                listAction: this.actionHandler.listAction.bind(this.actionHandler),
                relistAction: this.actionHandler.relistAction.bind(this.actionHandler),
                reviseAction: this.actionHandler.reviseAction.bind(this.actionHandler),
                stopAction: this.actionHandler.stopAction.bind(this.actionHandler),
                stopAndRemoveAction: this.actionHandler.stopAndRemoveAction.bind(this.actionHandler),
            };
        },

        validateItemsForMassAction: function () {
            if (this.getSelectedProductsString() === '' || this.getSelectedProductsArray().length === 0) {
                this.alert($t('Please select the Products you want to perform the Action on.'));

                return false;
            }

            return true;
        },

        // ---------------------------------------

        /**
         * @param {int} maxProductsInPart
         * @returns {*[]}
         */
        getSelectedItemsParts: function(maxProductsInPart)
        {
            const selectedProductsArray = this.getSelectedProductsArray();
            if (this.getSelectedProductsString() === '' || selectedProductsArray.length === 0) {
                return [];
            }

            let result = [];
            for (let i = 0; i < selectedProductsArray.length; i++) {
                if (result.length === 0 || result[result.length - 1].length === maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length - 1][result[result.length - 1].length] = selectedProductsArray[i];
            }

            return result;
        },

        /**
         * @param {Grid.massActionSubmitClick} $super
         */
        massActionSubmitClick: function ($super) {
            if (!this.validateItemsForMassAction()) {
                return;
            }

            $super()
        }
    });
});
