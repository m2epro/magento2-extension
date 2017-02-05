define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
    'mage/adminhtml/wysiwyg/tiny_mce/setup'
], function () {

    window.EbayTemplateDescription = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-validate-description-template', function(value, el) {

                if ($('description_mode').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_CUSTOM')) {
                    return true;
                }

                return Validation.get('required-entry').test(value,el);
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-condition-note-length', function(value) {

                if ($('condition_note_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_NOTE_MODE_NONE')) {
                    return true;
                }

                return value.length <= 1000;
            }, M2ePro.translator.translate('Seller Notes must be less then 1000 symbols.'));

            jQuery.validator.addMethod('M2ePro-validate-magento-product-id', function(value) {

                var isValidMagentoProductId = false;

                new Ajax.Request(M2ePro.url.get('ebay_template_description/checkMagentoProductId'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        product_id: value
                    },
                    onSuccess: function (transport) {
                        var response = transport.responseText.evalJSON();
                        isValidMagentoProductId = response.result;
                    }
                });

                return isValidMagentoProductId;
            }, M2ePro.translator.translate('Please enter a valid Magento product ID.'));
        },

        initObservers: function()
        {
            $('title_mode')
                .observe('change', EbayTemplateDescriptionObj.title_mode_change)
                .simulate('change');

            $('subtitle_mode')
                .observe('change', EbayTemplateDescriptionObj.subtitle_mode_change)
                .simulate('change');

            $('item_condition')
                .observe('change', EbayTemplateDescriptionObj.item_condition_change)
                .simulate('change');

            $('condition_note_mode')
                .observe('change', EbayTemplateDescriptionObj.condition_note_mode_change)
                .simulate('change');

            $('watermark_scale')
                .observe('change', EbayTemplateDescriptionObj.watermark_scale_mode_change)
                .simulate('change');

            $('watermark_mode')
                .observe('change', EbayTemplateDescriptionObj.watermark_mode_change)
                .simulate('change');

            $('description_mode')
                .observe('change', EbayTemplateDescriptionObj.description_mode_change)
                .simulate('change');

            $('description_template_show_hide_wysiwyg')
                .observe('click', wysiwygdescription_template.toggle.bind(wysiwygdescription_template))
                .observe('click', EbayTemplateDescriptionObj.showHideWYSIWYG);

            $('image_main')
                .observe('change', EbayTemplateDescriptionObj.image_main_change)
                .simulate('change');

            $('gallery_images')
                .observe('change', EbayTemplateDescriptionObj.gallery_images_change)
                .simulate('change');

            $('variation_images')
                .observe('change', EbayTemplateDescriptionObj.variation_images_change)
                .simulate('change');

            $('product_details_ean',
                'product_details_upc',
                'product_details_epid',
                'product_details_isbn'
            ).each(function(element) {
                element.observe('change', EbayTemplateDescriptionObj.product_details_specification_visibility_change)
                    .simulate('change');
            });

            $('product_details_brand')
                .observe('change', EbayTemplateDescriptionObj.product_details_brand_change)
                .simulate('change');

            $('product_details_mpn')
                .observe('change', EbayTemplateDescriptionObj.product_details_mpn_change);

            $('custom_inserts_open_popup')
                .observe('click', EbayTemplateDescriptionObj.customInsertsOpenPopup);

            this.initCustomInsertsPopup();
            this.initPreviewPopup();
        },

        initWYSIWYG: function ()
        {
            tinymce.dom.Event._pageInit();
        },

        // ---------------------------------------

        duplicateClick: function(headId, chapter_when_duplicate_text, templateNick)
        {
            var watermarkImageContainer = $('watermark_uploaded_image_container');

            if (watermarkImageContainer) {
                watermarkImageContainer.remove();
                $('watermark_image').addClassName('M2ePro-required-when-visible');
                $('watermark_image_container').addClassName('_required');
            }

            EbayTemplateEditObj.duplicateClick(headId, chapter_when_duplicate_text, templateNick);
        },

        // ---------------------------------------

        title_mode_change: function()
        {
            var self = EbayTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'custom_title_tr');
        },

        subtitle_mode_change: function()
        {
            var self = EbayTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'custom_subtitle_tr');
        },

        description_mode_change: function()
        {
            var self = EbayTemplateDescriptionObj;

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
                    wysiwygdescription_template.turnOff();
                }

                $$('.c-custom_description_tr').invoke('show');
            } else {
                if (viewEditCustomDescription) {
                    viewEditCustomDescription.remove();
                }
            }
        },

        view_edit_custom_change: function()
        {
            if (typeof wysiwygdescription_template !== 'undefined' && $('description_editor_type').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_SIMPLE')) {
                wysiwygdescription_template.turnOff();
            }

            $$('.c-custom_description_tr').invoke('show');
            $('view_edit_custom_description').hide();
        },

        item_condition_change: function()
        {
            $('condition_note_tr').show();
            $('condition_note_mode').simulate('change');

            var self = EbayTemplateDescriptionObj,
                isConditionNoteNeeded = true,

                conditionValue = $('condition_value'),
                conditionAttribute = $('condition_attribute');

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_MODE_NONE')) {
                isConditionNoteNeeded = false;
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_MODE_EBAY')) {
                self.updateHiddenValue(this, conditionValue);
                conditionAttribute.value = '';
                isConditionNoteNeeded = conditionValue.value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_EBAY_NEW');
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

        condition_note_mode_change: function()
        {
            var self = EbayTemplateDescriptionObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_NOTE_MODE_NONE')) {
                $('condition_note_template').update('');
            }

            self.setTextVisibilityMode(this, 'custom_condition_note_tr');
            self.setTextVisibilityMode(this, 'custom_condition_note_attributes_tr');
        },

        watermark_mode_change: function()
        {
            var self = EbayTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'watermark_image_container');
            if ($('watermark_uploaded_image_container')) {
                self.setTextVisibilityMode(this, 'watermark_uploaded_image_container');
            }

            if ($('watermark_scale').value != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::WATERMARK_SCALE_MODE_STRETCH')) {
                self.setTextVisibilityMode(this, 'watermark_position_container');
            }

            self.setTextVisibilityMode(this, 'watermark_scale_container');
            self.setTextVisibilityMode(this, 'watermark_transparent_container');
        },

        watermark_scale_mode_change: function()
        {
            var self = EbayTemplateDescriptionObj;

            $('watermark_position_container').show();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::WATERMARK_SCALE_MODE_STRETCH')) {
                $('watermark_position_container').hide();
            }
        },

        setTextVisibilityMode: function(obj, elementName)
        {
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

        image_main_change: function()
        {
            var self = EbayTemplateDescriptionObj;

            $('gallery_images_mode_tr', 'variation_images_mode_tr', 'use_supersize_images_tr',
              'default_image_url_tr', 'watermark_block').invoke('show');

            if ($$('#variation_configurable_images option').length > 1) {
                $('variation_configurable_images_container').show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::IMAGE_MAIN_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('image_main_attribute'));
            } else {
                $('image_main_attribute').value = '';
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::IMAGE_MAIN_MODE_NONE')) {
                $('gallery_images_mode_tr', 'variation_images_mode_tr', 'variation_configurable_images_container',
                  'use_supersize_images_tr', 'default_image_url_tr').invoke('hide');

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

        gallery_images_change: function()
        {
            var self = EbayTemplateDescriptionObj;

            $('gallery_images_limit').value = '';
            $('gallery_images_attribute').value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::GALLERY_IMAGES_MODE_PRODUCT')) {
                self.updateHiddenValue(this, $('gallery_images_limit'));
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::GALLERY_IMAGES_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $('gallery_images_attribute'));
            }
        },

        variation_images_change: function()
        {
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

        image_width_mode_change: function()
        {
            $('image_width_span')[this.value == 1 ? 'show' : 'hide']();
        },

        image_height_mode_change: function()
        {
            $('image_height_span')[this.value == 1 ? 'show' : 'hide']();
        },

        image_margin_mode_change: function()
        {
            $('image_margin_span')[this.value == 1 ? 'show' : 'hide']();
        },

        select_attributes_image_change: function()
        {
            $$('.all-products-images').invoke(this.value == 'media_gallery' ? 'show' : 'hide');
            $$('.all-products-image').invoke(this.value == 'image' ? 'show' : 'hide');
            if (this.value == 'image') {
                $('display_products_images').value = 'custom_settings';
            }
            $('display_products_images').simulate('change');
        },

        display_products_images_change: function()
        {
            $$('.products-images-custom-settings').invoke('hide');
            $$('.products-images-gallery-view').invoke('hide');

            if (this.value == 'gallery_view') {
                $$('.products-images-gallery-view').invoke('show');
            } else {
                $$('.products-images-custom-settings').invoke('show');
            }

            jQuery('.products-images-mode-change-label').each(function (index, elem) {
                jQuery(elem).find('.label span').text(jQuery(elem).find('.' + this.value).text());
            }.bind(this));
        },

        product_details_specification_visibility_change: function()
        {
            var self = EbayTemplateDescriptionObj,
                modeNone = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_NONE'),
                modeDoesNotApply = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY');

            var isNotAttributeMode = function(element) {
                return element.value == modeNone || element.value == modeDoesNotApply;
            };

            if ($('product_details_ean', 'product_details_upc', 'product_details_isbn', 'product_details_brand')
                    .every(isNotAttributeMode) && $('product_details_epid').value == modeNone
            ) {
                $('product_details_specification_separator').hide();
                $$('.product-details-specification').each(function(element) {
                    element.hide();
                    element.down('.select').down().selectedIndex = 1;
                });
            } else {
                $('product_details_specification_separator').show();
                var hiddenElement = $(this.id+'_attribute');
                if (!hiddenElement) {
                    return;
                }
                $$('.product-details-specification').invoke('show');
                self.updateHiddenValue(this, hiddenElement);
            }
        },

        product_details_brand_change: function()
        {
            var self = EbayTemplateDescriptionObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_NONE') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY')) {
                $('product_details_mpn_tr').hide();
                $('product_details_mpn').selectedIndex = 0;
            } else {
                $('product_details_mpn_tr').show();
                $$('.product-details-specification').invoke('show');
                self.updateHiddenValue(this, $(this.id+'_attribute'));
            }

            self.product_details_specification_visibility_change();
        },

        product_details_mpn_change: function()
        {
            var self = EbayTemplateDescriptionObj;

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::PRODUCT_DETAILS_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, $(this.id+'_attribute'));
            }
        },

        showHideWYSIWYG: function ()
        {
            var label;
            if ($('description_template').visible()) {
                label = M2ePro.translator.translate('Show Editor');
                $('description_editor_type').value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_SIMPLE');
            } else {
                label = M2ePro.translator.translate('Hide Editor');
                $('description_editor_type').value = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::EDITOR_TYPE_TINYMCE');
            }
            this.select('span').first().update(label);
        },

        initCustomInsertsPopup: function ()
        {
            var popup = jQuery('#custom_inserts_popup');
            if (!popup.find('form').length) {
                popup.wrapInner('<form id="description_custom_inserts_form"></form>');
                CommonObj.initFormValidation('#description_custom_inserts_form');
            }

            popup.modal({
                title: M2ePro.translator.translate('Custom Insertions'),
                type: 'slide',
                buttons: [],
                closed: function () {
                    EbayTemplateDescriptionObj.customInsertsOnClosePopup();
                }
            });
        },

        customInsertsOpenPopup: function ()
        {
            jQuery('#custom_inserts_popup').modal('openModal');

            EbayTemplateDescriptionObj.observeImageAttributes();
        },

        customInsertsOnClosePopup: function ()
        {
            jQuery('#description_custom_inserts_form').trigger('reset').validate().resetForm();
            EbayTemplateDescriptionObj.stopObservingImageAttributes();
        },

        customInsertsClosePopup: function (callback)
        {
            jQuery('#custom_inserts_popup').modal({
                closed: function () {
                    callback && callback();

                    // prevent callback closure
                    callback = undefined;

                    EbayTemplateDescriptionObj.customInsertsOnClosePopup();
                }
            }).modal('closeModal');
        },

        insertProductAttribute: function ()
        {
            this.customInsertsClosePopup(function () {
                AttributeObj.appendToTextarea('#' + $('custom_inserts_product_attribute').value + '#');
            });
        },

        insertM2eProAttribute: function ()
        {
            this.customInsertsClosePopup(function () {
                AttributeObj.appendToTextarea('#value[' + $('custom_inserts_m2epro_attribute').value + ']#');
            });
        },

        insertGallery: function()
        {
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

            if ($('select_attributes_image').value == 'media_gallery' && $('display_products_images').value == 'gallery_view')  {
                template += '2';
            } else if ($('image_linked_mode').value == '1') {
                template += '1';
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

            this.customInsertsClosePopup(function () {
                AttributeObj.appendToTextarea(template);
            });
        },

        // ---------------------------------------

        observeImageAttributes: function()
        {
            $('image_width_mode').observe('change', EbayTemplateDescriptionObj.image_width_mode_change).simulate('change');
            $('image_height_mode').observe('change', EbayTemplateDescriptionObj.image_height_mode_change).simulate('change');
            $('image_margin_mode').observe('change', EbayTemplateDescriptionObj.image_margin_mode_change).simulate('change');

            $('select_attributes_image')
                    .observe('change', EbayTemplateDescriptionObj.select_attributes_image_change)
                    .simulate('change');

            $('display_products_images')
                    .observe('change', EbayTemplateDescriptionObj.display_products_images_change)
                    .simulate('change');

            if($('watermark_mode').value == 1) {
                this.setTextVisibilityMode($('watermark_mode'), 'products_images_watermark_mode');
                $('image_insertion_watermark_mode').selectedIndex = 1;
            } else {
                this.setTextVisibilityMode($('watermark_mode'), 'products_images_watermark_mode');
                $('image_insertion_watermark_mode').selectedIndex = 0;
            }
        },

        stopObservingImageAttributes: function()
        {
            $('image_width_mode').stopObserving();
            $('image_height_mode').stopObserving();
            $('image_margin_mode').stopObserving();

            $('select_attributes_image').stopObserving();
        },

        // ---------------------------------------

        saveWatermarkImage: function(callback, params)
        {
            var form  = $('edit_form');

            form.action = M2ePro.url.get('ebay_template_description/saveWatermarkImage');
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

        initPreviewPopup: function ()
        {
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
                    click: function (event) {
                        this.closeModal(event);
                    }
                },{
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
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
                closed: function () {
                    jQuery('#description_preview_form').trigger('reset').validate().resetForm();
                }
            });
        },

        openPreviewPopup: function ()
        {
            if ($('description_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_CUSTOM') && !$('description_template').value.length) {
                this.alert(M2ePro.translator.translate('Please enter Description Value.'));
                return;
            }

            jQuery('#description_preview_popup').modal('openModal');
        },

        selectProductIdRandomly: function ()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_template_description/getRandomMagentoProductId'), {
                method: 'post',
                parameters: {
                    store_id: $('description_preview_store_id').value
                },
                onSuccess: function (transport) {
                    var response = transport.responseText.evalJSON();

                    if (response.success) {
                        $('description_preview_magento_product_id').value = response.product_id;
                    } else {
                        self.alert(response.message);
                    }
                }
            });
        }

        // ---------------------------------------
    });
});