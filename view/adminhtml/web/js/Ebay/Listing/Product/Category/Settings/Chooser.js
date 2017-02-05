define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
    'M2ePro/Plugin/Magento/AttributeCreator'
], function (jQuery, modal) {

    window.EbayListingProductCategorySettingsChooser = Class.create();
    EbayListingProductCategorySettingsChooser.prototype = Object.extend(new Common(), {

        // ---------------------------------------

        marketplaceId: null,
        accountId: null,
        divId: null,

        selectedCategories: {},
        categoryTitles: {},
        attributes: [],

        selectCallback: null,
        unselectCallback: null,
        doneCallback: null,
        cancelCallback: null,

        isShowEditLinks: true,
        categoriesRequiringValidation: {},

        isSingleCategoryMode: false,
        singleCategoryType: null,

        tempUnselectedCategory: {},
        tempSelectedCategory: {},

        isWizardMode: false,

        // ---------------------------------------

        initialize: function (div, marketplace, account) {
            this.marketplaceId = marketplace;
            this.accountId = account;
            this.divId = div;
        },

        // ---------------------------------------

        setSelectCallback: function (callback) {
            this.selectCallback = callback;
        },

        setUnselectCallback: function (callback) {
            this.unselectCallback = callback;
        },

        getMarketplaceId: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            return self.marketplaceId;
        },

        getAccountId: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            return self.accountId;
        },

        setAttributes: function (attributes) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.attributes = attributes;
        },

        getAttributes: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            return self.attributes;
        },

        setSingleCategoryMode: function (mode) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.isSingleCategoryMode = mode;
        },

        setSingleCategoryType: function (type) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.singleCategoryType = type;
        },

        setShowEditLinks: function (mode) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.isShowEditLinks = mode;
        },

        setSelectedCategory: function (type, mode, value) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.selectedCategories[type] = {
                mode: mode,
                value: value
            };
        },

        setSelectedCategories: function (categories) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.selectedCategories = categories;
        },

        getSelectedCategory: function (type) {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (typeof type == 'undefined') {
                return self.selectedCategories;
            }

            if (typeof self.selectedCategories[type] == 'undefined') {

                return {mode: 0, value: ''};
            }

            return self.selectedCategories[type];
        },

        getInternalData: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (self.isSingleCategoryMode) {
                return self.selectedCategories[self.singleCategoryType];
            }

            var internalData = {};

            self.setCategoryToInternalData(
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                'category_main_',
                internalData
            );
            self.setCategoryToInternalData(
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_SECONDARY'),
                'category_secondary_',
                internalData
            );
            self.setCategoryToInternalData(
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_MAIN'),
                'store_category_main_',
                internalData
            );
            self.setCategoryToInternalData(
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_SECONDARY'),
                'store_category_secondary_',
                internalData
            );

            return internalData;
        },

        getInternalDataByType: function (type) {
            var prefixByType = {};

            prefixByType[M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN')] = 'category_main_';
            prefixByType[M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_SECONDARY')] = 'category_secondary_';
            prefixByType[M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_MAIN')] = 'store_category_main_';
            prefixByType[M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_SECONDARY')] = 'store_category_secondary_';

            var data = {};

            this.setCategoryToInternalData(
                type,
                prefixByType[type],
                data
            );

            return data;
        },

        getConvertedInternalData: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            return self.selectedCategories;
        },

        setCategoryToInternalData: function (type, prefix, data) {
            var self = EbayListingProductCategorySettingsChooserObj;

            data[prefix + 'mode'] = 0;
            data[prefix + 'id'] = null;
            data[prefix + 'attribute'] = null;

            if (typeof self.selectedCategories[type] != 'undefined' &&
                typeof self.selectedCategories[type]['mode'] != 'undefined') {

                data[prefix + 'mode'] = self.selectedCategories[type].mode;

                if (data[prefix + 'mode'] == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_EBAY')) {
                    data[prefix + 'id'] = self.selectedCategories[type].value;
                } else if (data[prefix + 'mode'] == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_ATTRIBUTE')) {
                    data[prefix + 'attribute'] = self.selectedCategories[type].value;
                }
            }

            return data;
        },

        getCategoryTitle: function (type) {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (typeof self.categoryTitles[type] == 'undefined') {
                return '';
            }

            return self.categoryTitles[type];
        },

        setCategoryTitles: function (titles) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.categoryTitles = titles;
        },

        setIsWizardMode: function (mode) {
            var self = EbayListingProductCategorySettingsChooserObj;

            self.isWizardMode = mode;
        },

        // ---------------------------------------

        showEditPopUp: function (type) {
            var self = EbayListingProductCategorySettingsChooserObj;
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
                onSuccess: function (transport) {
                    var title = M2ePro.translator.translate('Edit') + ' ' + self.getCategoryTitle(type);

                    self.openPopUp(title, transport.responseText);
                    self.renderRecent();
                    self.renderAttributes();
                }
            });
        },

        openPopUp: function (title, html) {
            var self = EbayListingProductCategorySettingsChooserObj;

            if ($('chooser_container')) {
                $('chooser_container').remove();
            }

            $('html-body').insert({bottom: html});

            var content = jQuery('#chooser_container');

            modal({
                title: title,
                type: 'slide',
                closed: function(){
                    var type = $('category_type').value;

                    delete self.tempSelectedCategory[type];
                    delete self.tempUnselectedCategory[type];
                },
                buttons: [{
                    text : M2ePro.translator.translate('Cancel'),
                    click : function () {
                        EbayListingProductCategorySettingsChooserObj.cancelPopUp();
                    }
                },{
                    class : 'action primary',
                    text : M2ePro.translator.translate('Confirm'),
                    click : function () {
                        EbayListingProductCategorySettingsChooserObj.confirmCategory();
                    }
                }]
            }, content);

            content.modal('openModal');
        },

        // ---------------------------------------

        cancelPopUp: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            jQuery('#chooser_container').modal('closeModal');

            if (typeof self.cancelCallback == 'function') {
                self.cancelCallback();
            }
        },

        // ---------------------------------------

        selectCategory: function (mode, value) {
            var self = EbayListingProductCategorySettingsChooserObj;

            var type = $('category_type').value;

            self.tempSelectedCategory[type] = {
                mode: mode,
                value: value
            };
            delete self.tempUnselectedCategory[type];

            new Ajax.Request(M2ePro.url.get('ebay_category/getPath'), {
                method: 'post',
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    value: value,
                    mode: mode,
                    category_type: type
                },
                onSuccess: function (transport) {
                    $('selected_category_path').innerHTML = transport.responseText;
                    $('category_reset_link').show();
                }
            });
        },

        unSelectCategory: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            var type = $('category_type').value;
            self.tempUnselectedCategory[type] = true;
            delete self.tempSelectedCategory[type];

            $('selected_category_path').innerHTML = '';
            $('category_reset_link').hide();
            $('selected_category_path').innerHTML = '<span style="color: grey; font-style: italic">' + M2ePro.translator.translate('Not Selected') + '</span>';
        },

        isCategoryTemporarySelected: function (type) {
            return typeof this.tempSelectedCategory[type] != 'undefined'
                && typeof this.tempSelectedCategory[type].mode != 'undefined'
                && this.tempSelectedCategory[type].mode != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_NONE');
        },

        isCategoryTemporaryUnselected: function (type) {
            return typeof this.tempUnselectedCategory[type] != 'undefined';
        },

        isCategorySelected: function (type) {
            return typeof this.selectedCategories[type] != 'undefined'
                && typeof this.selectedCategories[type].mode != 'undefined'
                && this.selectedCategories[type].mode != M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_NONE');
        },

        isCategoryValidationRequired: function (type) {
            return typeof this.categoriesRequiringValidation[type] != 'undefined' && this.categoriesRequiringValidation[type];
        },

        confirmCategory: function () {
            var self = EbayListingProductCategorySettingsChooserObj;
            var type = $('category_type').value;

            $('category_validation').value = this.isCategoryTemporarySelected(type) ? 1 :
                (this.isCategorySelected(type) && !this.isCategoryTemporaryUnselected(type)) ? 1 : '';

            if (this.isCategoryValidationRequired(type) && !Validation.validate($('category_validation'))) {
                return;
            }

            if (typeof self.tempSelectedCategory[type] != 'undefined') {
                self.selectedCategories[type] = self.tempSelectedCategory[type];

                if (type == 0 && this.selectCallback != null) {
                    (this.selectCallback)(self.selectedCategories[type].mode, self.selectedCategories[type].value);
                }
            }

            if (typeof self.tempUnselectedCategory[type] != 'undefined') {
                delete self.selectedCategories[type];

                if (this.unselectCallback != null) {
                    (this.unselectCallback)();
                }
            }

            delete self.tempSelectedCategory[type];
            delete self.tempUnselectedCategory[type];

            jQuery('#chooser_container').modal('closeModal');

            self.reload();

            if (typeof self.doneCallback == 'function') {
                self.doneCallback();
            }
        },

        reload: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            var selectedCategories = {};
            var types = [
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN'),
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_SECONDARY'),
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_MAIN'),
                M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_STORE_SECONDARY')
            ];

            types.each(function (type) {
                if (typeof self.selectedCategories[type] == 'undefined') {
                    selectedCategories[type] = null;
                } else {
                    selectedCategories[type] = self.selectedCategories[type];
                }
            });

            new Ajax.Request(M2ePro.url.get('ebay_category/getChooserHtml'), {
                method: 'post',
                asynchronous: false,
                parameters: {
                    marketplace_id: self.marketplaceId,
                    account_id: self.accountId,
                    div_id: self.divId,
                    selected_categories: Object.toJSON(selectedCategories),
                    is_single_category_mode: self.isSingleCategoryMode,
                    single_category_type: self.singleCategoryType,
                    is_show_edit_links: self.isShowEditLinks,
                    select_callback: self.selectCallback,
                    unselect_callback: self.unselectCallback
                },
                onSuccess: function (transport) {
                    if ($(self.divId)) {
                        $(self.divId).innerHTML = transport.responseText;
                    }
                }
            });
        },

        reloadPopUp: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            var type = $('category_type').value;
            self.showEditPopUp(type);
        },

        // ---------------------------------------

        renderAttributes: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (!$('chooser_attributes_table')) {
                return;
            }

            var handlerObj = new AttributeCreator('category_chooser_' + this.marketplaceId +'_'+ this.accountId +'_'+ this.divId);
            handlerObj.setOnSuccessCallback(function(attributeParams, result) {

                $$('#chooser_attributes_table tbody').first().update();

                self.attributes.push({
                    code:  attributeParams.code,
                    label: attributeParams.store_label
                });
                self.renderAttributes();
                self.selectCategory(M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_ATTRIBUTE'), attributeParams.code);
            });

            handlerObj.setOnFailedCallback(function(attributeParams, result) {
                self.alert(result['error']);
            });

            var totalHtml = '',
                rowHtml   = '',
                newAttrHtml = '<td style="color: brown">'+M2ePro.translator.translate('Create a New One...')+'</td>' +
                    '<td style="padding-left: 55px"><a href="javascript:void(0)" ' +
                    'onclick="' + handlerObj.id + '.showPopup({\'allowed_attribute_types\':\'text,select\'});">' +
                    M2ePro.translator.translate('Select') + '</a></td>';

            self.attributes.each(function (attribute, index) {

                rowHtml += '<td>' + attribute.label + '</td>' +
                    '<td style="padding-left: 55px"><a href="javascript:void(0)" ' +
                    'onclick="EbayListingProductCategorySettingsChooserObj.selectCategory(' + M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_ATTRIBUTE') + ', \'' + attribute.code + '\')">' +
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

        renderRecent: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

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
                onSuccess: function (transport) {

                    var categories = transport.responseText.evalJSON();
                    var html = '';

                    if (transport.responseText.length > 2) {
                        categories.each(function (category) {
                            html += '<tr><td>' + category.path + '</td>' +
                                '<td><a href="javascript:void(0)" ' +
                                'onclick="EbayListingProductCategorySettingsChooserObj.selectCategory(' + M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_EBAY') + ', \'' + category.id + '\')">' +
                                M2ePro.translator.translate('Select') + '</a></td></tr>';
                        });
                    } else {
                        html += '<tr><td colspan="2" style="padding-left: 200px"><strong>' + M2ePro.translator.translate('No recently used Categories') + '</strong></td></tr>';
                    }

                    $('chooser_recent_table').innerHTML = html;
                }
            });
        },

        search: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

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
                onSuccess: function (transport) {

                    var html = '<table id="search_results_table">';

                    if (transport.responseText.length > 2) {
                        var result = transport.responseText.evalJSON();
                        result.each(function (category) {
                            html += '<tr><td style="padding: 2px;">';
                            html += category.titles + ' (' + category.id + ')';
                            html += '</td><td style="padding: 2px;">';
                            html += '<a href="javascript:void(0)" ' +
                                'onclick="EbayListingProductCategorySettingsChooserObj.selectCategory(' + M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_EBAY') + ', ' + category.id + ')">' +
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
                                refreshMessage = M2ePro.translator.translate('Try to <a href="javascript:void(0)" onclick="EbayListingProductCategorySettingsChooserObj.refreshEbayCategories()">update Marketplaces Data</a> and repeate the Search.');
                            } else {
                                refreshMessage = M2ePro.translator.translate('Try to <a href="javascript:void(0)" onclick="EbayListingProductCategorySettingsChooserObj.refreshStoreCategories()">refresh eBay Store Data</a> and repeate the Search.');
                            }
                        }

                        html += '<tr><td colspan="2" style="text-align: center;">' + refreshMessage + '</td></tr>';
                    }

                    html += '</table>';

                    $('chooser_search_results').innerHTML = html;
                }
            });
        },

        searchReset: function () {
            $('chooser_search_results').update();
            $('query').value = '';
            $('query').focus();
        },

        refreshStoreCategories: function () {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (self.accountId == null) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('ebay_category/refreshStoreCategories'), {
                method: 'post',
                parameters: {
                    account_id: self.accountId
                },
                onSuccess: function (transport) {

                    EbayListingProductCategorySettingsChooserTabsBrowseObj.renderTopLevelCategories('chooser_browser');

                    if ($('query').value.length != 0) {
                        self.search();
                    }
                }
            });
        },

        refreshEbayCategories: function () {
            var self = EbayListingProductCategorySettingsChooserObj;
            var win = window.open(M2ePro.url.get('ebay_marketplace/index'));

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                EbayListingProductCategorySettingsChooserTabsBrowseObj.renderTopLevelCategories('chooser_browser');

                if ($('query').value.length != 0) {
                    self.search();
                }
            }, 1000);
        },

        // ---------------------------------------

        validate: function () {
            var self = EbayListingProductCategorySettingsChooserObj;
            var mainStore = false;

            if ($$('#' + self.divId + ' .main-empty-advice').length <= 0) {
                return true;
            }

            if ($$('.main-store-empty-advice').length > 0) {
                mainStore = true;
            }

            $$('#' + self.divId + ' .main-empty-advice')[0].hide();
            if (mainStore) {
                $$('.main-store-empty-advice')[0].hide();
            }

            var typeEbayMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN');
            if (typeof self.selectedCategories[typeEbayMain] == 'undefined' ||
                typeof self.selectedCategories[typeEbayMain]['mode'] == 'undefined' ||
                self.selectedCategories[typeEbayMain]['mode'] == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Ebay\\Template\\Category::CATEGORY_MODE_NONE')) {

                $$('#' + self.divId + ' .main-empty-advice')[0].show();
                return false;
            }

            if (!mainStore) {
                return true;
            }

            var primary = $('magento_block_ebay_listing_category_chooser_store_primary_not_selected') == null;
            var secondary = $('magento_block_ebay_listing_category_chooser_store_secondary_not_selected') == null;

            if (primary == false && secondary == true) {
                $$('.main-store-empty-advice')[0].show();
                return false;
            }

            return true;
        },

        // ---------------------------------------

        keyPressQuery: function (event) {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (event.keyCode == 13) {
                self.search();
            }
        },

        // ---------------------------------------

        submitData: function (url) {
            var self = EbayListingProductCategorySettingsChooserObj;

            if (!self.validate()) {
                return;
            }

            var categoryData = self.getInternalData();

            self.postForm(url, {category_data: Object.toJSON(categoryData)});
        }

        // ---------------------------------------
    });
});