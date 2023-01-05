define([
    'jquery'
], function (jQuery) {
    window.TemplateHelperPriceChange = Class.create(Common, {
        priceChange: {},

        constAbsoluteIncrease: M2ePro.php.constant(
            '\\Ess\\M2ePro\\Model\\Template\\SellingFormat::PRICE_MODIFIER_ABSOLUTE_INCREASE'
        ),
        constAbsoluteDecrease: M2ePro.php.constant(
            '\\Ess\\M2ePro\\Model\\Template\\SellingFormat::PRICE_MODIFIER_ABSOLUTE_DECREASE'
        ),
        constPercentageIncrease: M2ePro.php.constant(
            '\\Ess\\M2ePro\\Model\\Template\\SellingFormat::PRICE_MODIFIER_PERCENTAGE_INCREASE'
        ),
        constPercentageDecrease: M2ePro.php.constant(
            '\\Ess\\M2ePro\\Model\\Template\\SellingFormat::PRICE_MODIFIER_PERCENTAGE_DECREASE'
        ),
        constAttribute: M2ePro.php.constant(
            '\\Ess\\M2ePro\\Model\\Template\\SellingFormat::PRICE_MODIFIER_ATTRIBUTE'
        ),

        initialize: function() {
            var self = this;

            jQuery.validator.addMethod('M2ePro-validate-price-modifier', function (value, el) {
                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                var modifier = el.up().down('input');
                modifier.removeClassName('price_unvalidated');

                if (modifier.style.visibility !== 'visible') {
                    return true;
                }

                if (modifier.value == '') {
                    return false;
                }

                var modifierValue = parseFloat(modifier.value);
                if (isNaN(modifierValue) || modifierValue <= 0) {
                    modifier.addClassName('price_unvalidated');
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Price Change is not valid.'));
        },

        initPriceChange: function (priceChange) {
            this.priceChange = priceChange;

            var template, button, handler;
            for (const [type, value] of Object.entries(this.priceChange)) {
                template = $(type + '_change_row_template');
                button = $(type + '_change_add_row_button');
                if (!template || !button) {
                    this.priceChange[type]['enabled'] = false;
                    continue;
                }

                // load row templates
                this.priceChange[type]['template'] = template.innerHTML;
                template.remove();

                // init onclick observers
                handler = function () {
                    this.addPriceChangeRow(type);
                }.bind(this);
                button.observe('click', handler);
            }
        },

        renderPriceChangeRows: function (type, data) {
            if (!this.priceChange[type]['enabled']) {
                return;
            }

            for (var i = 0; i < data.length; i++) {
                this.addPriceChangeRow(type, data[i]);
            }
        },

        addPriceChangeRow: function (type, rowData) {
            this.priceChange[type]['index']++;
            var rowIndex = this.priceChange[type]['index'],
                template = this.priceChange[type]['template'],
                priceChangeContainer = $(type + '_change_container');

            rowData = rowData || {};
            template = template.replace(/%index%/g, rowIndex);
            priceChangeContainer.insert(template);

            var modeElement = $(type + '_modifier_mode_' + rowIndex),
                valueElement = $(type + '_modifier_value_' + rowIndex),
                removeButtonElement = $(type + '_modifier_row_remove_button_' + rowIndex);

            var handlerObj = new AttributeCreator(type + '_modifier_mode_' + rowIndex);
            handlerObj.setSelectObj(modeElement);
            handlerObj.injectAddOption();

            if (rowData.mode) {
                var elementAttributeCode;
                for (var i = 0; i < modeElement.options.length; i++) {
                    if (modeElement.options[i].value != rowData.mode) {
                        continue;
                    }

                    if (modeElement.options[i].value < this.constAttribute) {
                        modeElement.selectedIndex = i;
                        valueElement.value = rowData['value'];
                        break;
                    } else {
                        elementAttributeCode = modeElement.options[i].getAttribute('attribute_code');
                        if (elementAttributeCode == rowData['attribute_code']) {
                            modeElement.selectedIndex = i;
                            valueElement.style.visibility = 'hidden';
                            break;
                        }
                    }
                }
            }

            var selectOnChangeHandler = function () {
                this.priceChangeSelectUpdate(type, modeElement)
            }.bind(this);
            modeElement
                .observe('change', selectOnChangeHandler)
                .simulate('change');

            var inputOnKeyUpHandler = function () {
                this.priceChangeCalculationUpdate(type);
            }.bind(this);
            valueElement.observe('keyup', inputOnKeyUpHandler);

            var buttonOnClickHandler = function () {
                this.removePriceChangeRow(type, removeButtonElement);
            }.bind(this);
            removeButtonElement.observe('click', buttonOnClickHandler);
        },

        removePriceChangeRow: function (type, element) {
            element.up('.price_change_row').remove();
            this.priceChangeCalculationUpdate(type);
        },

        priceChangeSelectUpdate: function (type, element) {
            var datasetKey = this.getDatasetKey(type) + 'ChangeIndex';
            var valueElement = $(type + '_modifier_value_' + element.dataset[datasetKey]),
                attributeElement = $(type + '_modifier_attribute_' + element.dataset[datasetKey]);

            if (element.options[element.selectedIndex].value == this.constAttribute) {
                valueElement.style.visibility = 'hidden';
                this.selectMagentoAttribute(element, attributeElement);
            } else {
                valueElement.style.visibility = 'visible';
                attributeElement.value = '';
            }

            this.priceChangeCalculationUpdate(type);
        },

        getDatasetKey: function (type) {
            return type
                .split('_')
                .map((item, index) => {
                    return index > 0 ? item[0].toUpperCase() + item.slice(1) : item;
                })
                .join('');
        },

        priceChangeCalculationUpdate: function (type) {
            var select, input, selectedOption, currentValue, result = 100, operations = ['$100'];

            $$('#' + type + '_change_container > *').each(function (element) {
                select = element.select('select').first();
                input = element.select('input').first();

                if (select.selectedIndex == -1) {
                    return;
                }

                selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value == this.constAttribute) {
                    result += 7.5;
                    operations.push('+ $7.5');
                    return;
                }

                currentValue = Number.parseFloat(input.value);
                if (isNaN(currentValue) || currentValue <= 0) {
                    return;
                }

                switch (Number.parseInt(selectedOption.value)) {
                    case this.constAbsoluteIncrease:
                        if (!isNaN(input.value)) {
                            result += currentValue;
                            operations.push(`+ $${currentValue}`);
                        }
                        break;
                    case this.constAbsoluteDecrease:
                        if (!isNaN(input.value)) {
                            result -= currentValue;
                            operations.push(`- $${currentValue}`);
                        }
                        break;
                    case this.constPercentageIncrease:
                        if (!isNaN(input.value)) {
                            result *= 1 + currentValue / 100;
                            operations.push(`+ ${currentValue}%`);
                        }
                        break;
                    case this.constPercentageDecrease:
                        if (!isNaN(input.value)) {
                            result *= 1 - currentValue / 100;
                            operations.push(`- ${currentValue}%`);
                        }
                        break;
                }
            }.bind(this));

            const calculationExampleElement = $(type + '_calculation_example');
            if (operations.length <= 1) {
                calculationExampleElement.hide();
                return;
            }

            calculationExampleElement.show();
            calculationExampleElement.innerHTML = 'Ex. ' + operations.join(' ') + ' = '
                + this.formatPrice(Math.round(result * 100) / 100, '$');

            calculationExampleElement.style.color = (result <= 0) ? 'red' : 'black';
        },

        formatPrice: function (price, currency) {
            if (isNaN(price)) {
                return currency + 0;
            }

            if (price >= 0) {
                return currency + price;
            } else {
                return '-' + currency + -price;
            }
        },

        selectMagentoAttribute: function (elementSelect, elementAttribute) {
            var attributeCode = elementSelect.options[elementSelect.selectedIndex]
                .getAttribute('attribute_code');
            elementAttribute.value = attributeCode;
        },
    });
});
