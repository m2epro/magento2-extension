define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Grid'
], function (jQuery, modal) {

    window.EbayListingProductCategorySettingsSpecificGrid = Class.create(Grid, {

        categoriesData: {},
        marketplaceId: null,
        selectedCategoryHash: null,

        // ---------------------------------------

        prepareActions: function () {

            this.actions = {
                editSpecificsAction: function (categoryHash) {
                    this.editSpecifics(categoryHash);
                }.bind(this)
            };
        },

        // ---------------------------------------

        editSpecifics: function(categoryHash)
        {
            var typeMain = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN'),
                selectedCategory = this.categoriesData[categoryHash][typeMain];

            var specifics = {};
            if (typeof selectedCategory['specific'] !== 'undefined') {
                specifics = selectedCategory['specific'];
            }

            new Ajax.Request(M2ePro.url.get('ebay_category/getCategorySpecificHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    marketplace_id     : this.marketplaceId,
                    selected_specifics : Object.toJSON(specifics),
                    template_id        : selectedCategory['template_id'],
                    category_mode      : selectedCategory['mode'],
                    category_value     : selectedCategory['value']
                },
                onSuccess: function(transport) {
                    this.selectedCategoryHash = categoryHash;
                    this.openPopUp(transport.responseText, categoryHash);
                }.bind(this)
            });
        },

        openPopUp: function (html, categoryId) {
            var self = this;
            if ($('chooser_container_specific')) {
                $('chooser_container_specific').remove();
            }

            $('html-body').insert({bottom: html});

            var content = jQuery('#chooser_container_specific');

            modal({
                title: M2ePro.translator.translate('Specifics'),
                type: 'slide',
                buttons: [{
                    text : M2ePro.translator.translate('Cancel'),
                    click : function () {
                        this.closeModal();
                    }
                },{
                    class : 'action primary',
                    text : M2ePro.translator.translate('Reset'),
                    click : function () {
                        EbayTemplateCategorySpecificsObj.resetSpecifics();
                    }
                },{
                    class : 'action primary',
                    text : M2ePro.translator.translate('Save'),
                    click : function () {
                        self.confirmSpecifics();
                    }
                },{
                }]
            }, content);

            content.modal('openModal');
        },

        confirmSpecifics: function()
        {
            this.initFormValidation('#edit_specifics_form');
            if (!jQuery('#edit_specifics_form').valid()) {
                return;
            }

            var self = EbayListingProductCategorySettingsSpecificGridObj,
                typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                selectedCategory = this.categoriesData[this.selectedCategoryHash][typeMain];

            selectedCategory['specific'] = EbayTemplateCategorySpecificsObj.collectSpecifics();

            new Promise(function (resolve, reject) {
                new Ajax.Request(M2ePro.url.get('ebay_category/getSelectedCategoryDetails'), {
                    method: 'post',
                    parameters: {
                        marketplace_id : self.marketplaceId,
                        account_id     : null,
                        value          : selectedCategory['value'],
                        mode           : selectedCategory['mode'],
                        category_type  : typeMain
                    },
                    onSuccess: function(transport) {

                        var response = transport.responseText.evalJSON();

                        if (response.is_custom_template === null) {
                            selectedCategory.template_id = null;
                            selectedCategory.is_custom_template = '0';
                        } else {
                            selectedCategory.template_id = null;
                            selectedCategory.is_custom_template = '1';
                        }

                        return resolve();
                    }
                });
            })
            .then(function() {
                var templateData = {};
                templateData[typeMain] = selectedCategory;

                return new Promise(function (resolve, reject) {
                    new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoSaveToSession'), {
                        method: 'post',
                        parameters: {
                            products_ids  : self.categoriesData[self.selectedCategoryHash]['listing_products_ids'].join(','),
                            template_data : Object.toJSON(templateData)
                        },
                        onSuccess: function(transport) {
                            return resolve(transport);
                        }
                    });
                });
            })
            .then(function() {
                jQuery('#chooser_container_specific').modal('closeModal');
                self.getGridObj().reload();
            });
        },

        // ---------------------------------------

        setCategoriesData: function(data)
        {
            this.categoriesData = data;
        },

        setMarketplaceId: function(id)
        {
            this.marketplaceId = id;
        }

        // ---------------------------------------
    });

});