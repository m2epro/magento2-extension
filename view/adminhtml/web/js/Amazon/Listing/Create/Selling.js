define([
    'M2ePro/Common'
], function () {

    window.AmazonListingCreateSelling = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {
            jQuery.validator.addMethod('M2ePro-validate-condition-note-length', function(value) {
                if ($('condition_note_mode').value != M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::CONDITION_NOTE_MODE_CUSTOM_VALUE')) {
                    return true;
                }

                return value.length <= 2000;
            }, M2ePro.translator.translate('condition_note_length_error'));

            jQuery.validator.addMethod('M2ePro-validate-sku-modification-custom-value', function(value) {
                if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::SKU_MODIFICATION_MODE_NONE')) {
                    return true;
                }

                if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::SKU_MODIFICATION_MODE_TEMPLATE')) {
                    return value.match(/%value%/g);
                }

                return true;
            }, M2ePro.translator.translate('sku_modification_custom_value_error'));

            jQuery.validator.addMethod('M2ePro-validate-sku-modification-custom-value-max-length', function(value) {
                if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::SKU_MODIFICATION_MODE_NONE')) {
                    return true;
                }

                if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::SKU_MODIFICATION_MODE_TEMPLATE')) {
                    value = value.replace('%value%', '');
                }

                return value.length < M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing_Product_Action_Type_ListAction_Validator_Sku_General::SKU_MAX_LENGTH');
            }, M2ePro.translator.translate('sku_modification_custom_value_max_length_error'));
        },

        // ---------------------------------------

        sku_mode_change: function () {
            var self = AmazonListingCreateSellingObj;

            $('sku_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::SKU_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('sku_custom_attribute'));
            }
        },

        // ---------------------------------------

        sku_modification_mode_change: function () {
            if ($('sku_modification_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::SKU_MODIFICATION_MODE_NONE')) {
                $('sku_modification_custom_value_tr').hide();
            } else {
                $('sku_modification_custom_value_tr').show();
            }
        },

        // ---------------------------------------

        condition_mode_change: function () {
            var self = AmazonListingCreateSellingObj,
                attributeCode = this.options[this.selectedIndex].getAttribute('attribute_code'),
                conditionValue = $('condition_value'),
                conditionCustomAttribute = $('condition_custom_attribute'),
                conditionNoteModeTr = $('condition_note_mode_tr'),
                conditionNoteValueTr = $('condition_note_value_tr');

            conditionNoteModeTr.show();
            conditionNoteValueTr.show();

            conditionValue.value = '';
            conditionCustomAttribute.value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::CONDITION_MODE_DEFAULT')) {
                self.updateHiddenValue(this, conditionValue);

                if (attributeCode == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::CONDITION_NEW')) {
                    conditionNoteModeTr.hide();
                    conditionNoteValueTr.hide();
                    $('condition_custom_tr').hide();
                } else {
                    self.condition_note_mode_change();
                }
            } else {
                self.updateHiddenValue(this, conditionCustomAttribute);
                conditionNoteModeTr.show();
                self.condition_note_mode_change();
            }
        },

        // ---------------------------------------

        gift_wrap_mode_change: function () {
            var self = AmazonListingCreateSellingObj;

            $('gift_wrap_attribute').value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::GIFT_WRAP_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('gift_wrap_attribute'));
            }
        },

        gift_message_mode_change: function () {
            var self = AmazonListingCreateSellingObj;

            $('gift_message_attribute').value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::GIFT_MESSAGE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('gift_message_attribute'));
            }
        },

        // ---------------------------------------

        condition_note_mode_change: function () {
            var self = AmazonListingCreateSellingObj;

            if ($('condition_note_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::CONDITION_NOTE_MODE_CUSTOM_VALUE')) {
                $('condition_note_value_tr').show();
                $('condition_custom_tr').show();
            } else {
                $('condition_note_value_tr').hide();
                $('condition_custom_tr').hide();
            }
        },

        handling_time_mode_change: function () {
            var self = AmazonListingCreateSellingObj;

            $('handling_time_custom_attribute').value = '';
            $('handling_time_value').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::HANDLING_TIME_MODE_RECOMMENDED')) {
                self.updateHiddenValue(this, $('handling_time_value'));
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('handling_time_custom_attribute'));
            }
        },

        restock_date_mode_change: function () {
            var self = AmazonListingCreateSellingObj;

            $('restock_date_value_tr').hide();

            $('restock_date_custom_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::RESTOCK_DATE_MODE_CUSTOM_VALUE')) {
                $('restock_date_value_tr').show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('restock_date_custom_attribute'));
            }
        },

        // ---------------------------------------

        general_id_mode_change: function () {
            const self = AmazonListingCreateSellingObj;
            const generalIdAttribute = $('general_id_attribute');

            generalIdAttribute.value = '';
            if (this.value !== 'none') {
                self.updateHiddenValue(this, generalIdAttribute);
            }
        },

        worldwide_id_mode_change: function () {
            const self = AmazonListingCreateSellingObj;
            const worldwideIdAttribute = $('worldwide_id_attribute');

            worldwideIdAttribute.value = '';
            if (this.value !== 'none') {
                self.updateHiddenValue(this, worldwideIdAttribute);
            }
        },

        // ---------------------------------------

        appendToText: function (ddId, targetId) {
            if ($(ddId).value == '') {
                return;
            }

            var attributePlaceholder = '#' + $(ddId).value + '#',
                element = $(targetId);

            if (document.selection) {
                /* IE */
                element.focus();
                document.selection.createRange().text = attributePlaceholder;
                element.focus();
            } else if (element.selectionStart || element.selectionStart == '0') {
                /* Webkit */
                var startPos = element.selectionStart,
                    endPos = element.selectionEnd,
                    scrollTop = element.scrollTop,
                    tempValue;

                tempValue = element.value.substring(0, startPos);
                tempValue += attributePlaceholder;
                tempValue += element.value.substring(endPos, element.value.length);
                element.value = tempValue;

                element.focus();
                element.selectionStart = startPos + attributePlaceholder.length;
                element.selectionEnd = startPos + attributePlaceholder.length;
                element.scrollTop = scrollTop;
            } else {
                element.value += attributePlaceholder;
                element.focus();
            }
        }

        // ---------------------------------------
    });

});
