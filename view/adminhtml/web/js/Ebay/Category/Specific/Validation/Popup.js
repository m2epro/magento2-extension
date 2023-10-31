define([
    'M2ePro/Plugin/Storage',
], function (localStorage) {
    window.EbayCategorySpecificValidationPopupClass = Class.create({
        templateCategoryIdLocalStorageKey: 'specific_validate_category_id',
        closePopupCallback: undefined,
        closePopupCallbackArguments: [],
        templateCategoryId: undefined,

        initialize: function () {
            if (this.hasTemplateCategoryId()) {
                let template_id = this.getAndRemoveTemplateCategoryId();
                this.openForTemplateCategoryId(template_id);
            }
        },

        setTemplateCategoryId: function (templateCategoryId) {
            console.log('set: ' + templateCategoryId)
            localStorage.set(this.templateCategoryIdLocalStorageKey, templateCategoryId)
        },


        hasTemplateCategoryId: function () {
            console.log('has: ' + !!localStorage.get(this.templateCategoryIdLocalStorageKey))
            return !!localStorage.get(this.templateCategoryIdLocalStorageKey)
        },

        getAndRemoveTemplateCategoryId: function () {
            console.log('get and remove: ' + localStorage.get(this.templateCategoryIdLocalStorageKey))
            let value = localStorage.get(this.templateCategoryIdLocalStorageKey);
            localStorage.remove(this.templateCategoryIdLocalStorageKey)

            return value;
        },

        open: function (listingProductIds) {
            var self = this;
            new Ajax.Request(M2ePro.url.get('ebay_category_specific_validation_modal_open'), {
                method: 'post',
                parameters: {
                    listing_product_ids: listingProductIds
                },
                onSuccess: function (transport) {
                    if (transport.responseText === '') {
                        self.executeClosePopupCallback();
                        return;
                    }

                    var modalDialog = $('modal_ebay_category_specific_validation');
                    if (!modalDialog) {
                        modalDialog = new Element('div', {
                            id: 'modal_ebay_category_specific_validation'
                        });
                    } else {
                        modalDialog.innerHTML = '';
                    }

                    window.ebayCategorySpecificValidation = jQuery(modalDialog).modal({
                        title: M2ePro.translator.translate('modal_title'),
                        type: 'slide',
                        buttons: [],
                        modalCloseBtnHandler: function () {
                            new Ajax.Request(M2ePro.url.get('ebay_category_specific_validation_modal_close'), {
                                method: 'post',
                            });

                            self.executeClosePopupCallback();
                            window.ebayCategorySpecificValidation.modal('closeModal');
                        }
                    });
                    window.ebayCategorySpecificValidation.modal('openModal');

                    modalDialog.insert(transport.responseText);
                }
            });
        },

        openForTemplateCategoryId: function (templateCategoryId) {
            var self = this;
            new Ajax.Request(M2ePro.url.get('ebay_category_specific_validation_listing_product_ids_by_product_type_id'), {
                method: 'post',
                parameters: {
                    template_category_id: templateCategoryId
                },
                onSuccess: function (transport) {
                    var listingProductIds = transport.responseText.evalJSON();
                    if (listingProductIds.length === 0) {
                        self.executeClosePopupCallback();
                        return;
                    }

                    self.open(listingProductIds.join(','));
                }
            });
        },

        executeClosePopupCallback: function () {
            var self = this;
            if (typeof this.closePopupCallback !== 'undefined') {
                setTimeout(function () {
                    self.closePopupCallback(...self.closePopupCallbackArguments);
                }, 1)
            }
        }
    });

    window.EbayCategorySpecificValidationPopup = new EbayCategorySpecificValidationPopupClass();
});
