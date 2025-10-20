define([
    'jquery',
    'M2ePro/Template/Helper/PriceChange',
    'M2ePro/Walmart/Template/Edit',
    'Magento_Ui/js/modal/modal',
    'mage/calendar'
], function (jQuery) {
    window.WalmartTemplateSellingFormat = Class.create(WalmartTemplateEdit, {

        rulesIndex: 0,
        promotionsIndex: 0,

        taxCodePopup: null,

        priceChange: {
            price: {
                index: 0,
                template: '',
                enabled: true
            }
        },
        priceChangeHelper: null,

        // ---------------------------------------

        initialize: function () {
            var self = this;
            this.setValidationCheckRepetitionValue(
                'M2ePro-price-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Template\\SellingFormat',
                'title',
                'id',
                M2ePro.formData.id,
                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK')
            );

            jQuery.validator.addMethod('M2ePro-validate-price-coefficient', function (value) {

                if (value == '') {
                    return true;
                }

                if (value == '0' || value == '0%') {
                    return false;
                }

                return value.match(/^[+-]?\d+[.]?\d*[%]?$/g);
            }, M2ePro.translator.translate('Coefficient is not valid.'));

            jQuery.validator.addMethod('M2ePro-validate-vat-percent', function (value, el) {

                if (value.length > 6) {
                    return false;
                }

                if (value < 0) {
                    return false;
                }

                value = Math.ceil(value);

                return value > 0 && value <= 30;
            }, M2ePro.translator.translate('wrong_value_more_than_30'));

            jQuery.validator.addMethod('M2ePro-validate-promotions', function (value, el) {

                var mode = +$('promotions_mode').value;

                if (mode === M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::PROMOTIONS_MODE_YES')
                    && WalmartTemplateSellingFormatObj.promotionsIndex === 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('You should specify at least one Promotion.'));

            jQuery.validator.addMethod('M2ePro-correct-date-range', function (value, el) {

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                var fromMode = $(el).up('tr').down('[id^="promotions_from_date_mode_"]').value,
                    toMode   = $(el).up('tr').down('[id^="promotions_to_date_mode_"]').value;

                if (fromMode == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::START_DATE_MODE_ATTRIBUTE') ||
                    toMode == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::START_DATE_MODE_ATTRIBUTE')) {
                    return true;
                }

                var fromDate = $(el).up('tr').down('[id^="promotions_from_date_value_"]').value;
                var toDate   = $(el).up('tr').down('[id^="promotions_to_date_value_"]').value;

                fromDate = Date.parse(fromDate);
                toDate   = Date.parse(toDate);

                return toDate > fromDate;
            }, M2ePro.translator.translate('Date range is not valid.'));

            this.priceChangeHelper = new TemplateHelperPriceChange();
            this.priceChangeHelper.initPriceChange(this.priceChange);
        },

        initObservers: function () {
            $('marketplace_id')
                .observe('change', WalmartTemplateSellingFormatObj.marketplaceIdOnChange)
                .simulate('change');

            $('qty_mode')
                .observe('change', WalmartTemplateSellingFormatObj.qty_mode_change)
                .simulate('change');

            $('qty_modification_mode')
                .observe('change', WalmartTemplateSellingFormatObj.qtyPostedMode_change)
                .simulate('change');

            if ($('price_mode')) {
                $('price_mode')
                    .observe('change', WalmartTemplateSellingFormatObj.price_mode_change)
                    .simulate('change');
            }

            if ($('sale_time_start_date_mode')) {
                $('sale_time_start_date_mode')
                    .observe('change', WalmartTemplateSellingFormatObj.sale_time_start_date_mode_change)
                    .simulate('change');
            }

            if ($('sale_time_end_date_mode')) {
                $('sale_time_end_date_mode')
                    .observe('change', WalmartTemplateSellingFormatObj.sale_time_end_date_mode_change)
                    .simulate('change');
            }

            if ($('price_increase_vat_percent')) {
                $('price_increase_vat_percent')
                    .observe('change', WalmartTemplateSellingFormatObj.price_increase_vat_percent_mode_change)
                    .simulate('change');
            }

            $('promotions_mode')
                .observe('change', WalmartTemplateSellingFormatObj.promotions_mode_change)
                .simulate('change');

            $('item_weight_mode')
                .observe('change', WalmartTemplateSellingFormatObj.item_weight_mode_change)
                .simulate('change');

            $('must_ship_alone_mode')
                .observe('change', WalmartTemplateSellingFormatObj.must_ship_alone_mode_change)
                .simulate('change');

            $('ships_in_original_packaging_mode')
                .observe('change', WalmartTemplateSellingFormatObj.ships_in_original_packaging_mode_change)
                .simulate('change');

            $('lag_time_mode')
                .observe('change', WalmartTemplateSellingFormatObj.lag_time_mode_change)
                .simulate('change');
        },

        // ---------------------------------------

        duplicateClick: function ($super, $headId) {
            this.setValidationCheckRepetitionValue(
                'M2ePro-price-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Template\\SellingFormat',
                'title',
                '',
                '',
                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK')
            );

            $super($headId, M2ePro.translator.translate('Add Selling Policy'));
        },

        // ---------------------------------------

        marketplaceIdOnChange: function () {
            var input = this;
            $$('.m2epro-marketplace-depended-block').each(function (el) {
                input.value ? el.show()
                    : el.hide();
            });
        },

        // ---------------------------------------

        qty_mode_change: function () {
            $('qty_custom_value_tr', 'qty_percentage_tr', 'qty_modification_mode_tr').invoke('hide');

            $('qty_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_NUMBER')) {
                $('qty_custom_value_tr').show();
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE')) {
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, $('qty_custom_attribute'));
            }

            $('qty_modification_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::QTY_MODIFICATION_MODE_OFF');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {

                $('qty_modification_mode_tr').show();

                $('qty_modification_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::QTY_MODIFICATION_MODE_ON');

                if (M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                    M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                    M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {
                    $('qty_modification_mode').value = M2ePro.formData.qty_modification_mode;
                }
            }

            $('qty_modification_mode').simulate('change');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {

                $('qty_percentage_tr').show();
            }
        },

        qtyPostedMode_change: function () {
            $('qty_min_posted_value_tr').hide();
            $('qty_max_posted_value_tr').hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::QTY_MODIFICATION_MODE_ON')) {
                $('qty_min_posted_value_tr').show();
                $('qty_max_posted_value_tr').show();
            }
        },

        // ---------------------------------------

        price_mode_change: function () {
            var self = WalmartTemplateSellingFormatObj;

            $('price_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('price_custom_attribute'));
            }
        },

        // ---------------------------------------

        sale_time_start_date_mode_change: function () {
            $('sale_time_start_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::DATE_VALUE')) {
                $('sale_time_start_date_value_tr').show();
            } else {
                $('sale_time_start_date_value_tr').hide();
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, $('sale_time_start_date_custom_attribute'));
            }

        },

        sale_time_end_date_mode_change: function () {
            $('sale_time_end_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::DATE_VALUE')) {
                $('sale_time_end_date_value_tr').show();
            } else {
                $('sale_time_end_date_value_tr').hide();
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, $('sale_time_end_date_custom_attribute'));
            }

        },

        // ---------------------------------------

        price_increase_vat_percent_mode_change: function () {
            var vatPercentTr = $('price_vat_percent_tr'),
                vatPercent = $('price_vat_percent');

            vatPercentTr.hide();
            vatPercent.removeClassName('M2ePro-validate-vat-percent');
            vatPercent.removeClassName('required-entry');

            if (+this.value) {
                vatPercentTr.show();
                vatPercent.addClassName('M2ePro-validate-vat-percent');
                vatPercent.addClassName('required-entry');
            } else {
                vatPercent.value = '';
            }
        },

        // ---------------------------------------

        lag_time_mode_change: function () {
            var self = WalmartTemplateSellingFormatObj;

            $('lag_time_custom_attribute').value = '';
            $('lag_time_value').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::LAG_TIME_MODE_RECOMMENDED')) {
                self.updateHiddenValue(this, $('lag_time_value'));
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::LAG_TIME_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('lag_time_custom_attribute'));
            }
        },

        // ---------------------------------------

        item_weight_mode_change: function () {
            var self = WalmartTemplateSellingFormatObj;
            self.weightModeChange.call(
                this,
                $('item_weight_custom_value_tr'),
                $('item_weight_custom_attribute'),
                this.value
            );
        },

        weightModeChange: function (customValueTr, customAttribute, value) {
            customValueTr.hide();

            customAttribute.value = '';

            if (value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::WEIGHT_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::WEIGHT_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, customAttribute);
            }
        },

        // ---------------------------------------

        must_ship_alone_mode_change: function () {
            var self = WalmartTemplateSellingFormatObj,
                hiddenElement = $('must_ship_alone_custom_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::MUST_SHIP_ALONE_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, hiddenElement);
            } else {
                hiddenElement.value = '';
            }
        },

        ships_in_original_packaging_mode_change: function () {
            var self = WalmartTemplateSellingFormatObj,
                hiddenElement = $('ships_in_original_packaging_custom_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, hiddenElement);
            } else {
                hiddenElement.value = '';
            }
        },

        // ---------------------------------------

        multi_element_mode_change: function (type, max) {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::' + type.toUpperCase() + '_MODE_NONE')) {

                $$('.' + type + '_tr').invoke('hide');
                $$('input[name="' + type + '_name[]"], input[name="' + type + '_value[]"]').each(function (obj) {
                    obj.value = '';
                });
                $(type + '_actions_tr').hide();

            } else {

                var visibleElementsCounter = 0;

                $$('.' + type + '_tr').each(function (obj) {
                    if (visibleElementsCounter == 0 || $(obj).select('input[name="' + type + '_value[]"]')[0].value != '') {
                        $(obj).show();
                        visibleElementsCounter++;
                    }
                });

                $(type + '_actions_tr').show();

                if (visibleElementsCounter > 1) {
                    $('hide_' + type + '_action').removeClassName('action-disabled');
                }

                visibleElementsCounter < max ? $('show_' + type + '_action').removeClassName('action-disabled')
                    : $('show_' + type + '_action').addClassName('action-disabled');

                if (visibleElementsCounter == 1 && $(type + '_value_0').value == '') {
                    $('show_' + type + '_action').addClassName('action-disabled');
                }
            }
        },

        multi_element_keyup: function (type, element) {
            if (!element.value) {
                return $('show_' + type + '_action').addClassName('action-disabled');
            }

            var nameElement, valueElement;
            nameElement = valueElement = element;

            if (element.id.indexOf('name') !== -1) {
                valueElement = element.up('div').select('#' + element.id.replace('name', 'value'))[0];
            }

            if (element.id.indexOf('value') !== -1) {
                nameElement = element.up('div').select('#' + element.id.replace('value', 'name'))[0];
            }

            if (!nameElement.value || !valueElement.value) {
                return $('show_' + type + '_action').addClassName('action-disabled');
            }

            var hiddenElements = $$('.' + type + '_tr').findAll(function (obj) {
                return !$(obj).visible();
            });

            if (hiddenElements.size() != 0) {
                $('show_' + type + '_action').removeClassName('action-disabled');
            }
        },

        showElement: function (type) {
            var emptyVisibleElementsExist = $$('.' + type + '_tr').any(function (obj) {

                var element = $(obj);

                if (element.select('input[name="' + type + '[]"]')[0]) {
                    return element.visible() && element.select('input[name="' + type + '[]"]')[0].value == '';
                }

                return element.visible() &&
                    (element.select('input[name="' + type + '_name[]"]')[0].value == '' ||
                        element.select('input[name="' + type + '_value[]"]')[0].value == '')
            });

            if (emptyVisibleElementsExist) {
                return;
            }

            var hiddenElements = $$('.' + type + '_tr').findAll(function (obj) {
                return !$(obj).visible();
            });

            if (hiddenElements.size() == 0) {
                return;
            }

            hiddenElements.shift().show();

            $('hide_' + type + '_action').removeClassName('action-disabled');
            $('show_' + type + '_action').addClassName('action-disabled');
        },

        hideElement: function (type, force) {
            force = force || false;

            var visibleElements = [];
            $$('.' + type + '_tr').each(function (el) {
                if (el.visible()) {
                    visibleElements.push(el);
                }
            });

            if (visibleElements.length <= 0 || (!force && visibleElements[visibleElements.length - 1].getAttribute('undeletable'))) {
                return;
            }

            if (visibleElements.length == 1) {
                var elementMode = $(type + '_mode');
                elementMode.value = M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::' + type.toUpperCase() + '_MODE_NONE');
                elementMode.simulate('change');
            }

            if (visibleElements.size() > 1) {

                var lastVisibleElement = visibleElements.pop();
                if (lastVisibleElement.select('input[name="' + type + '[]"]')[0]) {
                    lastVisibleElement.select('input[name="' + type + '[]"]')[0].value = '';
                }
                if (lastVisibleElement.select('input[name="' + type + '_name[]"]')[0]) {
                    lastVisibleElement.select('input[name="' + type + '_name[]"]')[0].value = '';
                }
                if (lastVisibleElement.select('input[name="' + type + '_value[]"]')[0]) {
                    lastVisibleElement.select('input[name="' + type + '_value[]"]')[0].value = '';
                }

                lastVisibleElement.hide();

                var nextVisibleElement = visibleElements.pop();
                if (!force && nextVisibleElement.getAttribute('undeletable')) {
                    $('hide_' + type + '_action').addClassName('action-disabled');
                }
            }

            $('show_' + type + '_action').removeClassName('action-disabled');
        },

        // ---------------------------------------

        promotions_mode_change: function () {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat::PROMOTIONS_MODE_YES')) {
                $('promotions_tr').show();
                $('promotions_table_main_screen').show();
                $('promotions_table').hide();
            } else {
                $('promotions_table_tbody').select('.promotions > td > button.remove_promotion_price_button').each(
                    WalmartTemplateSellingFormatObj.removePromotionsPriceRow
                );

                $('promotions_tr').hide();
                $('promotions_table_main_screen').hide();
                $('promotions_table').show();
            }
        },

        renderPromotions: function (data) {
            var self = this;

            data.each(function (promotion) {
                self.addPromotionsPriceRow(promotion);
            });
        },

        addPromotionsPriceRow: function (promotionData) {
            if ($('promotions_table_main_screen').visible()) {
                $('promotions_table_main_screen').hide();
                $('promotions_table').show();
            }

            var self = this;
            ++this.promotionsIndex;

            var tpl = $('promotions_table_row_template').down('tbody').innerHTML;
            tpl = tpl.replace(/temp-i/g, this.promotionsIndex);
            $('promotions_table_tbody').insert(tpl);

            var row = $('promotions_' + this.promotionsIndex + '_tr');
            row.select('[id^="promotions"]').invoke('enable');
            row.show();

            if (promotionData) {
                this.injectPromotionsData(row, promotionData);
            }

            row.select('.promotions_date_value').invoke('hide');

            row.down('[id^="promotions_price_mode_"]')
               .observe('change', this.updatePromotionsPriceMode)
               .simulate('change');

            row.down('[id^="promotions_from_date_mode_"]')
               .observe('change', this.updatePromotionsFromDateMode)
               .simulate('change');

            row.down('[id^="promotions_to_date_mode_"]')
               .observe('change', this.updatePromotionsToDateMode)
               .simulate('change');

            row.down('[id^="promotions_comparison_price_mode_"]')
               .observe('change', this.updatePromotionsComparisonPriceMode)
               .simulate('change');

            [row.down('[id^="promotions_from_date_value"]'), row.down('[id^="promotions_to_date_value"]')].forEach(function (element) {
                jQuery(element).calendar({
                    dateFormat: "mm/dd/yy",
                    showsTime: false,
                    timeFormat: "HH:mm:ss",
                    buttonText: "Select Date",
                    showButtonPanel: true,
                    singleClick: true
                                         });
            });

            if (WalmartTemplateSellingFormatObj.promotionsIndex >= 10) {
                $$('.add_promotion_price_button').each(function (el) {
                    el.hide();
                });
            }

            row.select('.tool-tip-image').each(function (element) {
                element.observe('mouseover', MagentoFieldTipObj.showToolTip);
                element.observe('mouseout', MagentoFieldTipObj.onToolTipIconMouseLeave);
            });

            row.select('.tool-tip-message').each(function (element) {
                element.observe('mouseout', MagentoFieldTipObj.onToolTipMouseLeave);
                element.observe('mouseover', MagentoFieldTipObj.onToolTipMouseEnter);
            });

            row.down('button.remove_promotion_price_button').addEventListener('click', self.removePromotionsPriceRow.bind(this));

            ['promotions_from_date_mode', 'promotions_to_date_mode', 'promotions_price_mode', 'promotions_comparison_price_mode'].forEach(function (el) {
                var handlerObj = new AttributeCreator(el+'_' + self.promotionsIndex);
                handlerObj.setSelectObj(row.down('.'+el));
                handlerObj.injectAddOption();
            });
        },

        removePromotionsPriceRow: function (el) {
            var targetNode;

            if (el.nodeType === Node.ELEMENT_NODE) {
                targetNode = el.up('.promotions');
            } else {
                targetNode = el.target.up('.promotions');
            }

            targetNode.remove();

            if ($('promotions_table_tbody').select('tr').length == 0) {
                $('promotions_table_main_screen').show();
                $('promotions_table').hide();
            }

            if (WalmartTemplateSellingFormatObj.promotionsIndex > 0) {
                --WalmartTemplateSellingFormatObj.promotionsIndex;
            }

            if (WalmartTemplateSellingFormatObj.promotionsIndex < 10) {
                $$('.add_promotion_price_button').each(function (el) {
                    el.show();
                });
            }
        },

        injectPromotionsData: function (row, data) {
            var selectorsMap = {
                promotions_price_custom_attribute: 'price_attribute',
                promotions_price_mode            : 'price_mode',
                promotions_price_coefficient     : 'price_coefficient',

                promotions_from_date_custom_attribute: 'start_date_attribute',
                promotions_from_date_mode            : 'start_date_mode',
                promotions_from_date_value           : 'start_date_value',

                promotions_to_date_custom_attribute: 'end_date_attribute',
                promotions_to_date_mode            : 'end_date_mode',
                promotions_to_date_value           : 'end_date_value',

                promotions_comparison_price_custom_attribute: 'comparison_price_attribute',
                promotions_comparison_price_mode            : 'comparison_price_mode',
                promotions_comparison_price_coefficient     : 'comparison_price_coefficient',

                promotions_type: 'type'
            };

            $H(selectorsMap).each(function (item) {

                var element = row.down('[id^="' + item.key + '"]');
                element.value = data[item.value];

                if (element.type == 'select-one') {

                    var attributeValue = data[item.value.replace('_mode', '_attribute')];
                    element.select('option').each(function (option) {
                        if (option.getAttribute('attribute_code') == attributeValue) {
                            option.setAttribute('selected', 'selected');
                        }
                    });
                }
            });
        },

        updatePromotionsPriceMode: function () {
            var customAttribute = this.up('td').down('[id^="promotions_price_custom_attribute_"]');

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::PRICE_MODE_ATTRIBUTE')) {
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, customAttribute);
            }
        },

        updatePromotionsFromDateMode: function () {
            var td = this.up('td');

            var customAttribute = td.down('[id^="promotions_from_date_custom_attribute_"]'),
                customValue     = td.down('[id^="promotions_from_date_value"]');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::START_DATE_MODE_VALUE')) {
                td.down('.promotions_date_value').show();
            } else {
                td.down('.promotions_date_value').hide();
                customValue.value = '';
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::START_DATE_MODE_ATTRIBUTE')) {
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, customAttribute);
            } else {
                customAttribute.value = '';
            }
        },

        updatePromotionsToDateMode: function () {
            var td = this.up('td');

            var customAttribute = td.down('[id^="promotions_to_date_custom_attribute_"]'),
                customValue     = td.down('[id^="promotions_to_date_value"]');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::END_DATE_MODE_VALUE')) {
                td.down('.promotions_date_value').show();
            } else {
                td.down('.promotions_date_value').hide();
                customValue.value = '';
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::END_DATE_MODE_ATTRIBUTE')) {
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, customAttribute);
            } else {
                customAttribute.value = '';
            }
        },

        updatePromotionsComparisonPriceMode: function () {
            var customAttribute = this.up('td').down('[id^="promotions_comparison_price_custom_attribute_"]');

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_SellingFormat_Promotion::COMPARISON_PRICE_MODE_ATTRIBUTE')) {
                WalmartTemplateSellingFormatObj.updateHiddenValue(this, customAttribute);
            }
        }
    });
});
