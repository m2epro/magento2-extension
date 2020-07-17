define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
    'M2ePro/Plugin/Magento/AttributeCreator'
], function(jQuery, modal)
{
    window.EbayTemplateCategoryChooser = Class.create(Common, {

        // ---------------------------------------

        marketplaceId: null,
        accountId: null,
        categoryMode: null,

        selectedCategories: {},
        tempSelectedCategory: {},
        selectedSpecifics: {},

        attributes: [],

        doneCallback: null,
        cancelCallback: null,

        confirmSpecificsCallback: null,
        resetSpecificsCallback: null,

        categoriesRequiringValidation: {},

        isEditCategoryAllowed: 1,
        isWizardMode: false,

        // ---------------------------------------

        initialize: function(marketplace, account)
        {
            this.marketplaceId = marketplace;
            this.accountId = account;

            jQuery.validator.addMethod(
                'main_store_category_value',
                function(value, el) {
                    return $('secondary_store_category_value').value === '' || value !== '';
                },
                M2ePro.translator.translate('eBay Primary Store Category must be selected.')
            );
        },

        // ---------------------------------------

        getMarketplaceId: function()
        {
            return this.marketplaceId;
        },

        getAccountId: function()
        {
            return this.accountId;
        },

        setAttributes: function(attributes)
        {
            this.attributes = attributes;
        },

        getAttributes: function()
        {
            return this.attributes;
        },

        setCategoryMode: function(mode)
        {
            this.categoryMode = mode;
        },

        setSelectedCategories: function(categories)
        {
            this.selectedCategories = categories;
        },


        getSelectedCategory: function(type)
        {
            if (typeof type == 'undefined') {
                return this.selectedCategories;
            }

            if (typeof this.selectedCategories[type] == 'undefined') {
                return {
                    mode: 0,
                    value: '',
                    path: '',
                    template_id: null,
                    is_custom_template: null
                };
            }

            return this.selectedCategories[type];
        },

        setIsWizardMode: function(mode)
        {
            this.isWizardMode = mode;
        },

        setIsEditCategoryAllowed: function(mode)
        {
            this.isEditCategoryAllowed = mode;
        },

        // ---------------------------------------

        showEditPopUp: function(type)
        {
            var self = EbayTemplateCategoryChooserObj;
            var selected = self.getSelectedCategory(type);

            new Ajax.Request(M2ePro.url.get('ebay_category/getChooserEditHtml'), {
                method: 'post',
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    category_type: type,
                    selected_mode: selected.mode,
                    selected_value: selected.value,
                    selected_path: selected.path
                },
                onSuccess: function(transport)
                {

                    self.openPopUp(M2ePro.translator.translate('Change Category'), transport.responseText);
                    self.renderRecent();
                    self.renderAttributes();

                    var categoryPathElement = $('selected_category_container').down('#selected_category_path');
                    categoryPathElement.innerHTML = self.cutDownLongPath(categoryPathElement.innerHTML.trim(), 130, '&gt;');
                }
            });
        },

        openPopUp: function(title, html)
        {
            var self = EbayTemplateCategoryChooserObj;

            if ($('chooser_container')) {
                $('chooser_container').remove();
            }

            $('html-body').insert({bottom: html});

            var content = jQuery('#chooser_container');

            modal({
                title: title,
                type: 'slide',
                closed: function()
                {
                    if ($('category_type')) {
                        var type = $('category_type').value;
                        delete self.tempSelectedCategory[type];
                    }
                },
                buttons: [{
                    class: 'ebay_template_category_chooser_cancel',
                    text: M2ePro.translator.translate('Cancel'),
                    click: function()
                    {
                        EbayTemplateCategoryChooserObj.cancelPopUp();
                    }
                }, {
                    class: 'action primary ebay_template_category_chooser_confirm',
                    text: M2ePro.translator.translate('Confirm'),
                    click: function()
                    {
                        EbayTemplateCategoryChooserObj.confirmCategory();
                    }
                }]
            }, content);

            content.modal('openModal');
        },

        // ---------------------------------------

        cancelPopUp: function()
        {
            var self = EbayTemplateCategoryChooserObj;

            if (typeof self.cancelCallback == 'function') {
                self.cancelCallback();
            }

            jQuery('#chooser_container').modal('closeModal');
        },

        // ---------------------------------------

        selectCategory: function(mode, value)
        {
            var self = EbayTemplateCategoryChooserObj;
            var type = $('category_type').value;

            new Ajax.Request(M2ePro.url.get('ebay_category/getSelectedCategoryDetails'), {
                method: 'post',
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    value: value,
                    mode: mode,
                    category_type: type
                },
                onSuccess: function(transport)
                {

                    var response = transport.responseText.evalJSON();

                    self.tempSelectedCategory[type] = {
                        mode: mode,
                        value: value,
                        path: response.path,
                        template_id: response.template_id,
                        is_custom_template: response.is_custom_template
                    };

                    var pathElement = $('selected_category_path');

                    pathElement.setAttribute('title', response.interface_path);
                    pathElement.innerHTML = self.cutDownLongPath(response.interface_path, 130, '>');

                    $('category_reset_link').show();
                }
            });
        },

        unSelectCategory: function()
        {
            var type = $('category_type').value;
            EbayTemplateCategoryChooserObj.tempSelectedCategory[type] = false;

            $('selected_category_path').innerHTML = '';
            $('category_reset_link').hide();
            $('selected_category_path').innerHTML = '<span style="color: grey; font-style: italic">' + M2ePro.translator.translate('Not Selected') + '</span>';
        },

        isCategoryTemporarySelected: function(type)
        {
            return typeof this.tempSelectedCategory[type] != 'undefined'
                && typeof this.tempSelectedCategory[type].mode != 'undefined'
                && this.tempSelectedCategory[type].mode != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_NONE');
        },

        isCategoryTemporaryUnselected: function(type)
        {
            return typeof this.tempSelectedCategory[type] != 'undefined' && this.tempSelectedCategory[type] === false;
            ;
        },

        isCategorySelected: function(type)
        {
            return typeof this.selectedCategories[type] != 'undefined'
                && typeof this.selectedCategories[type].mode != 'undefined'
                && this.selectedCategories[type].mode != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_NONE');
        },

        isCategoryValidationRequired: function(type)
        {
            return typeof this.categoriesRequiringValidation[type] != 'undefined' && this.categoriesRequiringValidation[type];
        },

        confirmCategory: function()
        {
            var self = EbayTemplateCategoryChooserObj;
            var type = $('category_type').value;

            $('category_validation').value = this.isCategoryTemporarySelected(type)
                ? 1
                : (this.isCategorySelected(type) && !this.isCategoryTemporaryUnselected(type)) ? 1 : '';

            if (this.isCategoryValidationRequired(type) && !Validation.validate($('category_validation'))) {
                return;
            }

            if (typeof self.tempSelectedCategory[type] != 'undefined') {

                if (self.isCategoryTemporaryUnselected(type)) {
                    if (type == M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN')) {
                        self.selectedSpecifics = {};
                    }

                    self.selectedCategories[type] = {};
                } else {
                    if (type == M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN') &&
                        (!self.isCategorySelected(type) || self.selectedCategories[type]['value'] != self.tempSelectedCategory[type]['value'])
                    ) {
                        self.selectedSpecifics = {};
                    }

                    self.selectedCategories[type] = self.tempSelectedCategory[type];
                }

                delete self.tempSelectedCategory[type];
            }

            if (typeof self.doneCallback == 'function') {
                self.doneCallback();
            }

            jQuery('#chooser_container').modal('closeModal');
            self.reload();
        },

        reload: function()
        {
            var self = EbayTemplateCategoryChooserObj;

            new Ajax.Request(M2ePro.url.get('ebay_category/getChooserHtml'), {
                method: 'post',
                asynchronous: false,
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    is_edit_category_allowed: self.isEditCategoryAllowed,
                    selected_categories: Object.toJSON(self.selectedCategories),
                    category_mode: self.categoryMode
                },
                onSuccess: function(transport)
                {
                    $('ebay_category_chooser').innerHTML = transport.responseText;
                }
            });
        },

        // ---------------------------------------

        renderAttributes: function()
        {
            var self = EbayTemplateCategoryChooserObj;

            if (!$('chooser_attributes_table')) {
                return;
            }

            var handlerObj = new AttributeCreator('category_chooser_' + this.marketplaceId + '_' + this.accountId);
            handlerObj.setOnSuccessCallback(function(attributeParams, result)
            {

                $$('#chooser_attributes_table tbody').first().update();

                self.attributes.push({
                    code: attributeParams.code,
                    label: attributeParams.store_label
                });
                self.renderAttributes();
                self.selectCategory(M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_ATTRIBUTE'), attributeParams.code);
            });

            handlerObj.setOnFailedCallback(function(attributeParams, result)
            {
                self.alert(result['error']);
            });

            var totalHtml = '',
                rowHtml = '',
                newAttrHtml = '<td style="color: brown">' + M2ePro.translator.translate('Create a New One...') + '</td>' +
                    '<td style="padding-right: 10px"><a href="javascript:void(0)"  style="float: right" ' +
                    'onclick="' + handlerObj.id + '.showPopup({\'allowed_attribute_types\':\'text,select\'});">' +
                    M2ePro.translator.translate('Select') + '</a></td>';

            self.attributes.each(function(attribute, index)
            {

                rowHtml += '<td>' + attribute.label + '</td>' +
                    '<td style="padding-right: 10px"><a href="javascript:void(0)" style="float: right" ' +
                    'onclick="EbayTemplateCategoryChooserObj.selectCategory(' + M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_ATTRIBUTE') + ', \'' + attribute.code + '\')">' +
                    M2ePro.translator.translate('Select') + '</a></td>';

                if ((index + 1) == self.attributes.length && (index + 1) % 2 != 0) {
                    rowHtml += newAttrHtml;
                }

                if (((index + 1) % 2 == 0) ||
                    (index + 1) == self.attributes.length) {

                    totalHtml += '<tr>' + rowHtml + '</tr>';
                    rowHtml = '';
                }

                if ((index + 1) == self.attributes.length && (index + 1) % 2 == 0) {
                    totalHtml += '<tr>' + newAttrHtml + '</tr>';
                }
            });

            $$('#chooser_attributes_table tbody').first().insert(totalHtml);
        },

        renderRecent: function()
        {
            var self = EbayTemplateCategoryChooserObj;

            if (!$('chooser_recent_table')) {
                return;
            }

            var type = $('category_type').value;

            var selected = null;
            if (typeof self.selectedCategories[type] != "undefined" &&
                self.selectedCategories[type].mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_EBAY')) {

                selected = self.selectedCategories[type].value;
            }

            new Ajax.Request(M2ePro.url.get('ebay_category/getRecent'), {
                method: 'post',
                parameters: {
                    marketplace: self.marketplaceId,
                    account: self.accountId,
                    selected_category: selected,
                    category_type: type
                },
                onSuccess: function(transport)
                {

                    var categories = transport.responseText.evalJSON();
                    var html = '';

                    if (transport.responseText.length > 2) {
                        html += '<tr><td width="730px"></td><td width="70px"></td></tr>';
                        categories.each(function(category)
                        {
                            html += '<tr><td>' + category.path + '</td>' +
                                '<td style="width: 60px"><a href="javascript:void(0)" ' +
                                'onclick="EbayTemplateCategoryChooserObj.selectCategory(' + M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_EBAY') + ', \'' + category.id + '\')">' +
                                M2ePro.translator.translate('Select') + '</a></td></tr>';
                        });
                    } else {
                        html += '<tr><td colspan="2" style="padding-left: 200px"><strong>' + M2ePro.translator.translate('No recently used Categories') + '</strong></td></tr>';
                    }

                    $('chooser_recent_table').innerHTML = html;
                }
            });
        },

        search: function()
        {
            var self = EbayTemplateCategoryChooserObj;

            var query = $('query').value;
            if (query.length < 3) {
                return;
            }

            var type = $('category_type').value;
            $('chooser_search_results').innerHTML = '';

            new Ajax.Request(M2ePro.url.get('ebay_category/search'), {
                method: 'post',
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    query: query,
                    category_type: type
                },
                onSuccess: function(transport)
                {

                    var html = '<table id="search_results_table"><tr><td width="740px"></td><td width="60px"></td></tr>';

                    if (transport.responseText.length > 2) {
                        var result = transport.responseText.evalJSON();
                        result.each(function(category)
                        {
                            html += '<tr><td style="padding: 2px;">';
                            html += category.titles + ' (' + category.id + ')';
                            html += '</td><td style="padding: 2px;">';
                            html += '<a href="javascript:void(0)" style="float: right" ' +
                                'onclick="EbayTemplateCategoryChooserObj.selectCategory(' + M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_EBAY') + ', ' + category.id + ')">' +
                                M2ePro.translator.translate('Select') + '</a>';
                            html += '</td>';
                        });
                    } else {
                        html += '<tr><td colspan="2" style="text-align: center;"><strong>' + M2ePro.translator.translate('No results') + '</strong></td></tr>';

                        var refreshMessage = '';

                        if (!self.isWizardMode) {

                            if ($('category_type').value == M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN') ||
                                $('category_type').value == M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_SECONDARY')
                            ) {
                                refreshMessage = M2ePro.translator.translate('Try to refreshEbayCategories.');
                            } else {
                                refreshMessage = M2ePro.translator.translate('Try to refreshStoreCategories.');
                            }
                        }

                        html += '<tr><td colspan="2" style="text-align: center;">' + refreshMessage + '</td></tr>';
                    }

                    html += '</table>';

                    $('chooser_search_results').innerHTML = html;
                }
            });
        },

        keyPressQuery: function(event)
        {
            if (event.keyCode == 13) {
                this.search();
            }
        },

        searchReset: function()
        {
            $('chooser_search_results').update();
            $('query').value = '';
            $('query').focus();
        },

        refreshStoreCategories: function()
        {
            var self = EbayTemplateCategoryChooserObj;

            if (self.accountId == null) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('ebay_category/refreshStoreCategories'), {
                method: 'post',
                parameters: {
                    account_id: self.accountId
                },
                onSuccess: function(transport)
                {

                    EbayTemplateCategoryChooserTabsBrowseObj.renderTopLevelCategories('chooser_browser');

                    if ($('query').value.length != 0) {
                        self.search();
                    }
                }
            });
        },

        refreshEbayCategories: function()
        {
            var self = EbayTemplateCategoryChooserObj;
            var win = window.open(M2ePro.url.get('ebay_marketplace/index'));

            var intervalId = setInterval(function()
            {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                EbayTemplateCategoryChooserTabsBrowseObj.renderTopLevelCategories('chooser_browser');

                if ($('query').value.length != 0) {
                    self.search();
                }
            }, 1000);
        },

        // ---------------------------------------

        cutDownLongPath: function(path, length, sep)
        {
            if (path.length > length && sep) {

                var parts = path.split(sep),
                    isNeedSeparator = false;

                var shortPath = '';
                parts.each(function(part, index)
                {
                    if ((part.length + shortPath.length) >= length) {

                        var lenDiff = (parts[parts.length - 1].length + shortPath.length) - length;
                        if (lenDiff > 0) {
                            shortPath = shortPath.slice(0, shortPath.length - lenDiff + 1);
                        }

                        shortPath = shortPath.slice(0, shortPath.length - 3) + '...';

                        shortPath += parts[parts.length - 1];
                        throw $break;
                    }

                    shortPath += part + (isNeedSeparator ? sep : '');
                    isNeedSeparator = true;
                });

                return shortPath;
            }

            return path;
        },

        // ---------------------------------------

        editSpecifics: function()
        {
            var self = EbayTemplateCategoryChooserObj,
                typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                selectedCategory = this.getSelectedCategory(typeMain);

            var specifics = [];
            if (typeof selectedCategory['specific'] !== 'undefined' && selectedCategory['specific'] !== null) {
                specifics = selectedCategory['specific'];
            }

            if (Object.keys(self.selectedSpecifics).length !== 0) {
                specifics = self.selectedSpecifics;
            }

            new Ajax.Request(M2ePro.url.get('ebay_category/getCategorySpecificHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    marketplace_id: self.marketplaceId,
                    selected_specifics: Object.toJSON(specifics),
                    template_id: selectedCategory['template_id'],
                    category_mode: selectedCategory['mode'],
                    category_value: selectedCategory['value']
                },
                onSuccess: function(transport)
                {
                    this.openSpecificsPopUp(M2ePro.translator.translate('Specifics'), transport.responseText);
                }.bind(this)
            });
        },

        openSpecificsPopUp: function(title, html)
        {
            if ($('chooser_container_specific')) {
                $('chooser_container_specific').remove();
            }

            $('html-body').insert({bottom: html});

            var content = jQuery('#chooser_container_specific');

            modal({
                title: title,
                type: 'slide',
                buttons: [{
                    class: 'ebay_template_category_specific_chooser_cancel',
                    text: M2ePro.translator.translate('Cancel'),
                    click: function()
                    {
                        this.closeModal();
                    }
                }, {
                    class: 'action primary ebay_template_category_specific_chooser_reset',
                    text: M2ePro.translator.translate('Reset'),
                    click: function()
                    {
                        EbayTemplateCategorySpecificsObj.resetSpecifics();
                    }
                }, {
                    class: 'action primary ebay_template_category_specific_chooser_save',
                    text: M2ePro.translator.translate('Save'),
                    click: function()
                    {
                        EbayTemplateCategoryChooserObj.confirmSpecifics();
                    }
                }, {}]
            }, content);

            content.modal('openModal');
        },

        confirmSpecifics: function()
        {
            this.initFormValidation('#edit_specifics_form');
            if (!jQuery('#edit_specifics_form').valid()) {
                return;
            }

            var self = EbayTemplateCategoryChooserObj,
                typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                selectedCategory = this.getSelectedCategory(typeMain);

            this.selectedSpecifics = EbayTemplateCategorySpecificsObj.collectSpecifics();

            new Ajax.Request(M2ePro.url.get('ebay_category/getSelectedCategoryDetails'), {
                method: 'post',
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    value: selectedCategory['value'],
                    mode: selectedCategory['mode'],
                    category_type: typeMain
                },
                onSuccess: function(transport)
                {

                    var response = transport.responseText.evalJSON();

                    if (response.is_custom_template === null) {
                        self.selectedCategories[typeMain].template_id = null;
                        self.selectedCategories[typeMain].is_custom_template = '0';
                    } else {
                        self.selectedCategories[typeMain].template_id = null;
                        self.selectedCategories[typeMain].is_custom_template = '1';
                    }

                    if (typeof self.confirmSpecificsCallback == 'function') {
                        self.confirmSpecificsCallback();
                    }

                    jQuery('#chooser_container_specific').modal('closeModal');
                    self.reload();
                }
            });
        },

        resetSpecificsToDefault: function()
        {
            var self = EbayTemplateCategoryChooserObj,
                typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                selectedCategory = this.getSelectedCategory(typeMain);

            new Ajax.Request(M2ePro.url.get('ebay_category/getSelectedCategoryDetails'), {
                method: 'post',
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    value: selectedCategory['value'],
                    mode: selectedCategory['mode'],
                    category_type: typeMain
                },
                onSuccess: function(transport)
                {

                    var response = transport.responseText.evalJSON();

                    self.selectedCategories[typeMain].template_id = response.template_id;
                    self.selectedCategories[typeMain].is_custom_template = response.is_custom_template;

                    self.selectedSpecifics = {};

                    if (typeof self.resetSpecificsCallback == 'function') {
                        self.resetSpecificsCallback();
                    }

                    self.reload();
                }
            });
        }

        // ---------------------------------------
    });
});
