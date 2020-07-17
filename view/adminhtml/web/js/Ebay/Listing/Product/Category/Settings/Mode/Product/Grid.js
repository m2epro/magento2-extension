define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Grid'
], function (jQuery, modal, MessageObj) {

    window.EbayListingProductCategorySettingsModeProductGrid = Class.create(EbayListingProductCategorySettingsGrid, {

        // ---------------------------------------

        productIdCellIndex: 1,
        productTitleCellIndex: 2,

        // ---------------------------------------

        prepareActions: function ($super) {
            $super();

            this.actions = Object.extend(this.actions, {

                getSuggestedCategoriesAction: function (id) {
                    this.getSuggestedCategories(id);
                }.bind(this),
                removeItemAction: function (id) {
                    var ids = id ? [id] : this.getSelectedProductsArray();
                    this.removeItems(ids);
                }.bind(this)

            });
        },

        // ---------------------------------------

        getSuggestedCategories: function (id) {
            var self = this,
                confirmAction;

            self.selectedProductsIds = id ? [id] : self.getSelectedProductsArray();
            self.unselectAll();

            confirmAction = function() {
                EbayListingProductCategorySettingsModeProductSuggestedSearchObj.search(
                    self.selectedProductsIds.join(','), function (searchResult) {
                        self.getGridObj().doFilter();
                        self.selectedProductsIds = [];

                        MessageObj.clear();

                        if (searchResult.failed > 0) {
                            MessageObj.addError(
                                M2ePro.translator.translate('Suggested Categories were not assigned.')
                                    .replace('%product_title%', searchResult.failed)
                            );
                        } else if (searchResult.succeeded > 0) {
                            MessageObj.addError(
                                M2ePro.translator.translate('Suggested Categories were assigned.')
                                    .replace('%product_title%', searchResult.succeeded)
                            );
                        }
                    }
                );
            };

            if (id) {
                self.confirm({
                    actions: {
                        confirm: function () {
                            confirmAction();
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            } else {
                confirmAction();
            }
        },

        getSuggestedCategoriesForAll: function () {
            var self = this,
                confirmAction,
                gridIds = self.getGridMassActionObj().getGridIds().split(',');

            confirmAction = function() {
                self.getGridMassActionObj().selectAll();
                self.getSuggestedCategories();
            };

            if (gridIds.length > 100) {
                self.confirm({
                    actions: {
                        confirm: function () {
                            confirmAction();
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            } else {
                confirmAction();
            }
        },

        // ---------------------------------------

        removeItems: function (id) {
            var self = this,
                confirmAction;

            confirmAction = function() {
                self.selectedProductsIds = id ? [id] : self.getSelectedProductsArray();

                var url = M2ePro.url.get('ebay_listing_product_category_settings/stepTwoDeleteProductsModeProduct');
                new Ajax.Request(url, {
                    method: 'post',
                    parameters: {
                        products_ids: self.selectedProductsIds.join(',')
                    },
                    onSuccess: function () {
                        self.unselectAllAndReload();
                    }
                });
            };

            if (id) {
                self.confirm({
                    actions: {
                        confirm: function () {
                            confirmAction();
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            } else {
                confirmAction();
            }
        },

        // ---------------------------------------

        confirm: function ($super, config) {
            var action = '';

            $$('select#' + this.gridId + '_massaction-select option').each(function (o) {
                if (o.selected && o.value != '') {
                    action = o.value;
                }
            });

            $super(config);
        },

        // ---------------------------------------
    });

});