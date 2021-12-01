define([
    'jquery',
    'M2ePro/Common'
], function (jQuery) {
    window.EbayTemplateCategorySpecifics = Class.create(Common, {

        maxSelectedSpecifics: 45,

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-custom-specific-attribute-title', function(value, el) {

                    var customTitleInput = el;

                    var result = true;
                    $$('.M2ePro-dictionary-specific-attribute-title').each(function(el) {
                        if (el.value == value) {
                            result = false;
                            throw $break;
                        }
                    });

                    $$('.M2ePro-custom-specific-attribute-title').each(function(el) {
                        if (el == customTitleInput) {
                            return;
                        }

                        if (!el.visible()) {
                            return;
                        }

                        if (trim(el.value) == value) {
                            result = false;
                            throw $break;
                        }
                    });

                    return result;
                }, M2ePro.translator.translate('Item Specifics cannot have the same Labels.')
            );
        },

        // ---------------------------------------

        resetSpecifics: function()
        {
            $$('[id*=specific_dictionary_value_mode_').each(function(el){
                el.childElements()[0].selected = true;
                el.simulate('change');
            });

            $$('.remove_custom_specific_button').each(function(el){
                el.simulate('click');
            });
        },

        collectSpecifics: function()
        {
            var specifics = {};

            var self = this;
            $('edit_specifics_form').select('input[name^="specific"]', 'select[name^="specific"]').each(
                function(el) {
                    if (el.disabled) {
                        return true;
                    }
                    var temp = el.name.match(/specific\[([a-z0-9_]*)\]\[([a-z_]*)\]/);
                    if (typeof specifics[temp[1]] === 'undefined') {
                        specifics[temp[1]] = {};
                    }

                    if (typeof specifics[temp[1]][temp[2]] === 'undefined') {
                        specifics[temp[1]][temp[2]] = {};
                    }

                    if (el.multiple) {
                        specifics[temp[1]][temp[2]] = self.getSelectValues(el);
                    } else {
                        let specific = specifics[temp[1]]['value_custom_value'];
                        if (typeof specific !== 'undefined' && Object.keys(specific).length !== 0) {
                            let multi_input = [];
                            if (Object.isArray(specific)) {
                                specifics[temp[1]][temp[2]].forEach(function(item) {
                                    multi_input.push(item);
                                });
                            } else {
                                multi_input.push(specifics[temp[1]][temp[2]]);
                            }
                            multi_input.push(el.value);
                            specifics[temp[1]][temp[2]] = multi_input;
                        } else {
                            specifics[temp[1]][temp[2]] = el.value;
                        }
                    }
                }
            );

            return specifics;
        },

        getSelectValues: function(select)
        {
            var result = [];
            var options = select && select.options;
            var opt;

            for (var i=0, iLen=options.length; i<iLen; i++) {
                opt = options[i];

                if (opt.selected) {
                    result.push(opt.value || opt.text);
                }
            }

            return result;
        },

        // ---------------------------------------

        // dictionary specifics
        // ---------------------------------------

        dictionarySpecificModeChange: function (index, select)
        {
            var self = this;
            var recommended = $('specific_dictionary_value_ebay_recommended_' + index),
                customValueTable = $('specific_dictionary_custom_value_table_' + index),
                customValueInputs = $$('[id*=specific_dictionary_value_custom_value_' + index +'_]'),
                attribute = $('specific_dictionary_value_custom_attribute_' + index);

            recommended.hide().disable();
            customValueTable.hide();
            customValueInputs.invoke('disable');
            attribute.hide().disable();

            if (select.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_EBAY_RECOMMENDED')) {
                recommended.show().enable();
            }
            if (select.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_CUSTOM_VALUE')) {
                customValueTable.show();
                customValueInputs.invoke('enable');
            }
            if (select.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_CUSTOM_ATTRIBUTE')) {
                attribute.show().enable();
            }

            this.checkSpecificsCounter(select);
        },

        addItemSpecificsCustomValueRow: function(index, button)
        {
            var timestampId = new Date().getTime();
            var tbody = $('specific_dictionary_custom_value_table_body_' + index);

            var newRow = Element.clone(tbody.childElements()[0], true);
            var newRowInput = newRow.select('[id*=specific_dictionary_value_custom_value_' + index + '_]')[0];
            newRowInput.clear();

            //replacing id to unique value
            var idParts = newRowInput.id.split(/(\d+)/);
            newRowInput.setAttribute('id', newRowInput.id.replace('_' + idParts[3], '_' + timestampId));

            tbody.appendChild(newRow);

            var valuesCounter = tbody.childElements().length;

            if (parseInt(tbody.getAttribute('data-max_values')) > valuesCounter) {
                button.show();
            } else {
                button.hide();
            }

            if (parseInt(tbody.getAttribute('data-min_values')) >= valuesCounter) {
                $$('#specific_dictionary_custom_value_table_body_' + index + ' tr td.btn_value_remove').invoke('hide');
            } else {
                $$('#specific_dictionary_custom_value_table_body_' + index + ' tr td.btn_value_remove').invoke('show');
            }
        },

        removeItemSpecificsCustomValue: function(button)
        {
            var tbody  = $(button).up('tbody'),
                addBtn = $(button).up('table').next('a');

            $(button).up('tr').remove();

            var valuesCounter = tbody.childElements().length;

            if (parseInt(tbody.getAttribute('data-max_values')) > valuesCounter) {
                addBtn.show();
            } else {
                addBtn.hide();
            }

            if (valuesCounter == 1 || parseInt(tbody.getAttribute('data-min_values')) >= valuesCounter) {
                var btnRemove = tbody.getElementsByClassName('btn_value_remove');
                for (var i = 0; i < btnRemove.length; i++) {btnRemove[i].hide();}
            }
        },
        // ---------------------------------------

        // custom specifics
        // ---------------------------------------

        customSpecificModeChange: function(select)
        {
            var self = this;
            var index = select.id.replace('specific_custom_value_mode_', '');

            var attribute = $('specific_custom_value_custom_attribute_' + index),
                customValue = $('specific_custom_value_custom_value_' + index),
                attributeTitleLabel = $('specific_custom_attribute_title_label_' + index),
                attributeTitleInput = $('specific_custom_attribute_title_input_' + index);

            attribute.hide().disable();
            customValue.hide().disable();

            attributeTitleLabel.hide();
            attributeTitleInput.hide().disable();

            if (select.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_CUSTOM_VALUE')) {
                customValue.show().enable();
                attributeTitleInput.show().enable();
            }
            if (select.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_CUSTOM_ATTRIBUTE')) {
                attribute.show().enable();
                attributeTitleLabel.show();
                attributeTitleInput.enable().clear();
            }
            if (select.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE')) {
                attribute.show().enable();
                attributeTitleInput.show().enable();
            }

            this.checkSpecificsCounter();
        },

        addCustomSpecificRow: function()
        {
            var timestampId = new Date().getTime();
            var tbody = $('specific_custom_table_body');

            var newRow = Element.clone(tbody.childElements()[0], true);

            newRow.down('button.remove_custom_specific_button').addEventListener('click', this.removeCustomSpecific.bind(this));

            newRow.show();
            newRow.select('[id*=specific_custom_]').each(function(el){

                if (typeof el.enable === 'function') {
                    el.enable();
                }

                //replacing id to unique value
                var idParts = el.id.split(/(\d+)/);
                el.setAttribute('id', el.id.replace('_' + idParts[1], '_' + timestampId));
                if (typeof el.name === 'string') {
                    el.setAttribute('name', el.name.replace('_' + idParts[1], '_' + timestampId));
                }
            });
            tbody.appendChild(newRow);

            var selectId = 'specific_custom_value_custom_attribute_' + timestampId;
            var handlerObj = new AttributeCreator(selectId);
            handlerObj.setSelectObj($(selectId));
            handlerObj.injectAddOption();
        },

        removeCustomSpecific: function(button)
        {
            var tbody = $('specific_custom_table_body');
            var targetNode;

            if (button.nodeType === Node.ELEMENT_NODE) {
                targetNode = button;
            } else {
                targetNode = button.target;
            }

            var removingRow = $(targetNode).up('tr');

            if (tbody.childElements().length > 1) {
                removingRow.remove();
                this.checkSpecificsCounter();
                return;
            }

            removingRow.select('[id*=specific_custom_]').each(function(el){
                if (typeof el.disable === 'function') {
                    el.disable();
                }
            });
            removingRow.hide();

            this.checkSpecificsCounter();
        },

        // ---------------------------------------

        checkSpecificsCounter: function ()
        {
            var valueModeNone = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Category_Specific::VALUE_MODE_NONE');

            if ($$('.specific-value-mode[value!=' + valueModeNone + ']').length >= this.maxSelectedSpecifics) {
                $('add_custom_specific_button').hide();
            } else {
                $('add_custom_specific_button').show();
            }
        }

        // ---------------------------------------
    });
});
