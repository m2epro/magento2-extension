define([
    'M2ePro/Grid'
], function () {

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