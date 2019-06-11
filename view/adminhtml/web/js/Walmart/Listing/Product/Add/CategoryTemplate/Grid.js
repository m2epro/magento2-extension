define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Walmart/Listing/View/Action',
    'M2ePro/Walmart/Listing/Product/Template/Category'
], function (modal, MessageObj) {

    window.WalmartListingProductAddCategoryTemplateGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getComponent: function ()
        {
            return 'Walmart';
        },

        // ---------------------------------------

        getMaxProductsInPart: function ()
        {
            return 1000;
        },

        // ---------------------------------------

        prepareActions: function ($super)
        {
            $super();
            this.actionHandler = new WalmartListingViewAction(this);
            this.templateCategoryHandler = new WalmartListingProductTemplateCategory(this);

            this.actions = Object.extend(this.actions, {

                duplicateAction: this.duplicateProducts.bind(this),

                setCategoryTemplateAction: (function() {
                    this.templateCategoryHandler.validateProductsForTemplateCategoryAssign(this.getSelectedProductsString(), null);
                }).bind(this),

                setCategoryTemplateByCategoryAction: (function() {
                    this.templateCategoryHandler.validateProductsForTemplateCategoryAssign(this.getSelectedProductsStringFromCategory(), this.getSelectedCategories());
                }).bind(this)
            });
        },

        // ---------------------------------------

        parseResponse: function (response)
        {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        duplicateProducts: function()
        {
            this.scrollPageToTop();
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('walmart_listing/duplicateProducts'), {
                method: 'post',
                parameters: {
                    component: this.getComponent(),
                    ids: this.getSelectedProductsString()
                },
                onSuccess: (function(transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);

                        if (response.type != 'error') {
                            this.unselectAllAndReload();
                        }

                    } catch (e) {
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }).bind(this)
            });
        },

        // ---------------------------------------

        afterInitPage: function ($super)
        {
            $super();
        },

        // ---------------------------------------

        setCategoryTemplateRowAction: function(id)
        {
            this.templateCategoryHandler.validateProductsForTemplateCategoryAssign(id, null);
        },

        // ---------------------------------------

        setCategoryTemplateByCategoryRowAction: function(id)
        {
            this.templateCategoryHandler.validateProductsForTemplateCategoryAssign(this.getSelectedProductsStringFromCategory(id), id);
        },

        // ---------------------------------------

        getSelectedCategories: function(categoryIds)
        {
            return categoryIds || this.getGridMassActionObj().checkedString;
        },

        getSelectedProductsStringFromCategory: function (categoryIds)
        {
            var productsIdsStr = '';

            categoryIds = categoryIds || this.getGridMassActionObj().checkedString;
            categoryIds = explode(',', categoryIds);

            categoryIds.each(function (categoryId) {

                if (productsIdsStr != '') {
                    productsIdsStr += ',';
                }
                productsIdsStr += $('products_ids_' + categoryId).value;
            });

            return productsIdsStr;
        },

        // ---------------------------------------

        mapToTemplateCategory: function (el, templateId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_add/assignByMagentoCategorySaveCategory'), {
                method: 'post',
                parameters: {
                    template_id: templateId,
                    magento_categories_ids: ListingGridHandlerObj.templateCategoryHandler.templateCategoryPopup.magentoCategoriesIds
                },
                onSuccess: function(transport) {

                    new Ajax.Request(M2ePro.url.get('walmart_listing_product_template_category/assign'), {
                        method: 'post',
                        parameters: {
                            products_ids: ListingGridHandlerObj.templateCategoryHandler.templateCategoryPopup.productsIds,
                            template_id:  templateId
                        },
                        onSuccess: function(transport) {
                            if (!transport.responseText.isJSON()) {
                                alert(transport.responseText);
                                return;
                            }

                            self.templateCategoryHandler.gridHandler.unselectAllAndReload();

                            var response = transport.responseText.evalJSON();

                            if (response.messages.length > 0) {
                                MessageObj.clear();
                                response.messages.each(function(msg) {
                                    MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](msg);
                                });
                            }
                        }
                    });
                }
            });

            ListingGridHandlerObj.templateCategoryHandler.templateCategoryPopup.modal('closeModal');
        },

        // ---------------------------------------

        completeCategoriesDataStep: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('walmart_listing_product_add/checkCategoryTemplateProducts'), {
                method: 'post',
                onSuccess: function(transport) {

                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    if (response['validation']) {
                        return setLocation(response['next_step_url']);
                    }

                    if (response['message']) {
                        MessageObj.clearAll();
                        return MessageObj.addErrorMessage(response['message']);
                    }

                    if ($('category_template_warning_popup_content')) {
                        $('category_template_warning_popup_content').remove();
                    }
                    $('html-body').insert({bottom: response.html});

                    $('category_template_warning_popup_content').select('span.total_count').each(function(el){
                        $(el).update(response['total_count']);
                    });

                    $('category_template_warning_popup_content').select('span.failed_count').each(function(el){
                        $(el).update(response['failed_count']);
                    });

                    self.skipPopup = jQuery('#category_template_warning_popup_content');

                    modal({
                        title: M2ePro.translator.translate('templateCategoryPopupTitle'),
                        type: 'popup',
                        buttons: [ {
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.skipPopup.modal('closeModal');
                            }
                        }, {
                            text: M2ePro.translator.translate('Continue'),
                            class: 'action-primary action-accept',
                            click: function () {
                                setLocation(response['next_step_url']);
                                self.skipPopup.modal('closeModal');
                            }
                        }]
                    }, self.skipPopup);

                    self.skipPopup.modal('openModal');

                }.bind(this)
            });
        },

        // ---------------------------------------
    });

});