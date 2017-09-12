define([
    'M2ePro/Plugin/Messages',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Action'
], function (MessageObj, modal) {

    window.AmazonListingProductVariationManage = Class.create(Action, {

        MATCHING_TYPE_EQUAL: 1,
        MATCHING_TYPE_VIRTUAL_AMAZON: 2,
        MATCHING_TYPE_VIRTUAL_MAGENTO: 3,

        // ---------------------------------------

        initialize: function ($super, gridHandler) {
            var self = this;

            $super(gridHandler);
        },

        // ---------------------------------------

        options: {},
        matchingType: 1,
        matchedAttributes: [],
        productAttributes: [],
        destinationAttributes: [],
        selectedDestinationAttributes: [],
        magentoVariationSet: [],
        amazonVariationSet: false,

        setOptions: function (options) {
            this.options = Object.extend(this.options, options);
            this.initValidators();
            return this;
        },

        initValidators: function () {
            var self = this;

            jQuery.validator.addMethod('M2ePro-amazon-attribute-unique-value', function(value, el) {
                var existedValues = [],
                    isValid = true,
                    form = el.up('form');

                form.select('select').each(function (el) {
                    if (el.value != '') {
                        if (existedValues.indexOf(el.value) === -1) {
                            existedValues.push(el.value);
                        } else {
                            isValid = false;
                        }
                    }
                });

                return isValid;
            }, M2ePro.translator.translate('variation_manage_matched_attributes_error_duplicate'));
        },

        initSettingsTab: function () {
            var self = this,
                form = $('variation_manager_attributes_form');

            if (form && self.matchingType == self.MATCHING_TYPE_EQUAL) {
                form.observe('change', function (e) {
                    if (e.target.tagName != 'SELECT') {
                        return;
                    }

                    $(e.target).select('.empty') && $(e.target).select('.empty').length && $(e.target).select('.empty')[0].hide();
                });
            }
        },

        // ---------------------------------------

        parseResponse: function (response) {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        openPopUp: function (productId, title, filter) {
            var self = this;

            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation/manage'), {
                method: 'post',
                parameters: {
                    product_id: productId,
                    filter: filter
                },
                onSuccess: function (transport) {

                    var containerEl = $('variation_manage_tabs_container');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    self.variationProductManagePopup = jQuery('#variation_manage_tabs_container');

                    modal({
                        title: title.escapeHTML(),
                        type: 'slide',
                        closed: function() {
                            self.gridHandler.unselectAllAndReload();
                        },
                        buttons: [
                        //     {
                        //     text: M2ePro.translator.translate('Close'),
                        //     class: 'action primary ',
                        //     click: function () {
                        //         self.searchMenuPopup.modal('closeModal')
                        //     }
                        // }
                        ]
                    }, self.variationProductManagePopup);

                    self.variationProductManagePopup.modal('openModal');
                    self.variationProductManagePopup.productId = productId;

                    self.initSettingsTab();
                }
            });
        },

        closeManageVariationsPopup: function () {
            this.variationProductManagePopup.modal('closeModal');
        },

        // ---------------------------------------

        openVocabularyAttributesPopUp: function (attributes) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/getAttributesPopup'), {
                onSuccess: function (transport) {

                    var containerEl = $('vocabulary_attributes_pupup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    self.vocabularyAttributesPupup = jQuery('#vocabulary_attributes_pupup');

                    modal({
                        title: 'Vocabulary',
                        type: 'popup',
                        closed: function() {
                            self.reloadVariationsGrid();
                        },
                        buttons: [{
                            text: M2ePro.translator.translate('No'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.addAttributesToVocabulary(false);
                            }
                        },{
                            text: M2ePro.translator.translate('Yes'),
                            class: 'action-primary action-accept',
                            click: function () {
                                self.addAttributesToVocabulary(true);
                            }
                        }]
                    }, self.vocabularyAttributesPupup);

                    self.vocabularyAttributesPupup.modal('openModal');

                    $('vocabulary_attributes_data').value = Object.toJSON(attributes);

                    var attributesHtml = '';
                    $H(attributes).each(function (element) {
                        attributesHtml += '<li>' + element.key + ' > ' + element.value + '</li>';
                    });

                    attributesHtml = '<ul style="list-style-position: inside;">' + attributesHtml + '</ul>';

                    var bodyHtml = str_replace('%attributes%', attributesHtml, $('vocabulary_attributes_pupup').innerHTML);

                    $('vocabulary_attributes_pupup').update(bodyHtml);
                }
            });
        },

        addAttributesToVocabulary: function (needAdd) {
            var self = this;

            var isRemember = $('vocabulary_attributes_remember_checkbox').checked;

            if (!needAdd && !isRemember) {
                self.vocabularyAttributesPupup.modal('closeModal');
                return;
            }

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/addAttributes'), {
                method: 'post',
                parameters: {
                    attributes: $('vocabulary_attributes_data').value,
                    need_add: needAdd ? 1 : 0,
                    is_remember: isRemember ? 1 : 0
                },
                onSuccess: function (transport) {
                    self.vocabularyAttributesPupup.modal('closeModal');
                }
            });
        },

        openVocabularyOptionsPopUp: function (options) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/getOptionsPopup'), {
                onSuccess: function (transport) {

                    var containerEl = $('vocabulary_options_pupup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    self.vocabularyOptionsPopup = jQuery('#vocabulary_options_pupup');

                    modal({
                        title: 'Vocabulary',
                        type: 'popup',
                        closed: function() {
                            self.reloadVariationsGrid();
                        },
                        buttons: [{
                            text: M2ePro.translator.translate('No'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.addOptionsToVocabulary(false);
                            }
                        },{
                            text: M2ePro.translator.translate('Yes'),
                            class: 'action-primary action-accept',
                            click: function () {
                                self.addOptionsToVocabulary(true);
                            }
                        }]
                    }, self.vocabularyOptionsPopup);

                    self.vocabularyOptionsPopup.modal('openModal');

                    $('vocabulary_options_data').value = Object.toJSON(options);

                    var optionsHtml = '';
                    $H(options).each(function (element) {

                        var valuesHtml = '';
                        $H(element.value).each(function (value) {
                            valuesHtml += value.key + ' > ' + value.value;
                        });

                        optionsHtml += '<li>' + element.key + ': ' + valuesHtml + '</li>';
                    });

                    optionsHtml = '<ul style="list-style-position: inside;">' + optionsHtml + '</ul>';

                    var bodyHtml = str_replace('%options%', optionsHtml, $('vocabulary_options_pupup').innerHTML);

                    $('vocabulary_options_pupup').update(bodyHtml);
                }
            });
        },

        addOptionsToVocabulary: function (needAdd) {
            var self = this;

            var isRemember = $('vocabulary_options_remember_checkbox').checked;

            if (!needAdd && !isRemember) {
                self.vocabularyOptionsPopup.modal('closeModal');
                return;
            }

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/addOptions'), {
                method: 'post',
                parameters: {
                    options_data: $('vocabulary_options_data').value,
                    need_add: needAdd ? 1 : 0,
                    is_remember: isRemember ? 1 : 0
                },
                onSuccess: function (transport) {
                    self.vocabularyOptionsPopup.modal('closeModal');
                }
            });
        },

        // ---------------------------------------

        setGeneralIdOwner: function (value, hideConfirm) {
            var self = this,
                confirmAction;

            confirmAction = function () {
                new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/setGeneralIdOwner'), {
                    method: 'post',
                    parameters: {
                        product_id: self.variationProductManagePopup.productId,
                        general_id_owner: value
                    },
                    onSuccess: function (transport) {

                        var response = self.parseResponse(transport);
                        if (response.success) {
                            return self.reloadVariationsGrid();
                        }

                        if (response.empty_sku) {
                            return self.openSkuPopUp();
                        }
                        self.openDescriptionTemplatePopUp(response.html);
                    }
                });
            };

            if (hideConfirm) {
                confirmAction();
            } else {
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
            }
        },

        openSkuPopUp: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/getSkuPopup'), {
                method: 'post',
                parameters: {
                    product_id: self.variationProductManagePopup.productId
                },
                onSuccess: function (transport) {

                    var containerEl = $('manage_variation_sku_popup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    self.skuPopup = jQuery('#manage_variation_sku_popup');

                    modal({
                        title: M2ePro.translator.translate('variation_manage_matched_sku_popup_title'),
                        type: 'popup',
                        modalClass: 'width-50',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.skuPopup.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'action-primary action-accept',
                            click: function () {
                                self.setProductSku();
                            }
                        }]
                    }, self.skuPopup);

                    self.skuPopup.modal('openModal');
                }
            });
        },

        setProductSku: function () {
            var self = this,
                data;

            this.initFormValidation('#variation_manager_sku_form');

            if (!jQuery('#variation_manager_sku_form').valid()) {
                return;
            }

            data = $('variation_manager_sku_form').serialize(true);
            data.product_id = self.variationProductManagePopup.productId;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/setListingProductSku'), {
                method: 'post',
                parameters: data,
                onSuccess: function (transport) {

                    var response = self.parseResponse(transport);
                    if (response.success) {
                        self.skuPopup.modal('closeModal');
                        self.setGeneralIdOwner(1, true);
                    } else {
                        var skuError = $('sku-error');

                        if (!skuError) {
                            skuError = new Element('label', {
                                id : 'sku-error',
                                for : 'sku',
                                class: 'error',
                                generated: true
                            });

                            $('sku').insert({after: skuError})
                        }

                        skuError.update(response.msg);
                        skuError.show();
                    }
                }
            });
        },

        openDescriptionTemplatePopUp: function (contentData) {
            var self = this;
            self.gridHandler.unselectAll();

            MessageObj.clear();

            if (!$('template_description_pop_up_content')) {
                $('html-body').insert({bottom: contentData});
            }

            self.templateDescriptionPopup = jQuery('#template_description_pop_up_content');

            modal({
                title: M2ePro.translator.translate('templateDescriptionPopupTitle'),
                type: 'slide',
                buttons: [{
                    text: M2ePro.translator.translate('Add New Description Policy'),
                    class: 'action primary ',
                    click: function () {
                        ListingGridHandlerObj.templateDescriptionHandler.createTemplateDescriptionInNewTab(M2ePro.url.get('newTemplateDescriptionUrl'));
                    }
                }]
            }, self.templateDescriptionPopup);

            self.templateDescriptionPopup.modal('openModal');

            self.templateDescriptionPopup.productsIds = self.variationProductManagePopup.productId;
            self.templateDescriptionPopup.checkIsNewAsinAccepted = 1;

            self.loadTemplateDescriptionGrid();
        },

        loadTemplateDescriptionGrid: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_description/viewGrid'), {
                method: 'post',
                parameters: {
                    products_ids: self.variationProductManagePopup.productId,
                    check_is_new_asin_accepted: 1,
                    map_to_template_js_fn: 'ListingGridHandlerObj.variationProductManageHandler.mapToTemplateDescription'
                },
                onSuccess: function (transport) {
                    $('template_description_grid').update(transport.responseText);
                    $('template_description_grid').show();
                }
            });
        },

        mapToTemplateDescription: function (el, templateId, mapToGeneralId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_template_description/assign'), {
                method: 'post',
                parameters: {
                    products_ids: self.variationProductManagePopup.productId,
                    template_id: templateId
                },
                onSuccess: function (transport) {
                    var response = self.parseResponse(transport);
                    if (response.type == 'success') {
                        self.templateDescriptionPopup.modal('closeModal');
                        self.setGeneralIdOwner(1, true);
                    }
                }
            });

            self.templateDescriptionPopup.modal('closeModal');
        },

        // ---------------------------------------

        changeVariationTheme: function (el) {
            var attrs = $('variation_manager_theme_attributes');
            attrs.hide();
            attrs.next().show();

            el.hide();
            el.next().show();

            var channelVariationThemeNote = $('channel_variation_theme_note');
            channelVariationThemeNote && channelVariationThemeNote.hide();
        },

        setVariationTheme: function () {
            var self = this,
                value = $('variation_manager_theme').value;

            if (value) {
                new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/setVariationTheme'), {
                    method: 'post',
                    parameters: {
                        product_id: self.variationProductManagePopup.productId,
                        variation_theme: value
                    },
                    onSuccess: function (transport) {
                        var response = self.parseResponse(transport);
                        if (response.success) {
                            self.reloadSettings();
                        }
                    }
                });
            }
        },

        cancelVariationTheme: function (el) {
            var attrs = $('variation_manager_theme_attributes');
            attrs.show();
            attrs.next().hide();

            el.up().previous().show();
            el.up().hide();

            var channelVariationThemeNote = $('channel_variation_theme_note');
            channelVariationThemeNote && channelVariationThemeNote.show();
        },

        // ---------------------------------------

        changeMatchedAttributes: function (el) {
            $$('.variation_manager_attributes_amazon_value').each(function (el) {
                el.hide();
            });

            $$('.variation_manager_attributes_amazon_select').each(function (el) {
                el.show();
            });

            el.hide();
            el.next().show();
            el.next().next().show();
        },

        // ---------------------------------------

        isValidAttributes: function () {
            var self = this,
                existedValues = [],
                isValid = true,
                form = $('variation_manager_attributes_form');

            if (!form || (form && form.serialize() == '')) {
                return true;
            }
            var data = form.serialize(true);

            form.select('.validation-advice').each(function (el) {
                el.hide();
            });

            if (typeof data['variation_attributes[amazon_attributes][]'] == 'string') {

                if (data['variation_attributes[amazon_attributes][]'] != '') {
                    return true;
                }

                var errorEl = form.select('.validation-advice')[0];
                errorEl.show();
                errorEl.update(M2ePro.translator.translate('variation_manage_matched_attributes_error'));

                return false;
            }

            var i = 0;
            data['variation_attributes[amazon_attributes][]'].each(function (attrVal) {
                if (attrVal != '' && existedValues.indexOf(attrVal) === -1) {
                    existedValues.push(attrVal);
                } else {
                    isValid = false;

                    var errorEl = $('variation_manager_attributes_error_' + i);
                    errorEl.show();
                    if (attrVal == '') {
                        errorEl.update(M2ePro.translator.translate('variation_manage_matched_attributes_error'));
                    } else {
                        errorEl.update(M2ePro.translator.translate('variation_manage_matched_attributes_error_duplicate'))
                    }
                }
                i++;
            });

            return isValid;
        },

        setMatchedAttributes: function () {
            var self = this,
                data;

            if (!self.isValidAttributes()) {
                return;
            }

            $('variation_manager_attributes_form').select('.validation-advice').each(function (el) {
                el.hide();
            });

            data = $('variation_manager_attributes_form').serialize(true);
            data.product_id = self.variationProductManagePopup.productId;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/setMatchedAttributes'), {
                method: 'post',
                parameters: data,
                onSuccess: function (transport) {
                    var response = self.parseResponse(transport);
                    if (response.success) {
                        self.reloadVariationsGrid();

                        if (response['vocabulary_attributes']) {
                            self.openVocabularyAttributesPopUp(response['vocabulary_attributes']);
                        }
                    }
                }
            });
        },

        cancelMatchedAttributes: function (el) {
            $$('.variation_manager_attributes_amazon_value').each(function (el) {
                el.show();
            });

            $$('.variation_manager_attributes_amazon_select').each(function (el) {
                el.hide();
            });

            $('variation_manager_attributes_form').select('.validation-advice').each(function (el) {
                el.hide();
            });

            el.hide();
            el.previous().show();
            el.next().hide();
        },

        // ---------------------------------------

        renderMatchedAttributesNotSetView: function (type) {
            var self = this,
                form = $('variation_manager_attributes_form'),
                tBody = form.down('tbody');

            tBody.update();

            $H(self.matchedAttributes).each(function (attribute) {
                var tr = new Element('tr'),
                    tdLabel = new Element('td', {
                        class: 'label',
                        style: 'border-right: 1px solid #D6D6D6 !important;'
                    }),
                    label = new Element('label'),
                    tdValue = new Element('td', {
                        class: 'value'
                    }),
                    valueSpan = new Element('span', {
                        style: 'color: red;'
                    });

                label.innerHTML = attribute.key;
                valueSpan.innerHTML = M2ePro.translator.translate('not_set');

                tdLabel.insert({bottom: label});
                tdValue.insert({bottom: valueSpan});

                tr.insert({bottom: tdLabel});
                tr.insert({bottom: tdValue});

                tBody.insert({bottom: tr});
            });

            var tr = new Element('tr'),
                tdBtns = new Element('td', {
                    class: 'label',
                    colspan: '2',
                    style: 'text-align: right; border: none'
                }),
                setBtn = new Element('button', {class: 'action primary'});

            setBtn.update(M2ePro.translator.translate('set_attributes'));
            setBtn.observe('click', function (event) {
                if (self.matchingType === self.MATCHING_TYPE_VIRTUAL_AMAZON) {
                    self.renderMatchedAttributesVirtualAmazonView();
                }

                if (self.matchingType === self.MATCHING_TYPE_VIRTUAL_MAGENTO) {
                    self.renderMatchedAttributesVirtualMagentoView();
                }
            });

            tdBtns.insert({bottom: setBtn});
            tr.insert({bottom: tdBtns});
            tBody.insert({bottom: tr});

        },

        renderMatchedAttributesVirtualAmazonView: function () {
            var self = this,
                form = $('variation_manager_attributes_form'),
                tBody = form.down('tbody');

            tBody.update();
            self.selectedDestinationAttributes = [];

            var prematchedAttributes = [];
            var i = 0;
            $H(self.matchedAttributes).each(function (attribute) {

                var tr = new Element('tr'),
                    tdLabel = new Element('td', {
                        class: 'label value',
                        style: 'border-right: 1px solid #D6D6D6 !important; vertical-align: inherit;'
                    }),
                    labelMagentoAttr = new Element('label', {
                        class: 'magento-attribute-name'
                    }),
                    inputVirtualAttribute = new Element('input', {
                        style: 'display: none',
                        value: attribute.key,
                        type: 'hidden',
                        disabled: 'disabled',
                        class: 'virtual-amazon-attribute-name-value',
                        name: 'variation_attributes[virtual_amazon_attributes][' + i + ']'
                    }),
                    selectVirtualAttributeOption = new Element('select', {
                        style: 'display: none; width: 200px; margin-top: -3px',
                        disabled: 'disabled',
                        class: 'admin__control-select required-entry virtual-amazon-option',
                        name: 'variation_attributes[virtual_amazon_option][' + i + ']'
                    }),
                    selectVirtualAttributeOptionGroup = new Element('optgroup', {
                        label: attribute.key
                    }),
                    labelVirtualAttributeAndOption = new Element('span', {
                        style: 'display: none; line-height: 20px; vertical-align: baseline;',
                        class: 'virtual-amazon-attribute-and-option'
                    }),
                    spanLeftHelpIcon = new Element('span', {
                        style: 'display: none',
                        class: 'left-help-icon'
                    }),
                    tdValue = new Element('td', {
                        class: 'value'
                    }),
                    inputMagentoAttr = new Element('input', {
                        value: attribute.key,
                        type: 'hidden',
                        class: 'magento-attribute-name-value',
                        name: 'variation_attributes[magento_attributes][' + i + ']'
                    }),
                    selectAmazonAttr = new Element('select', {
                        class: 'admin__control-select required-entry M2ePro-amazon-attribute-unique-value amazon-attribute-name',
                        name: 'variation_attributes[amazon_attributes][' + i + ']'
                    }),
                    spanVirtualAttribute = new Element('span', {
                        style: 'display: none; line-height: 20px; vertical-align: baseline;',
                        class: 'virtual-amazon-attribute-name'
                    }),
                    spanRightHelpIcon = new Element('span', {
                        style: 'display: none',
                        class: 'right-help-icon'
                    });

                var helpIconTpl = $('variation_settings_form_help_icon_tpl');

                spanLeftHelpIcon.update(helpIconTpl.innerHTML);
                spanLeftHelpIcon.down('.tool-tip-message-text').update(M2ePro.translator.translate('help_icon_magento_greater_left'));
                spanRightHelpIcon.update(helpIconTpl.innerHTML);
                spanRightHelpIcon.down('.tool-tip-message-text').update(M2ePro.translator.translate('help_icon_magento_greater_right'));

                var attributeStr = attribute.key;
                if (attribute.key.length > 13) {
                    attributeStr = attribute.key.substr(0, 12) + '...';
                    labelVirtualAttributeAndOption.title = attribute.key;
                    spanVirtualAttribute.title = attribute.key;
                }

                if (attribute.key.length < 31) {
                    labelMagentoAttr.update(attribute.key);
                } else {
                    labelMagentoAttr.update(attribute.key.substr(0, 28) + '...');
                    labelMagentoAttr.title = attribute.key;
                }

                spanVirtualAttribute.update(attributeStr + ' (<span>&ndash;</span>)');
                labelVirtualAttributeAndOption.update(attributeStr + ' (<a href="javascript:void(0);"></a>)');
                labelVirtualAttributeAndOption.down('a').title = '';

                labelVirtualAttributeAndOption.down('a').observe('click', function (event) {
                    labelVirtualAttributeAndOption.hide();
                    selectVirtualAttributeOption.show();
                    selectVirtualAttributeOption.value = '';
                    spanVirtualAttribute.down('span').update('&ndash;');
                    spanVirtualAttribute.down('span').title = '';
                });

                var option = new Element('option', {
                    value: ''
                });
                selectAmazonAttr.insert({bottom: option});

                self.destinationAttributes.each(function (destinationAttribute) {
                    var option = new Element('option', {
                        value: destinationAttribute
                    });
                    option.update(destinationAttribute);
                    selectAmazonAttr.insert({bottom: option});

                    if (attribute.value == destinationAttribute) {
                        selectAmazonAttr.value = destinationAttribute;
                        prematchedAttributes.push(selectAmazonAttr);
                    }
                });
                selectAmazonAttr.prevValue = '';

                selectAmazonAttr.observe('change', function (event) {

                    var result = true;
                    if (selectAmazonAttr.value != '' && inputMagentoAttr.value != selectAmazonAttr.value &&
                        self.productAttributes.indexOf(selectAmazonAttr.value) !== -1) {
                        result = false;

                        if (attribute.value == null) {
                            self.alert(M2ePro.translator.translate('duplicate_amazon_attribute_error'));
                        }
                        selectAmazonAttr.value = '';
                    }
                    attribute.value = null;

                    var prevValueIndex = self.selectedDestinationAttributes.indexOf(selectAmazonAttr.prevValue);
                    if (prevValueIndex > -1) {
                        self.selectedDestinationAttributes.splice(prevValueIndex, 1);
                    }

                    if (selectAmazonAttr.value != '') {
                        self.selectedDestinationAttributes.push(selectAmazonAttr.value);
                    }
                    selectAmazonAttr.prevValue = selectAmazonAttr.value;

                    form.select('select').each(function (el) {
                        result = Validation.get('M2ePro-amazon-attribute-unique-value').test($F(el), el) ? result : false;
                    });

                    if (result && self.selectedDestinationAttributes.length == self.destinationAttributes.length) {
                        self.showVirtualAmazonAttributes();
                    } else {
                        self.hideVirtualAmazonAttributes();
                    }
                });

                selectVirtualAttributeOption.observe('change', function (event) {
                    var value = selectVirtualAttributeOption.value;

                    labelVirtualAttributeAndOption.show();
                    selectVirtualAttributeOption.hide();

                    if (attributeStr.length + value.length < 28) {
                        spanVirtualAttribute.down('span').update(value);
                        spanVirtualAttribute.down('span').title = '';
                        labelVirtualAttributeAndOption.down('a').update(value);
                    } else {
                        spanVirtualAttribute.down('span').update(value.substr(0, 24 - attributeStr.length) + '...');
                        spanVirtualAttribute.down('span').title = value;
                        labelVirtualAttributeAndOption.down('a').update(value.substr(0, 24 - attributeStr.length) + '...');
                    }

                    labelVirtualAttributeAndOption.down('a').title = M2ePro.translator.translate('change_option') + ' "' + value + '"';
                });

                var option = new Element('option', {
                    value: ''
                });
                selectVirtualAttributeOption.insert({bottom: option});

                self.magentoVariationSet[attribute.key].each(function (optionValue) {
                    var option = new Element('option', {
                        value: optionValue
                    });
                    option.update(optionValue);
                    selectVirtualAttributeOptionGroup.insert({bottom: option});
                });
                selectVirtualAttributeOption.insert({bottom: selectVirtualAttributeOptionGroup});

                tdLabel.insert({bottom: labelMagentoAttr});
                tdLabel.insert({bottom: inputVirtualAttribute});
                tdLabel.insert({bottom: labelVirtualAttributeAndOption});
                tdLabel.insert({bottom: selectVirtualAttributeOption});
                tdLabel.insert({bottom: spanLeftHelpIcon});
                tdValue.insert({bottom: inputMagentoAttr});
                tdValue.insert({bottom: selectAmazonAttr});
                tdValue.insert({bottom: spanVirtualAttribute});
                tdValue.insert({bottom: spanRightHelpIcon});

                tr.insert({bottom: tdLabel});
                tr.insert({bottom: tdValue});

                tBody.insert({bottom: tr});

                i++;
            });

            var tr = new Element('tr'),
                tdBtns = new Element('td', {
                    class: 'label',
                    colspan: '2',
                    style: 'text-align: right; border: none'
                }),
                cancelBtn = new Element('a', {
                    href: 'javascript:void(0);',
                    style: 'margin-left: 9px;'
                }),
                confirmBtn = new Element('button', {
                    style: 'margin-left: 9px;',
                    class: 'action primary'
                });

            cancelBtn.update(M2ePro.translator.translate('Cancel'));
            confirmBtn.update(M2ePro.translator.translate('Confirm'));

            cancelBtn.observe('click', function (event) {
                self.renderMatchedAttributesNotSetView();
            });
            confirmBtn.observe('click', function (event) {
                form.select('.validation-advice').each(function (el) {
                    el.hide();
                });

                var result = true;
                form.select('select').each(function (el) {
                    el.classNames().each(function (className) {
                        var validationResult = Validation.test(className, el);
                        result = validationResult ? result : false;

                        if (!validationResult) {
                            throw $break;
                        }
                    });
                });

                form.select('.validation-advice').each(function (el) {
                    jQuery(el).wrapInner('<label class="mage-error">');
                });

                if (result) {
                    var data = form.serialize(true);
                    data.product_id = self.variationProductManagePopup.productId;

                    new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/setMatchedAttributes'), {
                        method: 'post',
                        parameters: data,
                        onSuccess: function (transport) {
                            var response = self.parseResponse(transport);
                            if (response.success) {
                                self.reloadVariationsGrid();

                                if (response['vocabulary_attributes']) {
                                    self.openVocabularyAttributesPopUp(response['vocabulary_attributes']);
                                }
                            }
                        }
                    });
                }
            });

            tdBtns.insert({bottom: cancelBtn});
            tdBtns.insert({bottom: confirmBtn});
            tr.insert({bottom: tdBtns});
            tBody.insert({bottom: tr});

            prematchedAttributes.each(function (el) {
                el.simulate('change');
            });

            tBody.select('.tool-tip-image').each(function (element) {
                element.observe('mouseover', MagentoFieldTipObj.showToolTip);
                element.observe('mouseout', MagentoFieldTipObj.onToolTipIconMouseLeave);
            });

            tBody.select('.tool-tip-message').each(function (element) {
                element.observe('mouseout', MagentoFieldTipObj.onToolTipMouseLeave);
                element.observe('mouseover', MagentoFieldTipObj.onToolTipMouseEnter);
            });
        },

        showVirtualAmazonAttributes: function () {
            var self = this,
                form = $('variation_manager_attributes_form');

            var virtualAmazonAttr = form.select('select.amazon-attribute-name[value=""]');
            virtualAmazonAttr.each(function (el) {
                el.disable().hide();

                var tr = el.up('tr');
                tr.down('.magento-attribute-name-value').disable();
                tr.down('.virtual-amazon-attribute-name').show();
                tr.down('.magento-attribute-name').hide();
                tr.down('.virtual-amazon-attribute-name-value').enable();
                tr.down('.virtual-amazon-option').enable().show();
                tr.down('.right-help-icon').show();
                tr.down('.left-help-icon').show();
            });
        },

        hideVirtualAmazonAttributes: function () {
            var self = this,
                form = $('variation_manager_attributes_form');

            var virtualAmazonAttr = form.select('select.amazon-attribute-name[value=""]');
            virtualAmazonAttr.each(function (el) {
                el.enable().show();

                var tr = el.up('tr');
                tr.down('.magento-attribute-name-value').enable();
                tr.down('.virtual-amazon-attribute-name').hide();
                tr.down('.magento-attribute-name').show();
                tr.down('.virtual-amazon-attribute-name-value').disable();
                tr.down('.virtual-amazon-option').disable().hide();
                tr.down('.virtual-amazon-attribute-and-option').hide();
                tr.down('.right-help-icon').hide();
                tr.down('.left-help-icon').hide();
            });
        },

        // ---------------------------------------

        renderMatchedAttributesVirtualMagentoView: function () {
            var self = this,
                form = $('variation_manager_attributes_form'),
                tBody = form.down('tbody');

            tBody.update();

            var prematchedAttributes = [];
            var i = 0;
            $H(self.matchedAttributes).each(function (attribute) {

                var tr = new Element('tr'),
                    tdLabel = new Element('td', {
                        class: 'label',
                        style: 'border-right: 1px solid #D6D6D6 !important;'
                    }),
                    labelMagentoAttr = new Element('label'),
                    tdValue = new Element('td', {
                        class: 'value'
                    }),
                    inputMagentoAttr = new Element('input', {
                        value: attribute.key,
                        type: 'hidden',
                        name: 'variation_attributes[magento_attributes][' + i + ']'
                    }),
                    selectAmazonAttr = new Element('select', {
                        class: 'admin__control-select required-entry M2ePro-amazon-attribute-unique-value amazon-attribute-name',
                        name: 'variation_attributes[amazon_attributes][' + i + ']'
                    });

                if (attribute.key.length < 31) {
                    labelMagentoAttr.update(attribute.key);
                } else {
                    labelMagentoAttr.update(attribute.key.substr(0, 28) + '...');
                    labelMagentoAttr.title = attribute.key;
                }

                var option = new Element('option', {
                    value: ''
                });
                selectAmazonAttr.insert({bottom: option});

                self.destinationAttributes.each(function (destinationAttribute) {
                    var option = new Element('option', {
                        value: destinationAttribute
                    });
                    option.update(destinationAttribute);
                    selectAmazonAttr.insert({bottom: option});

                    if (attribute.value == destinationAttribute) {
                        selectAmazonAttr.value = destinationAttribute;
                        prematchedAttributes.push(selectAmazonAttr);
                    }
                });
                selectAmazonAttr.prevValue = '';

                selectAmazonAttr.observe('change', function (event) {
                    var result = true;
                    if (selectAmazonAttr.value != '' && inputMagentoAttr.value != selectAmazonAttr.value &&
                        self.destinationAttributes.indexOf(inputMagentoAttr.value) !== -1) {
                        result = false;

                        if (attribute.value == null) {
                            self.alert(M2ePro.translator.translate('duplicate_magento_attribute_error'));
                        }
                        selectAmazonAttr.value = '';
                    }
                    attribute.value = null;

                    form.select('select.amazon-attribute-name').each(function (el) {
                        el.classNames().each(function (className) {
                            var v = Validation.get(className),
                                validationResult = v.test($F(el), el);

                            result = validationResult ? result : false;

                            if (!validationResult) {
                                throw $break;
                            }
                        });
                    });

                    if (result) {
                        self.showVirtualMagentoAttributes(i);
                    } else {
                        self.hideVirtualMagentoAttributes();
                    }
                });

                tdLabel.insert({bottom: labelMagentoAttr});
                tdValue.insert({bottom: inputMagentoAttr});
                tdValue.insert({bottom: selectAmazonAttr});

                tr.insert({bottom: tdLabel});
                tr.insert({bottom: tdValue});

                tBody.insert({bottom: tr});

                i++;
            });

            var tr = new Element('tr', {
                    class: 'buttons-row'
                }),
                tdBtns = new Element('td', {
                    class: 'label',
                    colspan: '2',
                    style: 'text-align: right; border: none'
                }),
                cancelBtn = new Element('a', {
                    href: 'javascript:void(0);',
                    style: 'margin-left: 9px;'
                }),
                confirmBtn = new Element('button', {
                    style: 'margin-left: 9px;',
                    class: 'action primary'
                });

            cancelBtn.update(M2ePro.translator.translate('Cancel'));
            confirmBtn.update(M2ePro.translator.translate('Confirm'));

            cancelBtn.observe('click', function (event) {
                self.renderMatchedAttributesNotSetView();
            });
            confirmBtn.observe('click', function (event) {
                form.select('.validation-advice').each(function (el) {
                    el.hide();
                });

                var result = true;
                form.select('select', 'input').each(function (el) {
                    el.classNames().each(function (className) {
                        var validationResult = Validation.test(className, el);
                        result = validationResult ? result : false;

                        if (!validationResult) {
                            throw $break;
                        }
                    });
                });

                form.select('.validation-advice').each(function (el) {
                    jQuery(el).wrapInner('<label class="mage-error">');
                });

                if (result) {
                    var data = form.serialize(true);
                    data.product_id = self.variationProductManagePopup.productId;

                    new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/setMatchedAttributes'), {
                        method: 'post',
                        parameters: data,
                        onSuccess: function (transport) {
                            var response = self.parseResponse(transport);
                            if (response.success) {
                                self.reloadVariationsGrid();

                                if (response['vocabulary_attributes']) {
                                    self.openVocabularyAttributesPopUp(response['vocabulary_attributes']);
                                }
                            }
                        }
                    });
                }
            });

            tdBtns.insert({bottom: cancelBtn});
            tdBtns.insert({bottom: confirmBtn});
            tr.insert({bottom: tdBtns});
            tBody.insert({bottom: tr});

            prematchedAttributes.each(function (el) {
                el.simulate('change');
            });
        },

        showVirtualMagentoAttributes: function (lastAttributeIndex) {
            var self = this,
                form = $('variation_manager_attributes_form'),
                buttonsRow = form.down('.buttons-row');

            form.select('tr.virtual-attribute').each(function (el) {
                el.remove();
            });

            var selectedValues = [];
            form.select('select.amazon-attribute-name').each(function (el) {
                selectedValues.push(el.value);
            });

            var i = lastAttributeIndex;
            self.destinationAttributes.each(function (attribute) {
                if (selectedValues.indexOf(attribute) !== -1) {
                    return true;
                }
                var tr = new Element('tr', {
                        class: 'virtual-attribute'
                    }),
                    tdLabel = new Element('td', {
                        class: 'label',
                        style: 'line-height: 26px; border-right: 1px solid #D6D6D6 !important;'
                    }),
                    labelMagentoAttr = new Element('label'),
                    spanLeftHelpIcon = new Element('span', {
                        class: 'left-help-icon'
                    }),
                    tdValue = new Element('td', {
                        class: 'value'
                    }),
                    inputMagentoAttr = new Element('input', {
                        value: attribute,
                        type: 'hidden',
                        name: 'variation_attributes[virtual_magento_attributes][' + i + ']'
                    }),
                    spanVirtualAttribute = new Element('span', {
                        style: 'display: none'
                    }),
                    spanRightHelpIcon = new Element('span', {
                        class: 'right-help-icon'
                    });

                var helpIconTpl = $('variation_settings_form_help_icon_tpl');

                spanLeftHelpIcon.update(helpIconTpl.innerHTML);
                spanLeftHelpIcon.down('.tool-tip-message-text').update(M2ePro.translator.translate('help_icon_amazon_greater_left'));
                spanRightHelpIcon.update(helpIconTpl.innerHTML);
                spanRightHelpIcon.down('.tool-tip-message-text').update(M2ePro.translator.translate('help_icon_amazon_greater_right'));

                var attributeStr = attribute;
                if (attribute.length > 13) {
                    attributeStr = attribute.substr(0, 12) + '...';
                    labelMagentoAttr.title = attribute;
                    spanVirtualAttribute.title = attribute;
                }

                labelMagentoAttr.update(attributeStr + ' (<span>&ndash;</span>)');

                if (self.amazonVariationSet === false) {
                    spanVirtualAttribute.update(attributeStr + ' (<span></span>)');
                    spanVirtualAttribute.show();

                    var virtualAttrOption = new Element('input', {
                        type: 'text',
                        style: 'width: 153px;',
                        class: 'required-entry',
                        name: 'variation_attributes[virtual_magento_option][' + i + ']'
                    });

                    virtualAttrOption.observe('keyup', function (event) {
                        var value = virtualAttrOption.value;

                        if (value == '') {
                            labelMagentoAttr.down('span').update('&ndash;');
                            labelMagentoAttr.down('span').title = '';
                            return;
                        }

                        if (attributeStr.length + value.length < 21) {
                            labelMagentoAttr.down('span').update(value);
                            labelMagentoAttr.down('span').title = '';
                        } else {
                            labelMagentoAttr.down('span').update(value.substr(0, 20 - attributeStr.length) + '...');
                            labelMagentoAttr.down('span').title = value;
                        }
                    });

                    labelMagentoAttr.insert({bottom: spanLeftHelpIcon});
                    spanVirtualAttribute.down('span').insert({bottom: virtualAttrOption});

                    tdLabel.insert({bottom: labelMagentoAttr});
                    tdValue.insert({bottom: inputMagentoAttr});
                    tdValue.insert({bottom: spanVirtualAttribute});
                    tdValue.insert({bottom: spanRightHelpIcon});
                } else {
                    var virtualAttrOption = new Element('select', {
                            style: 'width: 255px;',
                            class: 'admin__control-select required-entry virtual-magento-option',
                            name: 'variation_attributes[virtual_magento_option][' + i + ']'
                        }),
                        virtualAttrOptionGroup = new Element('optgroup', {
                            label: attribute
                        });

                    spanVirtualAttribute.update(attributeStr + ' (<a href="javascript:void(0);"></a>)');
                    spanVirtualAttribute.down('a').title = '';

                    spanVirtualAttribute.down('a').observe('click', function (event) {
                        spanVirtualAttribute.hide();
                        virtualAttrOption.show();
                        virtualAttrOption.value = '';
                        labelMagentoAttr.down('span').update('&ndash;');
                        labelMagentoAttr.down('span').title = '';
                    });

                    var option = new Element('option', {
                        value: ''
                    });
                    virtualAttrOption.insert({bottom: option});

                    self.amazonVariationSet[attribute].each(function (optionValue) {
                        var option = new Element('option', {
                            value: optionValue
                        });
                        option.update(optionValue);
                        virtualAttrOptionGroup.insert({bottom: option});
                    });
                    virtualAttrOption.insert({bottom: virtualAttrOptionGroup});

                    virtualAttrOption.observe('change', function (event) {
                        var value = virtualAttrOption.value;

                        spanVirtualAttribute.show();
                        virtualAttrOption.hide();

                        if (attributeStr.length + value.length < 28) {
                            labelMagentoAttr.down('span').update(value);
                            labelMagentoAttr.down('span').title = '';
                        } else {
                            labelMagentoAttr.down('span').update(value.substr(0, 22 - attributeStr.length) + '...');
                            labelMagentoAttr.down('span').title = value;
                        }

                        if (attributeStr.length + value.length < 45) {
                            spanVirtualAttribute.down('a').update(value);
                        } else {
                            spanVirtualAttribute.down('a').update(value.substr(0, 40 - attributeStr.length) + '...');
                        }
                        spanVirtualAttribute.down('a').title = M2ePro.translator.translate('change_option') + ' "' + value + '"';
                    });

                    labelMagentoAttr.insert({bottom: spanLeftHelpIcon});
                    tdLabel.insert({bottom: labelMagentoAttr});
                    tdValue.insert({bottom: inputMagentoAttr});
                    tdValue.insert({bottom: spanVirtualAttribute});
                    tdValue.insert({bottom: virtualAttrOption});
                    tdValue.insert({bottom: spanRightHelpIcon});
                }

                tr.insert({bottom: tdLabel});
                tr.insert({bottom: tdValue});

                buttonsRow.insert({before: tr});

                i++;
            });

            form.select('.tool-tip-image').each(function (element) {
                element.observe('mouseover', MagentoFieldTipObj.showToolTip);
                element.observe('mouseout', MagentoFieldTipObj.onToolTipIconMouseLeave);
            });

            form.select('.tool-tip-message').each(function (element) {
                element.observe('mouseout', MagentoFieldTipObj.onToolTipMouseLeave);
                element.observe('mouseover', MagentoFieldTipObj.onToolTipMouseEnter);
            });
        },

        hideVirtualMagentoAttributes: function () {
            var self = this,
                form = $('variation_manager_attributes_form');

            form.select('tr.virtual-attribute').each(function (el) {
                el.remove();
            });
        },

        // ---------------------------------------

        reloadSettings: function (callback) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/viewVariationsSettingsAjax'), {
                method: 'post',
                parameters: {
                    product_id: self.variationProductManagePopup.productId
                },
                onSuccess: function (transport) {

                    var response = self.parseResponse(transport);

                    $('amazonVariationProductManageTabs_settings_content').update(response.html);
                    self.initSettingsTab();

                    var img = $('amazonVariationProductManageTabs_settings').down('img');

                    img.hide();
                    if (response.error_icon != '') {
                        img.src = M2ePro.url.get('m2epro_skin_url') + '/images/' + response.error_icon + '.png';
                        img.show();
                    }

                    if (callback) {
                        callback.call();
                    }
                }
            });
        },

        reloadVariationsGrid: function () {
            ListingProductVariationManageVariationsGridObj.actionHandler.gridHandler.unselectAllAndReload();
        },

        // ---------------------------------------

        openVariationsTab: function (createNewAsin) {
            jQuery('#amazonVariationProductManageTabs').tabs('option', 'active', 0);
            ListingProductVariationManageVariationsGridObj.showNewChildForm(
                createNewAsin,
                ListingGridHandlerObj.variationProductManageHandler.variationProductManagePopup.productId
            );
        },

        // ---------------------------------------

        reloadVocabulary: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/viewVocabularyAjax'), {
                method: 'post',
                parameters: {
                    product_id: self.variationProductManagePopup.productId
                },
                onSuccess: function (transport) {
                    $('amazonVariationProductManageTabs_vocabulary_content').update(transport.responseText);
                }
            });
        },

        saveAutoActionSettings: function () {
            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_manage/saveAutoActionSettings'), {
                method: 'post',
                parameters: $('auto_action_settings_form').serialize(true)
            });
        },

        removeAttributeFromVocabulary: function (el) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/removeAttribute'), {
                            method: 'post',
                            parameters: {
                                magento_attr: decodeHtmlentities(el.up().down('.magento-attribute-name').innerHTML),
                                channel_attr: decodeHtmlentities(el.up().down('.channel-attribute-name').innerHTML)
                            },
                            onSuccess: function (transport) {
                                self.reloadVocabulary();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        removeOptionFromVocabulary: function (el) {
            var self = this,
                optionGroupRowEl = el.up('.channel-attribute-options-group');

            self.confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_vocabulary/removeOption'), {
                            method: 'post',
                            parameters: {
                                product_option: decodeHtmlentities(optionGroupRowEl.down('.product-option').innerHTML),
                                product_options_group: decodeHtmlentities(optionGroupRowEl.down('.product-options-group').innerHTML),
                                channel_attr: decodeHtmlentities(optionGroupRowEl.down('.channel-attribute-name').innerHTML)
                            },
                            onSuccess: function (transport) {
                                self.reloadVocabulary();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        }

        // ---------------------------------------
    });

});