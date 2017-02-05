define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Grid'
], function (modal, MagentoMessageObj) {

    window.EbayListingProductCategorySettingsModeCategoryGrid = Class.create(EbayListingProductCategorySettingsGrid, {

        // ---------------------------------------

        editCategories: function () {
            var url = M2ePro.url.get(
                'ebay_listing_product_category_settings/getChooserBlockHtml'
            );

            new Ajax.Request(url, {
                method: 'get',
                parameters: {
                    ids: this.getSelectedProductsString()
                },
                onSuccess: function (transport) {
                    this.openPopUp(M2ePro.translator.translate('Set eBay Categories'), transport.responseText);
                }.bind(this)
            });
        },

        // ---------------------------------------

        editPrimaryCategories: function () {
            this.editCategoriesByType(
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                true
            )
        },

        editStorePrimaryCategories: function () {
            this.editCategoriesByType(
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_MAIN'),
                false
            )
        },

        // ---------------------------------------

        openPopUp: function (title, content) {
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
                    $('m2epro-popup').update();
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
                        if (!EbayListingProductCategorySettingsChooserObj.validate()) {
                            return;
                        }

                        self.saveCategoriesData(EbayListingProductCategorySettingsChooserObj.getInternalData());
                        popup.modal('closeModal');
                    }
                }]
            }, popup);

            popup.modal('openModal');
        },

        // ---------------------------------------

        validate: function () {
            MagentoMessageObj.clear();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoModeCategoryValidate'), {
                method: 'post',
                onSuccess: function (transport) {

                    var response = transport.responseText.evalJSON();

                    if (response.validation == true) {
                        setLocation(M2ePro.url.get('ebay_listing_product_category_settings'));
                    } else {
                        MagentoMessageObj.addErrorMessage(response.message);
                    }

                }.bind(this)
            });
        },

        // ---------------------------------------

        confirm: function (config) {
            if (config.actions && config.actions.confirm) {
                config.actions.confirm();
            }
        }

        // ---------------------------------------
    });
});