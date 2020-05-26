define([
    'M2ePro/Plugin/Messages',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Grid'
], function (MagentoMessageObj, modal) {

    window.EbayListingProductCategorySettingsGrid = Class.create(Grid, {

        // ---------------------------------------

        prepareActions: function () {
            this.actions = {

                editCategoriesAction: function (id) {

                    id && this.selectByRowId(id);
                    this.editCategories();

                }.bind(this),

                editPrimaryCategoriesAction: function (id) {

                    id && this.selectByRowId(id);
                    this.editPrimaryCategories();

                }.bind(this),

                editStorePrimaryCategoriesAction: function (id) {

                    id && this.selectByRowId(id);
                    this.editStorePrimaryCategories();

                }.bind(this)

            };
        },

        // ---------------------------------------

        editPrimaryCategories: function () {
            alert('abstract editPrimaryCategories');
        },

        editStorePrimaryCategories: function () {
            alert('abstract editPrimaryCategories');
        },

        editCategoriesByType: function (type, validationRequired) {
            validationRequired = validationRequired || false;

            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/getChooserBlockHtml'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    products_ids: this.getSelectedProductsString()
                },
                onSuccess: function (transport) {

                    var temp = document.createElement('div');
                    temp.innerHTML = transport.responseText;
                    temp.innerHTML.evalScripts();

                    setTimeout(function go() {
                        if (typeof EbayListingProductCategorySettingsChooserObj == 'undefined') {
                            setTimeout(go, 50);
                        } else {
                            EbayListingProductCategorySettingsChooserObj.showEditPopUp(type);

                            validationRequired && (EbayListingProductCategorySettingsChooserObj.categoriesRequiringValidation[type] = true);

                            EbayListingProductCategorySettingsChooserObj.doneCallback = function () {
                                self.saveCategoriesData(EbayListingProductCategorySettingsChooserObj.getInternalDataByType(type));

                                EbayListingProductCategorySettingsChooserObj.doneCallback = null;
                                EbayListingProductCategorySettingsChooserObj.cancelCallback = null;

                                validationRequired && (delete EbayListingProductCategorySettingsChooserObj.categoriesRequiringValidation[type]);
                            };

                            EbayListingProductCategorySettingsChooserObj.cancelCallback = function () {
                                self.unselectAll();

                                EbayListingProductCategorySettingsChooserObj.doneCallback = null;
                                EbayListingProductCategorySettingsChooserObj.cancelCallback = null;

                                validationRequired && (delete EbayListingProductCategorySettingsChooserObj.categoriesRequiringValidation[type]);
                            };
                        }
                    }, 50);

                }.bind(this)
            });
        },

        saveCategoriesData: function (templateData) {
            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoSaveToSession'), {
                method: 'post',
                parameters: {
                    products_ids: this.getSelectedProductsString(),
                    template_data: Object.toJSON(templateData)
                },
                onSuccess: function (transport) {

                    this.unselectAll();
                    this.getGridObj().doFilter();

                    jQuery('#m2epro-popup').modal('closeModal');
                }.bind(this)
            });
        },

        // ---------------------------------------

        completeCategoriesDataStep: function () {
            var self = this;
            MagentoMessageObj.clear();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoModeValidate'), {
                method: 'post',
                asynchronous: true,
                parameters: {},
                onSuccess: function (transport) {

                    var response = transport.responseText.evalJSON();

                    if (response['validation']) {
                        return setLocation(M2ePro.url.get('ebay_listing_product_category_settings'));
                    }

                    if (response['message']) {
                        return MagentoMessageObj.addErrorMessage(response['message']);
                    }

                    $('next_step_warning_popup_content').select('span.total_count').each(function(el){
                        $(el).update(response['total_count']);
                    });

                    $('next_step_warning_popup_content').select('span.failed_count').each(function(el){
                        $(el).update(response['failed_count']);
                    });

                    var popup = jQuery('#next_step_warning_popup_content');

                    modal({
                        title: M2ePro.translator.translate('Set eBay Category'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                this.closeModal();
                            }
                        },{
                            text: M2ePro.translator.translate('Continue'),
                            class: 'action-primary action-accept forward',
                            id: 'save_popup_button',
                            click: function () {
                                this.closeModal();
                                setLocation(M2ePro.url.get('ebay_listing_product_category_settings'));
                            }
                        }]
                    }, popup);

                    popup.modal('openModal');

                }.bind(this)
            });
        },

        // ---------------------------------------

        editCategories: function () {
            alert('abstract editCategories');
        },

        // ---------------------------------------

        getComponent: function () {
            return 'ebay';
        }

        // ---------------------------------------
    });

});