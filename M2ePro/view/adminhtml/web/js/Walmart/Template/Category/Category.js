define([
   'jquery',
   'underscore',
   'M2ePro/Walmart/Template/Edit'
], function (jQuery, _) {

    window.WalmartTemplateCategory = Class.create(WalmartTemplateEdit, {

        // ---------------------------------------

        initialize: function()
        {
            var self = this;

            self.specificHandler = null;

            // ---------------------------------------
            self.categoryInfo = {};

            self.categoryPathHiddenInput = $('category_path');
            self.categoryNodeIdHiddenInput = $('browsenode_id');

            self.categoryProductDataNickHiddenInput = $('product_data_nick');
            // ---------------------------------------

            self.productDataNicksInfo = {};
            self.variationThemes      = [];

            // ---------------------------------------

            self.initValidation();
        },

        initObservers: function()
        {
            $('marketplace_id').observe('change', WalmartTemplateCategoryObj.onChangeMarketplace);

            $('edit_category_link').observe('click', WalmartTemplateCategoryObj.onClickEditCategory);

            $('product_data_nick').observe('change', WalmartTemplateCategoryObj.onProductDataNickChange)
                .simulate('change');
        },

        initValidation: function()
        {
            var self = this;

            self.setValidationCheckRepetitionValue('M2ePro-category-template-title',
                                                   M2ePro.translator.translate('The specified Title is already used for another Policy. Policy Title must be unique.'),
                                                   'Walmart\\Template\\Category', 'title', 'id',
                                                   M2ePro.formData.id);

            jQuery.validator.addMethod('M2ePro-validate-category', function(value) {
                return $('category_path').value != '';
            }, M2ePro.translator.translate('You should select Category and Product Type first'));
        },

        // ---------------------------------------

        setSpecificHandler: function(object)
        {
            var self = this;
            self.specificHandler = object;
        },

        // ---------------------------------------

        checkMarketplaceSelection: function()
        {
            return $('marketplace_id').value != '';
        },

        //########################################

        duplicateClick: function($super, $headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-category-template-title',
                                                   M2ePro.translator.translate('The specified Title is already used for another Policy. Policy Title must be unique.'),
                                                   'Walmart\\Template\\Category', 'title', '','');

            if (M2ePro.customData.category_locked) {

                M2ePro.customData.category_locked = false;
                this.hideCategoryWarning('category_locked_warning_message');
                $('edit_category_link').show();

                $('product_data_nick_select').removeAttribute('disabled');
            }

            if (M2ePro.customData.marketplace_locked) {

                M2ePro.customData.marketplace_locked = false;
                $('marketplace_locked_warning_message').remove();

                if (!M2ePro.customData.marketplace_force_set) {
                    $('marketplace_hidden_input').remove();
                    $('marketplace_id').removeAttribute('disabled');
                }
            }

            if (M2ePro.customData.new_asin_switcher_locked) {

                M2ePro.customData.new_asin_switcher_locked = false;
                $('new_asin_locked_warning_message').remove();

                if (!M2ePro.customData.new_asin_switcher_force_set) {
                    $('new_asin_accepted_hidden_input').remove();
                    $('new_asin_accepted').removeAttribute('disabled');
                }
            }

            $super($headId, M2ePro.translator.translate('Add Category Policy'));
        },

        // ---------------------------------------

        saveClick: function($super, url, confirmText, templateNick)
        {
            var self = WalmartTemplateCategoryObj;

            self.specificHandler.prepareSpecificsDataToPost();
            $super(url, confirmText, templateNick);
        },

        saveAndEditClick: function($super, url, confirmText, templateNick)
        {
            var self = WalmartTemplateCategoryObj;

            self.specificHandler.prepareSpecificsDataToPost();
            $super(url, undefined, confirmText, templateNick);
        },

        saveAndCloseClick: function($super, confirmText, templateNick)
        {
            var self = WalmartTemplateCategoryObj;

            self.specificHandler.prepareSpecificsDataToPost();
            $super(confirmText, templateNick);
        },

        //########################################

        onChangeMarketplace: function()
        {
            var self = WalmartTemplateCategoryObj;
            self.resetCategory();
        },

        onClickEditCategory: function()
        {
            var self = WalmartTemplateCategoryObj;

            if (!self.checkMarketplaceSelection()) {
                return alert(M2ePro.translator.translate('You should select Marketplace first.'));
            }

            WalmartTemplateCategoryCategoriesChooserObj.showEditCategoryPopUp();
        },

        onProductDataNickChange: function()
        {
            $('magento_block_template_category_edit_specifics').hide();
            if (this.value != '') {
                $('magento_block_template_category_edit_specifics').show();
            }
        },

        // ---------------------------------------

        setCategory: function(categoryInfo, notSetProductTypeForceIfOnlyOne)
        {
            var self = this;
            notSetProductTypeForceIfOnlyOne = notSetProductTypeForceIfOnlyOne || false;

            this.categoryInfo = categoryInfo;

            this.categoryPathHiddenInput.value   = this.getInterfaceCategoryPath(categoryInfo);
            this.categoryNodeIdHiddenInput.value = categoryInfo.browsenode_id;

            this.updateCategoryPathSpan(this.getInterfaceCategoryPath(categoryInfo, true));

            self.setProductDataNick(this.categoryInfo.product_data_nicks.shift());

            if (self.categoryInfo.product_data_nicks.length == 1 && !notSetProductTypeForceIfOnlyOne) {
                self.setProductDataNick(self.categoryInfo.product_data_nicks[0]);
            }

            this.hideCategoryWarning('category_is_not_accessible_message');

            $$('.m2epro-category-depended-block').each(function(el){
                el.show();
            });
        },

        setProductDataNick: function(productDataNick)
        {
            var self = this;

            this.categoryProductDataNickHiddenInput.value = productDataNick;
            this.categoryProductDataNickHiddenInput.simulate('change');

            this.updateWarningMessagesVisibility();

            this.specificHandler.reset();
            this.specificHandler.run(this.categoryInfo, productDataNick);
        },

        // ---------------------------------------

        resetCategory: function()
        {
            this.categoryInfo = {};

            this.categoryPathHiddenInput.value   = '';
            this.categoryNodeIdHiddenInput.value = '';

            this.resetCategoryPathSpan();
            this.resetProductDataNick();

            this.hideCategoryWarning('category_variation_warning_message');

            $$('.m2epro-category-depended-block').each(function(el){
                el.hide();
            });
        },

        resetProductDataNick: function()
        {
            this.categoryProductDataNickHiddenInput.value = '';
            this.categoryProductDataNickHiddenInput.simulate('change');
            this.specificHandler.reset();
        },

        // ---------------------------------------

        prepareEditMode: function()
        {
            var self = WalmartTemplateCategoryObj;

            if (M2ePro.formData.product_data_nick == '' ||
                M2ePro.formData.browsenode_id == '' ||
                M2ePro.formData.category_path == '') {

                return;
            }

            var callback = function(transport) {

                if (!transport.responseText) {

                    self.resetCategory();
                    self.showCategoryWarning('category_is_not_accessible_message');

                } else {

                    var categoryInfo = transport.responseText.evalJSON();

                    self.setCategory(categoryInfo, true);
                    self.setProductDataNick(M2ePro.formData.product_data_nick);

                    if (M2ePro.customData.category_locked) {

                        self.showCategoryWarning('category_locked_warning_message');
                        $('edit_category_link').hide();

                        $('product_data_nick_select').setAttribute('disabled', 'disabled');
                    }
                }
            };

            WalmartTemplateCategoryCategoriesChooserObj.getCategoryInfoFromDictionaryBrowseNodeId(
                M2ePro.formData.browsenode_id,
                M2ePro.formData.category_path,
                callback
            );
        },

        // ---------------------------------------

        showCategoryWarning: function(item)
        {
            var me = $(item);

            var atLeastOneWarningShown = $$('#category_warning_messages span.category-warning-item').any(function(obj) {
                return $(obj).id != me.id && $(obj).visible();
            });

            if (atLeastOneWarningShown && me.previous('span.additional-br')) {
                me.previous('span.additional-br').show();
            }

            $(item).show();
            $('category_warning_messages').show();
        },

        hideCategoryWarning: function(item)
        {
            var me = $(item);
            $(item).hide();

            var atLeastOneWarningShown = $$('#category_warning_messages .category-warning-item').any(function(obj) {
                return $(obj).visible();
            });

            if (me.previous('span.additional-br')) {
                me.previous('span.additional-br').hide();
            }

            !atLeastOneWarningShown && $('category_warning_messages').hide();
        },

        // ---------------------------------------

        updateCategoryPathSpan: function(path)
        {
            $('category_path_span').update(path);
        },

        resetCategoryPathSpan: function()
        {
            var span = $('category_path_span');
            span.innerHTML = '<span style="color: grey; font-style: italic">' + M2ePro.translator.translate('Not Selected') + '</span>';
        },

        updateWarningMessagesVisibility: function()
        {
            var self = WalmartTemplateCategoryObj;

            new Ajax.Request(M2ePro.url.get('walmart_template_category/getVariationThemes'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    marketplace_id:     $('marketplace_id').value,
                    product_data_nick: self.categoryProductDataNickHiddenInput.value
                },
                onSuccess: function(transport) {

                    self.variationThemes = transport.responseText.evalJSON();

                    self.variationThemes.length == 0 ? self.showCategoryWarning('category_variation_warning_message')
                        : self.hideCategoryWarning('category_variation_warning_message');
                }
            });
        },

        //########################################

        getInterfaceCategoryPath: function(categoryInfo, withBrowseNodeId)
        {
            withBrowseNodeId = withBrowseNodeId || false;

            var path = categoryInfo.path != null ? categoryInfo.path.replace(/>/g,' > ') + ' > ' + categoryInfo.title
                : categoryInfo.title;

            return !withBrowseNodeId ? path : path + ' ('+categoryInfo.browsenode_id+')';
        }

        // ---------------------------------------
    });
});