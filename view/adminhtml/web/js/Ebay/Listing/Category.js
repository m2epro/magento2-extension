define([
    'M2ePro/Common'
], function () {

    window.EbayListingCategory = Class.create(Common, {

        // ---------------------------------------

        gridObj: null,
        selectedProductsIds: [],

        // ---------------------------------------

        initialize: function(gridObj)
        {
            this.gridObj = gridObj;
        },

        // ---------------------------------------


        editCategorySettings: function(id, categoryMode)
        {
            this.selectedProductsIds = id ? [id] : this.gridObj.getSelectedProductsArray();

            new Ajax.Request(M2ePro.url.get('ebay_listing/getCategoryChooserHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids   : this.selectedProductsIds.join(','),
                    account_id     : this.gridObj.accountId,
                    marketplace_id : this.gridObj.marketplaceId,
                    category_mode  : categoryMode,
                },
                onSuccess: function(transport) {
                    this.openPopUp(M2ePro.translator.translate('Category Settings'), transport.responseText);
                }.bind(this)
            });
        },

        saveCategorySettings: function()
        {
            this.initFormValidation('#modal_view_action_dialog');

            if (!jQuery('#modal_view_action_dialog').valid()) {
                return;
            }

            var self = EbayListingCategoryObj;
            var selectedCategories = EbayTemplateCategoryChooserObj.selectedCategories;
            var typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN');
            if (typeof selectedCategories[typeMain] !== 'undefined') {
                selectedCategories[typeMain]['specific'] = EbayTemplateCategoryChooserObj.selectedSpecifics;
            }

            new Ajax.Request(M2ePro.url.get('ebay_listing/saveCategoryTemplate'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids           : self.selectedProductsIds.join(','),
                    account_id             : self.gridObj.accountId,
                    marketplace_id         : self.gridObj.marketplaceId,
                    template_category_data : Object.toJSON(selectedCategories)
                },
                onSuccess: function (transport) {
                    self.cancelCategorySettings();
                }.bind(this)
            });
        },

        // ---------------------------------------


        cancelCategorySettings: function ()
        {
            var self = EbayListingCategoryObj;

            jQuery('#modal_view_action_dialog').modal('closeModal');
            self.gridObj.unselectAllAndReload();

            if (typeof self.cancelCallback == 'function') {
                self.cancelCallback();
            }
        },

        // ---------------------------------------

        openPopUp: function(title, content, params, popupId)
        {
            var self = this;
            params = params || {};
            popupId = popupId || 'modal_view_action_dialog';

            var modalDialogMessage = $(popupId);

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('form', {
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
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Save'),
                    attr: {id: 'done_button'},
                    class: 'action-primary action-accept',
                    click: function () {
                        self.saveCategorySettings();
                    }
                }],
                closed: function () {
                    self.selectedProductsIds = [];
                    self.selectedCategoriesData = {};

                    self.gridObj.unselectAllAndReload();

                    return true;
                }
            }, params));

            this.popUp.modal('openModal');

            try {
                modalDialogMessage.innerHTML = content;
                modalDialogMessage.innerHTML.evalScripts();
            } catch (ignored) {}
        },

        //----------------------------------------

        modeSameSubmitData: function(url)
        {
            this.initFormValidation('#edit_form');

            if (!jQuery('#edit_form').valid()) {
                return;
            }

            var selectedCategories = EbayTemplateCategoryChooserObj.selectedCategories;
            var typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN');
            if (typeof selectedCategories[typeMain] !== 'undefined') {
                selectedCategories[typeMain]['specific'] = EbayTemplateCategoryChooserObj.selectedSpecifics;
            }

            this.postForm(url, {category_data: Object.toJSON(selectedCategories)});
        }
    });

});
