define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Grid'
], function (jQuery, modal, MagentoMessageObj) {

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
                resetCategoriesAction: function (id) {
                    this.resetCategories(id);
                }.bind(this),
                removeItemAction: function (id) {
                    var ids = id ? [id] : this.getSelectedProductsArray();
                    this.removeItems(ids);
                }.bind(this)

            });
        },

        // ---------------------------------------

        getSuggestedCategories: function (id) {
            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();
            this.unselectAll();

            if (id && !confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            EbayListingProductCategorySettingsModeProductSuggestedSearchObj.search(
                this.selectedProductsIds.join(','), function (searchResult) {
                    this.getGridObj().doFilter();
                    this.selectedProductsIds = [];

                    MagentoMessageObj.clear();

                    if (searchResult.failed > 0) {
                        MagentoMessageObj.addErrorMessage(
                            M2ePro.translator.translate('eBay could not assign Categories for %product_title% Products.')
                                .replace('%product_title%', searchResult.failed)
                        );
                    } else if (searchResult.succeeded > 0) {
                        MagentoMessageObj.addSuccessMessage(
                            M2ePro.translator.translate('Suggested Categories were successfully Received for %product_title% Product(s).')
                                .replace('%product_title%', searchResult.succeeded)
                        );
                    }
                }.bind(this)
            );
        },

        getSuggestedCategoriesForAll: function () {
            var gridIds = this.getGridMassActionObj().getGridIds().split(',');
            if (gridIds.length > 100 && !confirm('Are you sure?')) {
                return;
            }

            this.getGridMassActionObj().selectAll();
            this.getSuggestedCategories();
        },

        // ---------------------------------------

        editPrimaryCategories: function () {
            this.editCategoriesByType(M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN'))
        },

        editStorePrimaryCategories: function () {
            this.editCategoriesByType(M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_STORE_MAIN'))
        },

        // ---------------------------------------

        editCategories: function (id) {
            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/getChooserBlockHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    ids: this.selectedProductsIds.join(',')
                },
                onSuccess: function (transport) {

                    var title = M2ePro.translator.translate('Set eBay Category for Product(s)');

                    if (this.selectedProductsIds.length == 1) {
                        var productName = this.getProductNameByRowId(this.selectedProductsIds[0]);
                        title += '&nbsp;"' + productName + '"';
                    }

                    this.showChooserPopup(title, transport.responseText);
                }.bind(this)
            });
        },

        // ---------------------------------------

        resetCategories: function (id) {
            if (id && !confirm('Are you sure?')) {
                return;
            }

            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoSuggestedReset'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids: this.selectedProductsIds.join(',')
                },
                onSuccess: function (transport) {
                    this.getGridObj().doFilter();
                    this.unselectAll();
                }.bind(this)
            });
        },

        // ---------------------------------------

        showChooserPopup: function (title, content) {
            var self = this;

            if (!$('m2epro-popup')) {
                $('html-body').insert({bottom: '<div id="m2epro-popup"></div>'});
            }

            $('m2epro-popup').update();
            $('m2epro-popup').insert(content);

            var popup = jQuery('#m2epro-popup');

            modal({
                title: title,
                type: 'slide',
                closed: function(){
                    self.selectedProductsIds = [];

                    return true;
                },
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    click: function () {
                        popup.modal('closeModal');
                        self.unselectAll();
                    }
                },{
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action primary',
                    click: function () {
                        if (!self.validate()) {
                            return;
                        }

                        self.saveCategoriesData(EbayListingProductCategorySettingsChooserObj.getInternalData());
                    }
                }]
            }, popup);

            popup.modal('openModal');
        },

        // ---------------------------------------

        nextStep: function () {
            var self = this;

            MagentoMessageObj.clear();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoModeProductValidate'), {
                method: 'get',
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

                    var popup = jQuery('#next_step_warning_popup_content');

                    modal({
                        title: M2ePro.translator.translate('Set eBay Category'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary',
                            click: function () {
                                this.closeModal();
                            }
                        },{
                            text: M2ePro.translator.translate('Continue'),
                            class: 'primary forward',
                            id: 'save_popup_button',
                            click: function () {
                                this.closeModal();
                                setLocation(M2ePro.url.get('ebay_listing_product_category_settings'));
                            }
                        }]
                    }, popup);

                    popup.modal('openModal');

                    // $('total_count').update(response['total_count']);
                    // $('failed_count').update(response['failed_count']);

                }.bind(this)
            });
        },

        // ---------------------------------------

        removeItems: function (ids) {
            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            var url = M2ePro.url.get('ebay_listing_product_category_settings/stepTwoDeleteProductsModeProduct');
            new Ajax.Request(url, {
                method: 'post',
                parameters: {
                    products_ids: ids.join(',')
                },
                onSuccess: function () {
                    this.unselectAllAndReload();
                }.bind(this)
            });
        },

        // ---------------------------------------

        confirm: function ($super) {
            var action = '';

            $$('select#' + this.gridId + '_massaction-select option').each(function (o) {
                if (o.selected && o.value != '') {
                    action = o.value;
                }
            });

            if (action == 'removeItem' ||
                action == 'editCategories' ||
                action == 'editPrimaryCategories' ||
                action == 'editStorePrimaryCategories') {
                return true;
            }

            var result = $super();
            if (action == 'getSuggestedCategories' && !result) {
                this.unselectAll();
            }

            return result;
        },

        // ---------------------------------------

        validate: function () {
            if ($$('.main-store-empty-advice').length <= 0) {
                return true;
            }

            $$('.main-store-empty-advice')[0].hide();

            var primary = $('magento_block_ebay_listing_category_chooser_store_primary_not_selected') == null;
            var secondary = $('magento_block_ebay_listing_category_chooser_store_secondary_not_selected') == null;

            if (primary == false && secondary == true) {
                $$('.main-store-empty-advice')[0].show();
                return false;
            }

            return true;
        }

        // ---------------------------------------
    });

});