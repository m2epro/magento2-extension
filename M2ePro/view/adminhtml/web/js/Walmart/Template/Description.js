define([
    'M2ePro/Walmart/Template/Edit',
], function() {

    window.WalmartTemplateDescription = Class.create(WalmartTemplateEdit, {

        // ---------------------------------------

        initialize: function()
        {
            this.initValidation();
        },

        initValidation: function()
        {
            var self = this;

            self.setValidationCheckRepetitionValue('M2ePro-description-template-title',
                                                   M2ePro.translator.translate('The specified Title is already used for another Policy. Policy Title must be unique.'),
                                                   'Template\\Description', 'title', 'id',
                                                   M2ePro.formData.id,
                                                   M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK'));
        },

        initObservers: function()
        {
            $('title_mode')
                .observe('change', WalmartTemplateDescriptionObj.title_mode_change)
                .simulate('change');

            $('brand_mode')
                .observe('change', WalmartTemplateDescriptionObj.brand_mode_change)
                .simulate('change');

            $('manufacturer_mode')
                .observe('change', WalmartTemplateDescriptionObj.manufacturer_mode_change)
                .simulate('change');

            $('manufacturer_part_number_mode')
                .observe('change', WalmartTemplateDescriptionObj.manufacturer_part_number_mode_change)
                .simulate('change');

            $('model_number_mode')
                .observe('change', WalmartTemplateDescriptionObj.model_number_mode_change)
                .simulate('change');

            $('total_count_mode')
                .observe('change', WalmartTemplateDescriptionObj.total_count_mode_change)
                .simulate('change');

            $('keywords_mode')
                .observe('change', WalmartTemplateDescriptionObj.keywords_mode_change)
                .simulate('change');

            // ---

            $('count_per_pack_mode')
                .observe('change', WalmartTemplateDescriptionObj.onChangeCountPerPackMode)
                .simulate('change');

            $('multipack_quantity_mode')
                .observe('change', WalmartTemplateDescriptionObj.onChangeMultipackQuantityMode)
                .simulate('change');

            // ---

            $('msrp_rrp_mode')
                .observe('change', WalmartTemplateDescriptionObj.onChangeMsrpRrpMode)
                .simulate('change');

            // ---

            $('key_features_mode')
                .observe('change', function () {
                    WalmartTemplateDescriptionObj.multi_element_mode_change.call(this,'key_features',5);
                })
                .simulate('change');

            $('other_features_mode')
                .observe('change', function () {
                    WalmartTemplateDescriptionObj.multi_element_mode_change.call(this,'other_features',5);
                })
                .simulate('change');

            $('attributes_mode')
                .observe('change', function () {
                    WalmartTemplateDescriptionObj.multi_element_mode_change.call(this,'attributes',5);
                })
                .simulate('change');

            $('description_mode')
                .observe('change', WalmartTemplateDescriptionObj.description_mode_change)
                .simulate('change');

            $('image_main_mode')
                .observe('change', WalmartTemplateDescriptionObj.image_main_mode_change)
                .simulate('change');

            $('image_variation_difference_mode')
                .observe('change', WalmartTemplateDescriptionObj.image_variation_difference_mode_change)
                .simulate('change');

            $('gallery_images_mode')
                .observe('change', WalmartTemplateDescriptionObj.gallery_images_mode_change)
                .simulate('change');
        },

        //########################################

        duplicateClick: function($super, $headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-description-template-title',
                                                   M2ePro.translator.translate('The specified Title is already used for another Policy. Policy Title must be unique.'),
                                                   'Template\\Description', 'title', '','',
                                                   M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK'));

            $super($headId, M2ePro.translator.translate('Add Description Policy'));
        },

        //########################################

        title_mode_change: function()
        {
            var customTitle = $('custom_title_tr');
            this.value == 1 ? customTitle.show() : customTitle.hide();
        },

        brand_mode_change: function()
        {
            var customAttribute = $('brand_custom_attribute'),
                customValueTr   = $('brand_custom_value_tr');

            customValueTr.hide();

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::BRAND_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::BRAND_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        manufacturer_mode_change: function()
        {
            var customAttribute = $('manufacturer_custom_attribute'),
                customValueTr   = $('manufacturer_custom_value_tr');

            customValueTr.hide();

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MANUFACTURER_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MANUFACTURER_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        manufacturer_part_number_mode_change: function()
        {
            var customAttribute = $('manufacturer_part_number_custom_attribute'),
                customValueTr   = $('manufacturer_part_number_custom_value_tr');

            customValueTr.hide();

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        model_number_mode_change: function()
        {
            var customAttribute = $('model_number_custom_attribute'),
                customValueTr   = $('model_number_custom_value_tr');

            customValueTr.hide();

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MODEL_NUMBER_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MODEL_NUMBER_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        total_count_mode_change: function()
        {
            var customAttribute = $('total_count_custom_attribute'),
                customValueTr   = $('total_count_custom_value_tr');

            customValueTr.hide();

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::TOTAL_COUNT_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::TOTAL_COUNT_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        keywords_mode_change: function()
        {
            var customAttribute = $('keywords_custom_attribute'),
                customValueTr   = $('keywords_custom_value_tr');

            customValueTr.hide();

            customAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::KEYWORDS_MODE_CUSTOM_VALUE')) {
                customValueTr.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::KEYWORDS_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        onChangeCountPerPackMode: function()
        {
            var targetCustomValue     = $('count_per_pack_custom_value_tr'),
                targetCustomAttribute = $('count_per_pack_custom_attribute');

            targetCustomValue.hide();

            targetCustomAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::COUNT_PER_PACK_MODE_CUSTOM_VALUE')) {
                targetCustomValue.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::COUNT_PER_PACK_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, targetCustomAttribute);
            }
        },

        onChangeMultipackQuantityMode: function()
        {
            var targetCustomValue     = $('multipack_quantity_custom_value_tr'),
                targetCustomAttribute = $('multipack_quantity_custom_attribute');

            targetCustomValue.hide();

            targetCustomAttribute.value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MULTIPACK_QUANTITY_MODE_CUSTOM_VALUE')) {
                targetCustomValue.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MULTIPACK_QUANTITY_MODE_CUSTOM_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, targetCustomAttribute);
            }
        },

        // ---------------------------------------

        onChangeMsrpRrpMode: function()
        {
            var customAttribute = $('msrp_rrp_custom_attribute');

            customAttribute.value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::MSRP_RRP_MODE_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, customAttribute);
            }
        },

        // ---------------------------------------

        multi_element_mode_change: function(type, max)
        {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::' + type.toUpperCase() + '_MODE_NONE')) {

                $$('.' + type + '_tr').invoke('hide');
                $$('input[name="' + type + '[]"], input[name="' + type + '_name[]"], input[name="' + type + '_value[]"]').each(function(obj) {
                    obj.value = '';
                });
                $(type + '_actions_tr').hide();

            } else {

                var visibleElementsCounter = 0;

                $$('.' + type + '_tr').each(function(obj) {
                    if (visibleElementsCounter == 0 ||
                        ($(obj).select('input[name="' + type + '_value[]"]')[0] && $(obj).select('input[name="' + type + '_value[]"]')[0].value != '') ||
                        ($(obj).select('input[name="' + type + '[]"]')[0] && $(obj).select('input[name="' + type + '[]"]')[0].value != '')
                    ) {
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

                if (visibleElementsCounter == 1 && ($(type + '_value_0') && $(type + '_value_0').value == '') || ($(type + '_0') && $(type + '_0').value == '')) {
                    $('show_' + type + '_action').addClassName('action-disabled');
                }
            }
        },

        multi_element_keyup: function(type, element)
        {
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

            var hiddenElements = $$('.' + type + '_tr').findAll(function(obj) {
                return !$(obj).visible();
            });

            if (hiddenElements.size() != 0) {
                $('show_' + type + '_action').removeClassName('action-disabled');
            }
        },

        description_mode_change: function()
        {
            if (this.value !== '' && this.options[0].value === '') {
                this.removeChild(this.options[0]);
            }

            this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::DESCRIPTION_MODE_CUSTOM')
                ? $$('.c-custom_description_tr').invoke('show')
                : $$('.c-custom_description_tr').invoke('hide');
        },

        image_main_mode_change: function()
        {
            var self = WalmartTemplateDescriptionObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::IMAGE_MAIN_MODE_NONE')) {
                $('gallery_images_mode_tr').hide();
                $('gallery_images_mode').value = 0;
                $('gallery_images_mode').simulate('change');
            } else {
                $('gallery_images_mode_tr').show();
            }

            $('image_main_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::IMAGE_MAIN_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('image_main_attribute'));
            }
        },

        image_variation_difference_mode_change: function()
        {
            var self = WalmartTemplateDescriptionObj;

            $('image_variation_difference_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::IMAGE_VARIATION_DIFFERENCE_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('image_variation_difference_attribute'));
            }
        },

        gallery_images_mode_change: function()
        {
            $('gallery_images_limit').value = '';
            $('gallery_images_attribute').value = '';
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::GALLERY_IMAGES_MODE_PRODUCT')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, $('gallery_images_limit'));
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::GALLERY_IMAGES_MODE_ATTRIBUTE')) {
                WalmartTemplateDescriptionObj.updateHiddenValue(this, $('gallery_images_attribute'));
            }
        },

        // ---------------------------------------

        showElement: function(type)
        {
            var emptyVisibleElementsExist = $$('.' + type + '_tr').any(function(obj) {

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

            var hiddenElements = $$('.' + type + '_tr').findAll(function(obj) {
                return !$(obj).visible();
            });

            if (hiddenElements.size() == 0) {
                return;
            }

            hiddenElements.shift().show();

            $('hide_' + type + '_action').removeClassName('action-disabled');
            $('show_' + type + '_action').addClassName('action-disabled');
        },

        hideElement: function(type, force)
        {
            force = force || false;

            var visibleElements = [];
            $$('.' + type + '_tr').each(function(el) {
                if(el.visible()) visibleElements.push(el);
            });

            if (visibleElements.length <= 0 || (!force && visibleElements[visibleElements.length - 1].getAttribute('undeletable'))) {
                return;
            }

            if (visibleElements.length == 1) {
                var elementMode = $(type + '_mode');
                elementMode.value = M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Template_Description::' + type.toUpperCase() + '_MODE_NONE');
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
                if(!force && nextVisibleElement.getAttribute('undeletable')) {
                    $('hide_' + type + '_action').addClassName('action-disabled');
                }
            }

            $('show_' + type + '_action').removeClassName('action-disabled');
        }

        // ---------------------------------------
    });
});