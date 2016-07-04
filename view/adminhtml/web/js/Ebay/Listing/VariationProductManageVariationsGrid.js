define([
    'jquery',
    'M2ePro/Grid'
], function (jQuery) {
    window.EbayListingVariationProductManageVariationsGrid = Class.create(Grid, {

        // ---------------------------------------

        initialize: function($super, gridId)
        {
            $super(gridId);

            jQuery.validator.addMethod('M2ePro-upc', function(value, el) {

                if (value.length == 0) {
                    return true;
                }

                if (value.length != 12) {
                    return false;
                }

                var barcode = value.substr(0, value.length-1);

                var sum = 0;
                var length  = barcode.length-1;

                for (var i = 0; i <= length; i++) {
                    if ((i % 2) === 0) {
                        sum += parseInt(barcode.charAt(length - i) * 3);
                    } else {
                        sum += parseInt(barcode.charAt(length - i));
                    }
                }

                var calc  = sum % 10;
                var checksum = (calc === 0) ? 0 : (10 - calc);

                return value.charAt(length+1) == checksum;
            }, M2ePro.translator.translate('Please enter valid UPC'));

            jQuery.validator.addMethod('M2ePro-ean', function(value, el) {

                if (value.length == 0) {
                    return true;
                }

                if (value.length != 13) {
                    return false;
                }

                var barcode = value.substr(0, value.length-1);

                var sum = 0;
                var length  = barcode.length-1;

                for (var i = 0; i <= length; i++) {
                    if ((i % 2) === 0) {
                        sum += parseInt(barcode.charAt(length - i) * 3);
                    } else {
                        sum += parseInt(barcode.charAt(length - i));
                    }
                }

                var calc  = sum % 10;
                var checksum = (calc === 0) ? 0 : (10 - calc);

                return value.charAt(length+1) == checksum;

            }, M2ePro.translator.translate('Please enter valid EAN'));

            jQuery.validator.addMethod('M2ePro-isbn', function(value, el) {

                if (value.length == 0) {
                    return true;
                }

                if (value.length == 10) {

                    var a = 0;
                    for (var i = 0; i < 10; i++) {
                        if (value.charAt(i) == "X" || value.charAt(i) == "x") {
                            a += 10 * parseInt(10 - i);
                        } else {
                            a += parseInt(value.charAt(i)) * parseInt(10 - i);
                        }
                    }
                    return (a % 11 == 0);

                } else if (value.length == 13) {

                    if (value.substr(0,3) != '978') {
                        return false;
                    }

                    var check = 0;
                    for (var i = 0; i < 13; i += 2) {
                        check += parseInt(value.substr(i,1));
                    }
                    for (var i = 1; i < 12; i += 2) {
                        check += 3 * parseInt(value.substr(i,1));
                    }

                    return check % 10 == 0;
                }

                return false;
            }, M2ePro.translator.translate('Please enter valid ISBN'));

            this.initFormValidation('form[id^="variation_identifiers_edit_"]');
        },

        // ---------------------------------------

        getComponent: function()
        {
            return 'ebay';
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 1000;
        },

        // ---------------------------------------

        prepareActions: function()
        {
            return false;
        },

        // ---------------------------------------

        afterInitPage: function($super)
        {
            $super();

            $$('.attributes-options-filter').each(this.initAttributesOptionsFilter, this);
        },

        // ---------------------------------------

        parseResponse: function(response)
        {
            if (!response.responseText.isJSON()) {
                return;
            }

            return response.responseText.evalJSON();
        },

        // ---------------------------------------

        initAttributesOptionsFilter: function(filterEl)
        {
            var srcElement = Element.down(filterEl, 'select');

            srcElement.observe('change', this.onAttributesOptionsFilterChange.bind(this));

            var valuesDiv = Element.down(filterEl, '.attributes-options-filter-values');
            valuesDiv.optionsCount = valuesDiv.childElementCount;

            if(valuesDiv.optionsCount == srcElement.childElementCount - 1) {
                srcElement.hide();
            }

            valuesDiv.optionsIterator = 0;
            valuesDiv.childElements().each(function(attrValue) {

                var removeImg = Element.down(attrValue, '.filter-param-remove'),
                    attrName = Element.down(attrValue, 'input[type="hidden"]'),
                    selectedOption = Element.down(filterEl, 'select option[value="' + attrName.value + '"]');

                selectedOption.hide();

                valuesDiv.optionsIterator++;

                removeImg.show();
                removeImg.observe('click', function() {
                    valuesDiv.optionsCount--;
                    selectedOption.show();
                    srcElement.show();
                    Element.remove(attrValue);
                });
            }, this);
        },

        onAttributesOptionsFilterChange: function(e)
        {
            var srcElement = e.target || e.srcElement,
                parentDiv = Element.up(srcElement, '.attributes-options-filter'),
                valuesDiv = Element.down(parentDiv, '.attributes-options-filter-values'),
                selectedOption = Element.down(srcElement, '[value="' + srcElement.value + '"]');

            selectedOption.hide();

            valuesDiv.optionsCount++;
            valuesDiv.optionsIterator++;

            srcElement.enable();
            if(valuesDiv.optionsCount == srcElement.childElementCount - 1) {
                srcElement.hide();
            }

            var filterName = parentDiv.id.replace('attributes-options-filter_', '');

            var newOptionContainer = new Element('div'),
                newOptionLabel = new Element('div'),
                newOptionValue = new Element('input', {
                    type: 'text',
                    class: 'input-text admin__control-text',
                    name: filterName + '[' + valuesDiv.optionsIterator + '][value]'
                }),
                newOptionAttr = new Element('input', {
                    type: 'hidden',
                    name: filterName + '[' + valuesDiv.optionsIterator + '][attr]',
                    value: srcElement.value
                }),
                removeImg = Element.clone(Element.down(parentDiv, '.attributes-options-filter-selector .filter-param-remove'));

            newOptionLabel.innerHTML = srcElement.value + ': ';
            removeImg.show();

            Event.observe(newOptionValue, 'keypress', this.getGridObj().filterKeyPress.bind(this.getGridObj()));

            newOptionContainer.insert({ bottom: newOptionLabel });
            newOptionContainer.insert({ bottom: newOptionValue });
            newOptionContainer.insert({ bottom: newOptionAttr });
            newOptionContainer.insert({ bottom: removeImg });

            valuesDiv.insert({ bottom: newOptionContainer });

            removeImg.observe('click', function() {
                valuesDiv.optionsCount--;
                selectedOption.show();
                srcElement.show();
                newOptionContainer.remove();
            }, this);

            srcElement.value = '';
        },

        // ---------------------------------------

        editVariationIdentifiers: function(editBtn, variationId)
        {
            $('variation_identifiers_edit_'+variationId).show();
            $('variation_identifiers_'+variationId).hide();
            editBtn.hide();
        },

        confirmVariationIdentifiers: function(editBtn, variationId)
        {
            var self = this,
                form = $('variation_identifiers_edit_'+variationId)

            if (!self.isValid(form)) {
                return;
            }

            var data = form.serialize(true);

            new Ajax.Request(M2ePro.url.get('ebay_listing_variation_product_manage/setIdentifiers'), {
                method: 'post',
                parameters: data,
                onSuccess: function(transport) {

                    var response = self.parseResponse(transport);
                    if(response.success) {
                        VariationsGridObj.getGridObj().reload();
                    }
                }
            });
        },

        cancelVariationIdentifiers: function(variationId)
        {
            var form = $('variation_identifiers_edit_'+variationId);
            form.reset();
            this.isValid(form);
            form.hide();
            $('variation_identifiers_'+variationId).show();
            $('edit_variations_'+variationId).show();
        },

        isValid: function (form)
        {
            var upc = form.down('.M2ePro-upc'),
                ean = form.down('.M2ePro-ean'),
                isbn = form.down('.M2ePro-isbn'),
                mpn = form.down('.M2ePro-mpn');

            upc.value = trim(upc.value);
            ean.value = trim(ean.value);
            isbn.value = trim(isbn.value.replace('-',''));
            mpn.value = trim(mpn.value);

            return jQuery(form).valid();
        }

    });
});