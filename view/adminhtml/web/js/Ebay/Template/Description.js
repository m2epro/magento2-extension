define([
    'M2ePro/Ebay/Template/Description/ComplianceDocuments',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
    'mage/adminhtml/wysiwyg/tiny_mce/setup'
], function(ComplianceDocuments) {
    window.EbayTemplateDescription = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {
            jQuery.validator.addMethod('M2ePro-validate-description-mode', function(value, el) {

                if (value === '-1') {
                    return false;
                }

                return Validation.get('required-entry').test(value, el);
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-description-template', function(value, el) {

                if ($('description_mode').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_CUSTOM')) {
                    return true;
                }

                return Validation.get('required-entry').test(value, el);
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-condition-note-length', function(value) {

                if ($('condition_note_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_NOTE_MODE_NONE')) {
                    return true;
                }

                return value.length <= 1000;
            }, M2ePro.translator.translate('Seller Notes must be less then 1000 symbols.'));

            jQuery.validator.addMethod('M2ePro-validate-condition-descriptor-for-graded', function(value, el) {
                return $('condition_value').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_GRADED')
                        || value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE')
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-condition-descriptor-for-ungraded', function(value, el) {
                return $('condition_value').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_UNGRADED')
                        || value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE')
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-condition-descriptor-certification-number-length', function(value) {

                if (
                    $('condition_value').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_GRADED')
                    || value.length === 0
                ) {
                    return true;
                }

                return value.length <= 30;
            }, M2ePro.translator.translate('Certification Number must be less then 30 symbols.'));

            jQuery.validator.addMethod('M2ePro-validate-magento-product-id', function(value) {

                var isValidMagentoProductId = false;

                new Ajax.Request(M2ePro.url.get('ebay_template_description/checkMagentoProductId'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        product_id: value
                    },
                    onSuccess: function(transport) {
                        var response = transport.responseText.evalJSON();
                        isValidMagentoProductId = response.result;
                    }
                });

                return isValidMagentoProductId;
            }, M2ePro.translator.translate('Please enter a valid Magento product ID.'));
        },

        initObservers: function() {
            $('title_mode')
                .observe('change', EbayTemplateDescriptionObj.title_mode_change)
                .simulate('change');

            $('subtitle_mode')
                .observe('change', EbayTemplateDescriptionObj.subtitle_mode_change)
                .simulate('change');

            $('condition_descriptor_professional_grader')
                    .observe('change', EbayTemplateDescriptionObj.conditionDescriptorProfessionalGraderChange);

            $('condition_descriptor_grade')
                    .observe('change', EbayTemplateDescriptionObj.conditionDescriptorGradeChange);

            $('condition_descriptor_grade_card_condition')
                    .observe('change', EbayTemplateDescriptionObj.conditionDescriptorGradeCardConditionChange);

            $('condition_descriptor_certification_number_mode')
                    .observe('change', EbayTemplateDescriptionObj.conditionDescriptorCertificationNumberChange);

            $('item_condition')
                .observe('change', EbayTemplateDescriptionObj.item_condition_change)
                .simulate('change');

            $('condition_note_mode')
                .observe('change', EbayTemplateDescriptionObj.condition_note_mode_change)
                .simulate('change');

            $('watermark_mode')
                .observe('change', EbayTemplateDescriptionObj.watermark_mode_change)
                .simulate('change');

            $('watermark_transparent')
                    .observe('change', EbayTemplateDescriptionObj.watermark_transparent_mode_change)
                    .simulate('change');

            $('description_mode')
                .observe('change', EbayTemplateDescriptionObj.description_mode_change)
                .simulate('change');

            $('image_main')
                .observe('change', EbayTemplateDescriptionObj.image_main_change)
                .simulate('change');

            $('gallery_images')
                .observe('change', EbayTemplateDescriptionObj.gallery_images_change)
                .simulate('change');

            $('variation_images')
                .observe('change', EbayTemplateDescriptionObj.variation_images_change)
                .simulate('change');

            $('video')
                    .observe('change', EbayTemplateDescriptionObj.video_change)
                    .simulate('change');

            $('product_details_brand')
                .observe('change', EbayTemplateDescriptionObj.product_details_brand_change)
                .simulate('change');

            $('product_details_mpn')
                .observe('change', EbayTemplateDescriptionObj.product_details_mpn_change);

            $('custom_inserts_open_popup')
                .observe('click', EbayTemplateDescriptionObj.customInsertsOpenPopup);

            if (typeof wysiwygdescription_template !== 'undefined') {
                $('description_template_show_hide_wysiwyg')
                    .observe('click', wysiwygdescription_template.toggle.bind(wysiwygdescription_template)).simulate('click')
                    .observe('click', EbayTemplateDescriptionObj.showHideWYSIWYG);
            } else {
                $('description_template_tr').down('.admin__field-control').down('.admin__field').appendChild($('description_template_buttons'));
            }

            this.initCustomInsertsPopup();
            this.initPreviewPopup();

            this.initDocumentsFieldObservers();
        },

        // ---------------------------------------

        duplicateClick: function(headId, chapter_when_duplicate_text, templateNick) {
            var watermarkImageContainer = $('watermark_uploaded_image_container');

            if (watermarkImageContainer) {
                watermarkImageContainer.remove();
                $('watermark_image').addClassName('M2ePro-required-when-visible');
                $('watermark_image_container').addClassName('_required');
            }

            EbayTemplateEditObj.duplicateClick(headId, chapter_when_duplicate_text, templateNick);
        },

        // ---------------------------------------

        title_mode_change: function() {
            var self = EbayTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'custom_title_tr');
        },

        subtitle_mode_change: function() {
            var self = EbayTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'custom_subtitle_tr');
        },

        description_mode_change: function() {
            if (this.value !== '-1' && this.options[0].value === '-1') {
                this.removeChild(this.options[0]);
            }

            var viewEditCustomDescription = $('view_edit_custom_description');

            if (viewEditCustomDescription) {
                viewEditCustomDescription.hide();
            }

            $$('.c-custom_description_tr').invoke('hide');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_CUSTOM')) {
                if (viewEditCustomDescription) {
                    viewEditCustomDescription.show();
                    $$('.c-custom_description_tr').invoke('hide');
                    return;
                }

                if (typeof wysiwygdescription_template !== 'undefined' && $('description_editor_type').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_SIMPLE')) {
                    wysiwygdescription_template.toggle();
                }

                $$('.c-custom_description_tr').invoke('show');
            } else {
                if (viewEditCustomDescription) {
                    viewEditCustomDescription.remove();
                }
            }
        },

        view_edit_custom_change: function() {
            if (typeof wysiwygdescription_template !== 'undefined' && $('description_editor_type').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_SIMPLE')) {
                wysiwygdescription_template.toggle();
            }

            $$('.c-custom_description_tr').invoke('show');
            $('view_edit_custom_description').hide();
        },

        // region Item Condition

        item_condition_change: function() {
            $('condition_note_tr').show();
            $('condition_note_mode').simulate('change');

            var self = EbayTemplateDescriptionObj,
                isConditionNoteNeeded = true,

                conditionValue = $('condition_value'),
                conditionAttribute = $('condition_attribute');

            self.hideAllConditionDescriptors();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_MODE_NONE')) {
                isConditionNoteNeeded = false;
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_MODE_EBAY')) {
                self.updateHiddenValue(this, conditionValue);
                conditionAttribute.value = '';
                isConditionNoteNeeded = conditionValue.value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_NEW');

                if (conditionValue.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_GRADED')) {
                    self.showGradedConditionDescriptors();
                    isConditionNoteNeeded = false;
                } else if (conditionValue.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_UNGRADED')) {
                    self.showUngradedConditionDescriptors();
                    isConditionNoteNeeded = false;
                }
            } else {
                self.updateHiddenValue(this, conditionAttribute);
                conditionValue.value = '';
            }

            if (!isConditionNoteNeeded) {

                $('condition_note_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_NOTE_MODE_NONE');
                $('condition_note_mode').simulate('change');

                $('condition_note_tr').hide();
                $('custom_condition_note_tr').hide();
            }
        },

        hideAllConditionDescriptors: function () {
            this.hideConditionDescriptorProfessionalGrader();
            this.hideConditionDescriptorGrade();
            this.hideConditionDescriptorCertificationNumber()

            this.hideConditionDescriptorGradeCardCondition();
        },

        showGradedConditionDescriptors: function () {
            this.showConditionDescriptorProfessionalGrader();
            this.showConditionDescriptorGrade();
            this.showConditionDescriptorCertificationNumber();

            this.hideConditionDescriptorGradeCardCondition();
        },

        showUngradedConditionDescriptors: function () {
            this.hideConditionDescriptorProfessionalGrader();
            this.hideConditionDescriptorGrade();
            this.hideConditionDescriptorCertificationNumber();

            this.showConditionDescriptorGradeCardCondition();
        },

        // region Condition Descriptor - Professional Grader

        conditionDescriptorProfessionalGraderChange: function() {
            const self = EbayTemplateDescriptionObj;
            const gradeValue = $('condition_descriptor_professional_grader_value');
            const graderAttribute = $('condition_descriptor_professional_grader_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, graderAttribute);
                gradeValue.update('');
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_EBAY')) {
                self.updateHiddenValue(this, gradeValue);
                graderAttribute.update('');
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE')) {
                graderAttribute.update('');
                gradeValue.update('');
            }
        },

        hideConditionDescriptorProfessionalGrader: function () {
            $('condition_descriptor_professional_grader_value').value = M2ePro.php.constant(
                    'Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE'
            );
            $('condition_descriptor_professional_grader_tr').hide();
            $('condition_descriptor_professional_grader').simulate('change');
        },

        showConditionDescriptorProfessionalGrader: function () {
            $('condition_descriptor_professional_grader_tr').show();
            $('condition_descriptor_professional_grader').simulate('change');
        },

        // endregion
        // region Condition Descriptor - Grade

        conditionDescriptorGradeChange: function() {
            const self = EbayTemplateDescriptionObj;
            const gradeValue = $('condition_descriptor_grade_value');
            const gradeAttribute = $('condition_descriptor_grade_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, gradeAttribute);
                gradeValue.update('');
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_EBAY')) {
                self.updateHiddenValue(this, gradeValue);
                gradeAttribute.update('');
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE')) {
                gradeAttribute.update('');
                gradeValue.update('');
            }
        },

        hideConditionDescriptorGrade: function () {
            $('condition_descriptor_grade_value').value = M2ePro.php.constant(
                    'Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE'
            );
            $('condition_descriptor_grade_tr').hide();
            $('condition_descriptor_grade').simulate('change');
        },

        showConditionDescriptorGrade: function () {
            $('condition_descriptor_grade_tr').show();
            $('condition_descriptor_grade').simulate('change');
        },

        // endregion
        // region Condition Descriptor - Grade Card Condition

        conditionDescriptorGradeCardConditionChange: function() {
            const self = EbayTemplateDescriptionObj;
            const gradeValue = $('condition_descriptor_grade_card_condition_value');
            const gradeAttribute = $('condition_descriptor_grade_card_condition_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, gradeAttribute);
                gradeValue.update('');
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_EBAY')) {
                self.updateHiddenValue(this, gradeValue);
                gradeAttribute.update('');
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE')) {
                gradeAttribute.update('');
                gradeValue.update('');
            }
        },

        hideConditionDescriptorGradeCardCondition: function () {
            $('condition_descriptor_grade_card_condition_value').value = M2ePro.php.constant(
                    'Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE'
            );
            $('condition_descriptor_grade_card_condition_tr').hide();
            $('condition_descriptor_grade_card_condition').simulate('change');
        },

        showConditionDescriptorGradeCardCondition: function () {
            $('condition_descriptor_grade_card_condition_tr').show();
            $('condition_descriptor_grade_card_condition').simulate('change');
        },

        // endregion
        // region Condition Descriptor - Certification Number

        conditionDescriptorCertificationNumberChange: function() {
            const self = EbayTemplateDescriptionObj;
            const gradeValueContainer = $('condition_descriptor_certification_number_value_tr');
            const gradeAttribute =  $('condition_descriptor_certification_number_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_NONE')) {
                gradeValueContainer.hide();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_CUSTOM')) {
                gradeValueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, gradeAttribute);
            }
        },

        hideConditionDescriptorCertificationNumber: function () {
            $('condition_descriptor_certification_number_mode_tr').hide();
            $('condition_descriptor_certification_number_value_tr').hide();
        },

        showConditionDescriptorCertificationNumber: function () {
            $('condition_descriptor_certification_number_mode_tr').show();
            $('condition_descriptor_certification_number_mode')
                    .show()
                    .simulate('change');
        },

        // endregion
        // endregion

        condition_note_mode_change: function() {
            var self = EbayTemplateDescriptionObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_NOTE_MODE_NONE')) {
                $('condition_note_template').update('');
            }

            self.setTextVisibilityMode(this, 'custom_condition_note_tr');
            self.setTextVisibilityMode(this, 'custom_condition_note_attributes_tr');
        },

        watermark_mode_change: function() {
            var self = EbayTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'watermark_image_container');
            if ($('watermark_uploaded_image_container')) {
                self.setTextVisibilityMode(this, 'watermark_uploaded_image_container');
            }

            self.setTextVisibilityMode(this, 'watermark_position_container');
            self.setTextVisibilityMode(this, 'watermark_transparent_container');

            if ($('watermark_transparent').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::WATERMARK_TRANSPARENT_MODE_NO')) {
                self.setTextVisibilityMode(this, 'watermark_opacity_level_container');
            }
        },

        watermark_transparent_mode_change: function() {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::WATERMARK_TRANSPARENT_MODE_NO')) {
                $('watermark_opacity_level_container').hide();
            }
            else {
                $('watermark_opacity_level_container').show();
            }
        },

        setTextVisibilityMode: function(obj, elementName) {
            var elementObj = $(elementName);

            if (!elementObj) {
                return;
            }

            elementObj.hide();

            if (obj.value == 1) {
                elementObj.show();
            }
        },

        // ---------------------------------------

        image_main_change: function() {
            var self = EbayTemplateDescriptionObj;

            $(
                'gallery_images_mode_tr',
                'variation_images_mode_tr',
                'use_supersize_images_tr',
                'default_image_url_tr',
                'watermark_block'
            ).invoke('show');

            if ($$('#variation_configurable_images option').length > 1) {
                $('variation_configurable_images_container').show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::IMAGE_MAIN_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('image_main_attribute'));
            } else {
                $('image_main_attribute').value = '';
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::IMAGE_MAIN_MODE_NONE')) {
                $(
                    'gallery_images_mode_tr',
                    'variation_images_mode_tr',
                    'variation_configurable_images_container',
                    'use_supersize_images_tr',
                    'default_image_url_tr'
                ).invoke('hide');

                $('use_supersize_images').value = 0;

                $('gallery_images').value = 0;
                $('gallery_images').simulate('change');

                $('variation_configurable_images').value = '';

                $('variation_images').value = 1;
                $('variation_images_limit').value = 1;
                $('variation_images').simulate('change');

                $('default_image_url').value = '';

                $('watermark_block').hide();

                var watermarkMode = $('watermark_mode');

                if (watermarkMode.selectedIndex != 0) {
                    watermarkMode.selectedIndex = 0;
                    watermarkMode.simulate('change');
                }
            }
        },

        gallery_images_change: function() {
            var self = EbayTemplateDescriptionObj;

            $('gallery_images_limit').value = '';
            $('gallery_images_attribute').value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::GALLERY_IMAGES_MODE_PRODUCT')) {
                self.updateHiddenValue(this, $('gallery_images_limit'));
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::GALLERY_IMAGES_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('gallery_images_attribute'));
            }
        },

        variation_images_change: function() {
            var self = EbayTemplateDescriptionObj;

            $('variation_images_limit').value = '';
            $('variation_images_attribute').value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::VARIATION_IMAGES_MODE_PRODUCT')) {
                self.updateHiddenValue(this, $('variation_images_limit'));
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::VARIATION_IMAGES_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('variation_images_attribute'));
            }
        },

        // ---------------------------------------

        image_width_mode_change: function() {
            $('image_width_span')[this.value == 1 ? 'show' : 'hide']();
        },

        image_height_mode_change: function() {
            $('image_height_span')[this.value == 1 ? 'show' : 'hide']();
        },

        image_margin_mode_change: function() {
            $('image_margin_span')[this.value == 1 ? 'show' : 'hide']();
        },

        select_attributes_image_change: function() {
            $$('.all-products-images').invoke(this.value == 'media_gallery' ? 'show' : 'hide');
            $$('.all-products-image').invoke(this.value == 'image' ? 'show' : 'hide');
            if (this.value == 'image') {
                $('display_products_images').value = 'custom_settings';
            }
            $('display_products_images').simulate('change');
        },

        display_products_images_change: function() {
            $$('.products-images-custom-settings').invoke('hide');
            $$('.products-images-gallery-view').invoke('hide');

            if (this.value == 'gallery_view') {
                $$('.products-images-gallery-view').invoke('show');
            } else {
                $$('.products-images-custom-settings').invoke('show');
            }

            jQuery('.products-images-mode-change-label').each(function(index, elem) {
                jQuery(elem).find('.label span').text(jQuery(elem).find('.' + this.value).text());
            }.bind(this));
        },

        video_change: function() {
            var self = EbayTemplateDescriptionObj;
            $('video_custom_value_tr').hide()

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::VIDEO_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('video_attribute'));
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::VIDEO_MODE_CUSTOM_VALUE')) {
                $('video_custom_value_tr').show()
                $('video_attribute').value = '';
            } else {
                $('video_attribute').value = '';
            }
        },

        product_details_brand_change: function() {
            var self = EbayTemplateDescriptionObj,
                modeNone = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_NONE'),
                modeDoesNotApply = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY');

            if (this.value == modeNone || this.value == modeDoesNotApply) {
                $('product_details_mpn_tr').hide();
                $('product_details_mpn').selectedIndex = 0;
                $('product_details_specification_separator').hide();
                $$('.product-details-specification').each(function(element) {
                    element.hide();
                    element.down('.select').down().selectedIndex = 1;
                });
            } else {
                $('product_details_mpn_tr').show();
                $$('.product-details-specification').invoke('show');
                self.updateHiddenValue(this, $(this.id + '_attribute'));
                $('product_details_specification_separator').show();
                var hiddenElement = $(this.id + '_attribute');
                if (!hiddenElement) {
                    return;
                }
                $$('.product-details-specification').invoke('show');
                self.updateHiddenValue(this, hiddenElement);
            }
        },

        product_details_mpn_change: function() {
            var self = EbayTemplateDescriptionObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $(this.id + '_attribute'));
            }
        },

        showHideWYSIWYG: function() {
            var label;
            if ($('description_editor_type').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_TINYMCE')) {
                label = M2ePro.translator.translate('Show Editor');
                $('description_editor_type').value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_SIMPLE');
            } else {
                label = M2ePro.translator.translate('Hide Editor');
                $('description_editor_type').value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_TINYMCE');
            }
            this.select('span').first().update(label);
        },

        initCustomInsertsPopup: function() {
            var popup = jQuery('#custom_inserts_popup');
            if (!popup.find('form').length) {
                popup.wrapInner('<form id="description_custom_inserts_form"></form>');
                CommonObj.initFormValidation('#description_custom_inserts_form');
            }

            popup.modal({
                title: M2ePro.translator.translate('Custom Insertions'),
                type: 'slide',
                buttons: [],
                closed: function() {
                    EbayTemplateDescriptionObj.customInsertsOnClosePopup();
                }
            });
        },

        customInsertsOpenPopup: function() {
            jQuery('#custom_inserts_popup').modal('openModal');

            EbayTemplateDescriptionObj.observeImageAttributes();
        },

        customInsertsOnClosePopup: function() {
            jQuery('#description_custom_inserts_form').trigger('reset').validate().resetForm();
            EbayTemplateDescriptionObj.stopObservingImageAttributes();
        },

        customInsertsClosePopup: function(callback) {
            jQuery('#custom_inserts_popup').modal({
                closed: function() {
                    callback && callback();

                    // prevent callback closure
                    callback = undefined;

                    EbayTemplateDescriptionObj.customInsertsOnClosePopup();
                }
            }).modal('closeModal');
        },

        insertProductAttribute: function() {
            var self = this;

            self.customInsertsClosePopup(function() {
                self.appendToTextarea('#' + $('custom_inserts_product_attribute').value + '#');
            });
        },

        insertM2eProAttribute: function() {
            var self = this;

            self.customInsertsClosePopup(function() {
                self.appendToTextarea('#value[' + $('custom_inserts_m2epro_attribute').value + ']#');
            });
        },

        insertGallery: function() {
            var self = this;

            if (!jQuery('#description_custom_inserts_form').valid()) {
                return;
            }

            var template = '#' + $('select_attributes_image').value;

            if ($('image_width_mode').value == '1') {
                template += '[' + $('image_width').value + ',';
            } else {
                template += '[,';
            }

            if ($('image_height_mode').value == '1') {
                template += '' + $('image_height').value + ',';
            } else {
                template += ',';
            }

            if ($('image_margin_mode').value == '1') {
                template += '' + $('image_margin').value + ',';
            } else {
                template += ',';
            }

            if ($('select_attributes_image').value == 'media_gallery' && $('display_products_images').value == 'gallery_view') {
                template += '2';
            } else {
                template += "0";
            }

            if ($('select_attributes_image').value == 'media_gallery') {
                template += ',' + $('select_attributes_image_layout').value + ',' + $('select_attributes_image_count').value;
            }

            if ($('select_attributes_image').value == 'media_gallery' && $('display_products_images').value == 'gallery_view') {
                template += ',"' + $('gallery_hint_text').value + '"';
            } else if ($('select_attributes_image').value == 'media_gallery') {
                // media_gallery with empty gallery hint
                template += ',""';
            }

            if ($('image_insertion_watermark_mode').value == '1') {
                template += ',1';
            } else {
                template += ',';
            }

            if ($('select_attributes_image').value == 'image') {
                template += ',' + $('select_attributes_image_order_position').value;
            }

            template += ']#';

            self.customInsertsClosePopup(function() {
                self.appendToTextarea(template);
            });
        },

        // ---------------------------------------

        observeImageAttributes: function() {
            $('image_width_mode').observe('change', EbayTemplateDescriptionObj.image_width_mode_change).simulate('change');
            $('image_height_mode').observe('change', EbayTemplateDescriptionObj.image_height_mode_change).simulate('change');
            $('image_margin_mode').observe('change', EbayTemplateDescriptionObj.image_margin_mode_change).simulate('change');

            $('select_attributes_image')
                .observe('change', EbayTemplateDescriptionObj.select_attributes_image_change)
                .simulate('change');

            $('display_products_images')
                .observe('change', EbayTemplateDescriptionObj.display_products_images_change)
                .simulate('change');

            if ($('watermark_mode').value == 1) {
                this.setTextVisibilityMode($('watermark_mode'), 'products_images_watermark_mode');
                $('image_insertion_watermark_mode').selectedIndex = 1;
            } else {
                this.setTextVisibilityMode($('watermark_mode'), 'products_images_watermark_mode');
                $('image_insertion_watermark_mode').selectedIndex = 0;
            }
        },

        stopObservingImageAttributes: function() {
            $('image_width_mode').stopObserving();
            $('image_height_mode').stopObserving();
            $('image_margin_mode').stopObserving();

            $('select_attributes_image').stopObserving();
        },

        // ---------------------------------------

        saveWatermarkImage: function(callback, params) {
            var form = $('edit_form');

            form.action = M2ePro.url.get('ebay_template_description/saveWatermarkImage', {
                 id: params['id'],
            });

            form.target = 'watermark_image_frame';

            if ($('watermark_image_frame') === null) {
                document.body.appendChild(new Element('iframe', {
                    id: 'watermark_image_frame',
                    name: 'watermark_image_frame',
                    style: 'display: none'
                }));
            }

            $('watermark_image_frame').observe('load', function() {
                if (typeof callback == 'function') {
                    callback(params);
                }
            });

            form.submit();
        },

        // ---------------------------------------

        initPreviewPopup: function() {
            var popup = jQuery('#description_preview_popup');
            if (!popup.find('form').length) {
                popup.wrapInner(new Element('form', {
                    id: 'description_preview_form',
                    method: 'post',
                    target: '_blank',
                    action: M2ePro.url.get('ebay_template_description/preview')
                }));
                this.initFormValidation('#description_preview_form');
            }

            popup.modal({
                title: M2ePro.translator.translate('Description Preview'),
                type: 'popup',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function(event) {
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    click: function(event) {
                        if (!jQuery('#description_preview_form').valid()) {
                            return;
                        }

                        $('description_preview_description_mode').value = $('description_mode').value;
                        $('description_preview_description_template').value = $('description_template').value;
                        $('description_preview_watermark_mode').value = $('watermark_mode').value;

                        $('description_preview_form').submit();

                        this.closeModal(event);
                    }
                }],
                closed: function() {
                    jQuery('#description_preview_form').trigger('reset').validate().resetForm();
                }
            });
        },

        openPreviewPopup: function() {
            if ($('description_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_CUSTOM') && !$('description_template').value.length) {
                this.alert(M2ePro.translator.translate('Please enter Description Value.'));
                return;
            }

            jQuery('#description_preview_popup').modal('openModal');
        },

        selectProductIdRandomly: function() {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_template_description/getRandomMagentoProductId'), {
                method: 'post',
                parameters: {
                    store_id: $('description_preview_store_id').value
                },
                onSuccess: function(transport) {
                    var response = transport.responseText.evalJSON();

                    if (response.success) {
                        $('description_preview_magento_product_id').value = response.product_id;
                    } else {
                        self.alert(response.message);
                    }
                }
            });
        },

        appendToTextarea: function(value) {
            if (value == '') {
                return;
            }

            if (typeof tinymce != 'undefined' && typeof tinymce.get('description_template') != 'undefined'
                && tinymce.get('description_template') != null) {

                var data = tinymce.get('description_template').getContent();
                tinymce.get('description_template').setContent(data + value);

                return;
            }

            var element = $('description_template');

            if (document.selection) {

                /* IE */
                element.focus();
                document.selection.createRange().text = value;
                element.focus();

            } else if (element.selectionStart || element.selectionStart == '0') {

                /* Webkit */
                var startPos = element.selectionStart;
                var endPos = element.selectionEnd;
                var scrollTop = element.scrollTop;
                element.value = element.value.substring(0, startPos) + value + element.value.substring(endPos, element.value.length);
                element.focus();
                element.selectionStart = startPos + value.length;
                element.selectionEnd = startPos + value.length;
                element.scrollTop = scrollTop;

            } else {

                element.value += value;
                element.focus();
            }
        },

        initDocumentsFieldObservers: function () {
            const tableWrapper = jQuery('#documents_table_wrapper');

            const addRowButtonSelector = '.add_row';
            const removeRowButtonSelector = '.remove_row';

            const documentTypeDropdownSelector = '.document-type'
            const changeModeDropdownSelector = '.document-mode';
            const magentoAttributeDropdownSelector = '.document-magento-attribute';
            const customValueInputSelector = '.document-custom-value';

            const validateRowCount = function () {
                tableWrapper.find(addRowButtonSelector).attr('disabled', function () {
                    return tableWrapper.find('tbody tr').length >= 10
                });
            };

            const addRow = function () {
                let clonedRow = tableWrapper.find('tbody tr:last').clone();

                const incrementCallback = (index, name) => name.replace(/(\d+)/, ($0, $1) => ++$1)

                clonedRow.find(documentTypeDropdownSelector)
                        .prop('selectedIndex', 0)
                        .attr('name', incrementCallback)
                        .attr('id', incrementCallback)

                clonedRow.find(changeModeDropdownSelector)
                        .prop('selectedIndex', 0)
                        .attr('name', incrementCallback)
                        .attr('id', incrementCallback)

                clonedRow.find(magentoAttributeDropdownSelector)
                        .prop('selectedIndex', 0)
                        .removeAttr('option_injected')
                        .attr('name', incrementCallback)
                        .attr('id', incrementCallback)
                        .show()
                        .find('option[value="new-one-attribute"]').remove()

                clonedRow.find(customValueInputSelector)
                        .attr('name', incrementCallback)
                        .attr('id', incrementCallback)
                        .val('')
                        .hide()

                clonedRow.find(removeRowButtonSelector).show();

                tableWrapper.find('tbody').append(clonedRow)

                validateRowCount();

                window.initializationCustomAttributeInputs();
            };

            const removeRow = function (e) {
                jQuery(e.target.closest('tr')).remove();
                validateRowCount();
            }

            const changeMode = function (e) {
                const select = jQuery(e.currentTarget);
                const currentRow = jQuery(e.target.closest('tr'));

                const attributesDropdown = currentRow.find(magentoAttributeDropdownSelector);
                const customValueInput = currentRow.find(customValueInputSelector);

                if (select.val() == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE')) {
                    attributesDropdown.hide();
                    customValueInput.show();
                }

                if (select.val() == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE')) {
                    attributesDropdown.show();
                    customValueInput.hide();
                }
            }

            tableWrapper.on('click', addRowButtonSelector, addRow);
            tableWrapper.on('click', removeRowButtonSelector, removeRow);
            tableWrapper.on('change', changeModeDropdownSelector, changeMode);
        }

        // ---------------------------------------
    });
});
