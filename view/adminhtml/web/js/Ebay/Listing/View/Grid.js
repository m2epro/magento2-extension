define([
    'M2ePro/Listing/View/Grid',
    'M2ePro/Ebay/Listing/VariationProductManage',
    'M2ePro/Ebay/Listing/View/Action',
    'M2ePro/Ebay/Listing/View/Ebay/Bids'
], function () {

    window.EbayListingViewGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        selectedProductsIds: [],
        selectedCategoriesData: {},

        // ---------------------------------------

        prepareActions: function($super)
        {
            this.actionHandler = new EbayListingViewAction(this);

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

            this.variationProductManageHandler = new EbayListingVariationProductManage(this);
            this.listingProductBidsHandler = new EbayListingViewEbayBids(this);

            this.actions = Object.extend(this.actions, {

                editCategorySettingsAction: function(id) {
                    this.editCategorySettings(id);
                }.bind(this)

            });

        },

        massActionSubmitClick: function($super)
        {
            if (this.getSelectedProductsString() == '' || this.getSelectedProductsArray().length == 0) {
                this.alert(M2ePro.translator.translate('Please select the Products you want to perform the Action on.'));
                return;
            }
            $super();
        },

        // ---------------------------------------

        editCategorySettings: function(id)
        {
            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();

            new Ajax.Request(M2ePro.url.get('ebay_listing/getCategoryChooserHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    product_ids: this.selectedProductsIds.join(',')
                },
                onSuccess: function(transport) {

                    this.unselectAll();

                    var title = M2ePro.translator.translate('eBay Categories');

                    if (this.selectedProductsIds.length == 1) {
                        var productName = this.getProductNameByRowId(this.selectedProductsIds[0]);
                        title += '&nbsp;' + M2ePro.translator.translate('of Product') + '&nbsp;"' + productName + '"';
                    }

                    this.openPopUp(title, transport.responseText);

                    $('cancel_button').stopObserving('click').observe('click', function() {
                        this.popUp.modal('closeModal');
                    }.bind(this));

                    $('done_button').stopObserving('click').observe('click', function() {
                        if (!EbayListingProductCategorySettingsChooserObj.validate()) {
                            return;
                        }

                        this.selectedCategoriesData = EbayListingProductCategorySettingsChooserObj.getInternalData();
                        this.editSpecificSettings();
                    }.bind(this));
                }.bind(this)
            });
        },

        // ---------------------------------------

        editSpecificSettings: function()
        {
            var typeEbayMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN');

            new Ajax.Request(M2ePro.url.get('ebay_listing/getCategorySpecificHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    ids: this.selectedProductsIds.join(','),
                    category_mode: EbayListingProductCategorySettingsChooserObj.getSelectedCategory(typeEbayMain)['mode'],
                    category_value: EbayListingProductCategorySettingsChooserObj.getSelectedCategory(typeEbayMain)['value']
                },
                onSuccess: function(transport) {

                    var title = M2ePro.translator.translate('Specifics');

                    this.openPopUp(title, transport.responseText);

                    $('cancel_button').stopObserving('click').observe('click', function() { this.popUp.modal('closeModal'); }.bind(this));
                    $('done_button').stopObserving('click').observe('click', this.saveCategoryTemplate.bind(this));
                }.bind(this)
            });
        },

        // ---------------------------------------

        saveCategoryTemplate: function()
        {
            if (!EbayListingProductCategorySettingsSpecificObj.validate()) {
                return;
            }

            var categoryTemplateData = {};
            categoryTemplateData = Object.extend(categoryTemplateData, this.selectedCategoriesData);
            categoryTemplateData = Object.extend(categoryTemplateData, EbayListingProductCategorySettingsSpecificObj.getInternalData());

            new Ajax.Request(M2ePro.url.get('ebay_listing/saveCategoryTemplate'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    ids: this.selectedProductsIds.join(','),
                    template_category_data: Object.toJSON(categoryTemplateData)
                },
                onSuccess: function(transport) {
                    this.popUp.modal('closeModal');
                    this.getGridObj().doFilter();
                }.bind(this)
            });
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
                    self.selectedCategoriesData = {};

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