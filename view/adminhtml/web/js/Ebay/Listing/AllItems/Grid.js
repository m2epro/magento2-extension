define([
    'M2ePro/Grid',
    'M2ePro/Ebay/Listing/AllItems/Action',
    'M2ePro/Listing/Action/Processor'
], function () {

    window.EbayListingAllItemsGrid = Class.create(Grid, {

        // ---------------------------------------

        selectedProductsIds: [],
        productIdCellIndex: 1,
        productTitleCellIndex: 2,

        // ---------------------------------------

        getProductIdByRowId: function(rowId)
        {
            return this.getCellContent(rowId,this.productIdCellIndex);
        },

        // ---------------------------------------

        prepareActions: function()
        {
            var actionProcessor = new ListingActionProcessor(this);
            actionProcessor.setProgressBar('all_items_progress_bar');
            actionProcessor.setGridWrapper('all_items_content_container');

            this.actionHandler = new EbayListingAllItemsAction(actionProcessor);

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
                this.alert(M2ePro.translator.translate('please_select_the_products'));

                return false;
            }

            return true;
        },

        // ---------------------------------------

        getSelectedItemsParts: function(maxProductsInPart)
        {
            var selectedProductsArray = this.getSelectedProductsArray();

            if (this.getSelectedProductsString() == '' || selectedProductsArray.length === 0) {
                return [];
            }

            var result = [];
            for (var i = 0; i < selectedProductsArray.length; i++) {
                if (result.length === 0 || result[result.length - 1].length == maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length - 1][result[result.length - 1].length] = selectedProductsArray[i];
            }

            return result;
        },

        // ---------------------------------------

        getComponent: function()
        {
            return 'ebay';
        },

        // ---------------------------------------

        openPopUp: function(title, content, params, popupId)
        {
            var self = this;
            params = params || {};
            popupId = popupId || 'modal_view_action_dialog';

            var modalDialogMessage = $(popupId);

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('div', {
                    id: popupId
                });
            }

            modalDialogMessage.innerHTML = '';

            this.popUp = jQuery(modalDialogMessage).modal(Object.extend({
                title: title,
                type: 'slide',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    attr: {id: 'cancel_button'},
                    class: 'action-dismiss',
                    click: function () {}
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    attr: {id: 'done_button'},
                    class: 'action-primary action-accept forward',
                    click: function () {}
                }],
                closed: function() {
                    self.selectedProductsIds = [];

                    self.getGridObj().reload();

                    return true;
                }
            }, params));

            this.popUp.modal('openModal');

            try {
                modalDialogMessage.innerHTML = content;
                modalDialogMessage.innerHTML.evalScripts();
            } catch (ignored) {}
        }

        // ---------------------------------------
    });

});
