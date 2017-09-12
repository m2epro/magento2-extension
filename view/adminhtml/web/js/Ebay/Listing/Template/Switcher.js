define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function(jQuery, modal) {

    window.EbayListingTemplateSwitcher = Class.create(Common, {

        // ---------------------------------------

        storeId: null,
        marketplaceId: null,
        checkAttributesAvailability: false,
        listingProductIds: '',

        // ---------------------------------------

        initialize: function() {
            jQuery.validator.addMethod('M2ePro-validate-ebay-template-title', function(value, el) {

                var mode = base64_decode(value).evalJSON().mode;

                return mode !== null;
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validate-ebay-template-title', function(value, el) {

                var templateNick = el.name.substr(0, el.name.indexOf('['));

                return EbayListingTemplateSwitcherObj.isTemplateTitleUnique(templateNick, value);
            }, M2ePro.translator.translate('Policy with the same Title already exists.'));
        },

        // ---------------------------------------

        getSwitcherNickByElementId: function(id)
        {
            return id.replace('template_', '');
        },

        getSwitcherElementId: function(templateNick)
        {
            return 'template_' + templateNick;
        },

        getSwitcher: function(templateNick)
        {
            return $(this.getSwitcherElementId(templateNick));
        },

        getSwitcherValueId: function(templateNick)
        {
            var switcher = this.getSwitcher(templateNick);
            var switcherValue = base64_decode(switcher.value).evalJSON();

            return switcherValue.id;
        },

        getSwitcherValueMode: function(templateNick)
        {
            var switcher = this.getSwitcher(templateNick);
            var switcherValue = base64_decode(switcher.value).evalJSON();

            return switcherValue.mode;
        },

        isSwitcherValueModeEmpty: function(templateNick)
        {
            return this.getSwitcherValueMode(templateNick) === null;
        },

        isSwitcherValueModeParent: function(templateNick)
        {
            return this.getSwitcherValueMode(templateNick) == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_PARENT');
        },

        isSwitcherValueModeCustom: function(templateNick)
        {
            return this.getSwitcherValueMode(templateNick) == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_CUSTOM');
        },

        isSwitcherValueModeTemplate: function(templateNick)
        {
            return this.getSwitcherValueMode(templateNick) == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_TEMPLATE');
        },

        isExistSynchronizationTab: function()
        {
            return typeof EbayTemplateSynchronizationHandlerObj != 'undefined';
        },

        isNeededSaveWatermarkImage: function(ajaxResponse)
        {
            var isDescriptionTemplate = false;

            ajaxResponse.each(function(template) {
                if (template.nick == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION')) {
                    isDescriptionTemplate = true;
                }
            });

            return isDescriptionTemplate && $('watermark_image').value != '';
        },

        getTemplateDataContainer: function(templateNick)
        {
            return $('template_' + templateNick + '_data_container');
        },

        // ---------------------------------------

        change: function()
        {
            var templateNick = EbayListingTemplateSwitcherObj.getSwitcherNickByElementId(this.id);
            var templateMode = EbayListingTemplateSwitcherObj.getSwitcherValueMode(templateNick);

            EbayListingTemplateSwitcherObj.clearMessages(templateNick);

            switch (templateMode) {
                case M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_PARENT'):
                    EbayListingTemplateSwitcherObj.clearContent(templateNick);
                    break;

                case M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_CUSTOM'):
                    EbayListingTemplateSwitcherObj.reloadContent(templateNick);
                    break;

                case M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_TEMPLATE'):
                    EbayListingTemplateSwitcherObj.clearContent(templateNick);
                    EbayListingTemplateSwitcherObj.checkMessages(templateNick);
                    break;
            }

            EbayListingTemplateSwitcherObj.hideEmptyOption(EbayListingTemplateSwitcherObj.getSwitcher(templateNick));

            if (!EbayListingTemplateSwitcherObj.isSwitcherValueModeCustom(templateNick)) {
                EbayListingTemplateSwitcherObj.updateButtonsVisibility(templateNick);
                EbayListingTemplateSwitcherObj.updateEditVisibility(templateNick);
                EbayListingTemplateSwitcherObj.updateTemplateLabelVisibility(templateNick);
            }
        },

        // ---------------------------------------

        clearMessages: function(templateNick)
        {
            $('template_switcher_' + templateNick + '_messages').innerHTML = '';
        },

        checkMessages: function(templateNick)
        {
            if (!this.checkAttributesAvailability &&
                templateNick != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_SELLING_FORMAT') &&
                templateNick != M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_SHIPPING')
            ) {
                return;
            }

            var // template ID
                id = this.getSwitcherValueId(templateNick),
            // template nick
                nick = templateNick,
            // template data (for custom settings only) and listing product ids (only when we edit product settings)
                data = Form.serialize(this.getTemplateDataContainer(templateNick).id)
                    + '&listing_product_ids=' + this.listingProductIds,
            // store ID of the listing
                storeId = this.storeId,
            // marketplace ID of the listing
                marketplaceId = this.marketplaceId,
            // do we need to check attributes availability (only when we edit product settings)
                checkAttributesAvailability = this.checkAttributesAvailability,
            // container, where messages should be displayed
                container = 'template_switcher_' + templateNick + '_messages',
            // callback function, which should be called, when messages are displayed
                callback = function() {
                    var refresh = $(container).down('a.refresh-messages');
                    if (refresh) {
                        refresh.observe('click', function() {
                            this.checkMessages(templateNick);
                        }.bind(this))
                    }
                }.bind(this);

            TemplateHandlerObj.checkMessages(
                id,
                nick,
                data,
                storeId,
                marketplaceId,
                checkAttributesAvailability,
                container,
                callback
            );
        },

        // ---------------------------------------

        updateEditVisibility: function(templateNick)
        {
            var tdEdit = $('template_' + templateNick + '_edit');

            if (!tdEdit) {
                return;
            }

            if (this.isSwitcherValueModeTemplate(templateNick)) {
                tdEdit.show();
            } else {
                tdEdit.hide();
            }
        },

        updateButtonsVisibility: function(templateNick)
        {
            var divButtonsContainer = $('template_' + templateNick + '_buttons_container');

            if (!divButtonsContainer) {
                return;
            }

            if (this.isSwitcherValueModeCustom(templateNick)) {
                divButtonsContainer.show();
            } else {
                divButtonsContainer.hide();
            }
        },

        updateTemplateLabelVisibility: function(templateNick)
        {
            var labelContainer = $('template_' + templateNick + '_nick_label');
            var templateLabel = labelContainer.down('span.template');

            labelContainer.hide();
            templateLabel && templateLabel.hide();

            if (this.isSwitcherValueModeTemplate(templateNick)) {
                labelContainer.show();
                templateLabel && templateLabel.show();
            }

            if (this.isSwitcherValueModeEmpty(templateNick)) {
                labelContainer.hide();
            }
        },

        // ---------------------------------------

        scrollToFirstFailedElement: function()
        {
            var errors = $$('label.mage-error');

            // if (errors.length > 0) {
            //     $('#ebayListingTemplateEditTabs').showTabContent(errors[0]);
            // }

            // errors[0].up('table').scrollIntoView();
        },

        // ---------------------------------------

        saveSwitchers: function(callback)
        {
            var validationResult = jQuery('#edit_form').valid();

            if (EbayListingTemplateSwitcherObj.isExistSynchronizationTab()) {
                EbayTemplateSynchronizationHandlerObj.checkVirtualTabValidation();
            }

            if (!validationResult) {
                EbayListingTemplateSwitcherObj.scrollToFirstFailedElement();
                return;
            }

            $('edit_form').request({
                method: 'post',
                asynchronous: true,
                parameters: {},
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    response.each(function(template) {
                        EbayListingTemplateSwitcherObj.afterCustomSaveAsTemplate(template.nick, template.id, template.title);
                    });

                    var params = {};

                    $$('.template-switcher').each(function(switcher) {
                        params[switcher.name] = switcher.value;
                    });

                    if ($('ebayListingTemplateEditTabs')) {
                        params['tab'] = jQuery('#ebayListingTemplateEditTabs').data().tabs.active.find('a')[0].id.split('_').pop();
                    }

                    if (EbayListingTemplateSwitcherObj.isNeededSaveWatermarkImage(response)) {
                        EbayTemplateDescriptionObj.saveWatermarkImage(callback, params);
                    } else if (typeof callback == 'function') {
                        callback(params);
                    }

                }.bind(this)
            });
        },

        // ---------------------------------------

        isTemplateTitleUnique: function(templateNick, templateTitle)
        {
            var unique = true;

            new Ajax.Request(M2ePro.url.get('ebay_template/isTitleUnique'), {
                method: 'get',
                asynchronous: false,
                parameters: {
                    nick: templateNick,
                    title: templateTitle
                },
                onSuccess: function(transport) {
                    unique = transport.responseText.evalJSON()['unique'];
                }
            });

            return unique;
        },

        // ---------------------------------------

        validateCustomTemplate: function(templateNick)
        {
            var validationResult = $('template_' + templateNick + '_data_container').select('select, input').collect(
                function (el) {
                    return jQuery('#edit_form').form().validate().element(el);
                }
            );

            if (validationResult.indexOf(false) != -1) {
                EbayListingTemplateSwitcherObj.scrollToFirstFailedElement();
                return false;
            }

            return true;
        },

        // ---------------------------------------

        customSaveAsTemplate: function(templateNick)
        {
            if (!this.validateCustomTemplate(templateNick)) {
                return;
            }

            // jQuery('#edit_form').valid()

            new Ajax.Request(M2ePro.url.get('ebay_template/newTemplateHtml'), {
                method: 'GET',
                parameters: {
                    nick: templateNick
                },
                onSuccess: (function(transport) {
                    if ($('new_template_form_' + templateNick)) {
                        $('new_template_form_' + templateNick).remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    var form = jQuery('#new_template_form_' + templateNick);

                    form.form().validation();

                    modal({
                        title: M2ePro.translator.translate('Save as New Policy'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                form.modal('closeModal');
                                $('new_template_form_' + templateNick).remove()
                            }
                        },{
                            text: M2ePro.translator.translate('Save'),
                            class: 'action-primary action-accept',
                            click: function () {
                                if (!form.form().valid()) {
                                    return false;
                                }

                                $$('input[name="' + templateNick + '[id]"]')[0].value = '';
                                $$('input[name="' + templateNick + '[is_custom_template]"]')[0].value = 0;
                                $$('input[name="' + templateNick + '[title]"]')[0].value = $('template_title_' + templateNick).value;

                                $('edit_form').request({
                                    method: 'post',
                                    asynchronous: true,
                                    parameters: {
                                        nick: templateNick
                                    },
                                    onSuccess: function(transport) {

                                        var response = transport.responseText.evalJSON();

                                        response.each(function(template) {
                                            EbayListingTemplateSwitcherObj.addToSwitcher(template.nick, template.id, template.title);
                                            EbayListingTemplateSwitcherObj.clearContent(template.nick);
                                            EbayListingTemplateSwitcherObj.updateButtonsVisibility(template.nick);
                                            EbayListingTemplateSwitcherObj.updateEditVisibility(template.nick);
                                            EbayListingTemplateSwitcherObj.updateTemplateLabelVisibility(template.nick);
                                            EbayListingTemplateSwitcherHandlerObj.checkMessages(template.nick);
                                        });
                                    }.bind(this)
                                });

                                form.modal('closeModal');
                            }
                        }]
                    }, form);

                    form.modal('openModal');
                }).bind(this)
            });
        },

        afterCustomSaveAsTemplate: function(templateNick, templateId, templateTitle)
        {
            $$('input[name="' + templateNick + '[id]"]')[0].value = templateId;
            $$('input[name="' + templateNick + '[title]"]')[0].value = templateTitle;

            var switcher = EbayListingTemplateSwitcherObj.getSwitcher(templateNick);

            switcher.down('.template-switcher-custom-option').value = base64_encode(
                Object.toJSON({
                    mode: M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_CUSTOM'),
                    nick: templateNick,
                    id: templateId
                })
            );
        },

        // ---------------------------------------

        editTemplate: function(templateNick)
        {
            var templateId = this.getSwitcherValueId(templateNick);

            window.open(
                M2ePro.url.get('ebay_template/edit', {id: templateId, nick: templateNick, close_on_save: 1}), '_blank'
            );
        },

        // ---------------------------------------

        customizeTemplate: function(templateNick)
        {
            this.clearMessages(templateNick);
            this.reloadContent(templateNick, function()
            {
                $$('input[name="' + templateNick + '[id]"]')[0].value = EbayListingTemplateSwitcherObj.getSwitcherValueId(templateNick);
                $$('input[name="' + templateNick + '[is_custom_template]"]')[0].value = 1;
                $$('input[name="' + templateNick + '[title]"]')[0].value += ' [' + M2ePro.translator.translate('Customized') + ']';
            });
            this.getSwitcher(templateNick).selectedIndex = 0;
        },

        // ---------------------------------------

        clearContent: function(templateNick)
        {
            this.getTemplateDataContainer(templateNick).innerHTML = '';
            this.getTemplateDataContainer(templateNick).hide();
        },

        // ---------------------------------------

        reloadContent: function(templateNick, callback)
        {
            var id = this.getSwitcherValueId(templateNick);
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_template/getTemplateHtml'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    id: id,
                    nick: templateNick
                },
                onSuccess: function(transport) {

                    BlockNoticeObj.initializedBlocks = [];

                    self.getTemplateDataContainer(templateNick).replace(transport.responseText);
                    self.getTemplateDataContainer(templateNick).show();

                    self.updateButtonsVisibility(templateNick);
                    self.updateEditVisibility(templateNick);
                    self.updateTemplateLabelVisibility(templateNick);

                    if (typeof callback == 'function') {
                        callback();
                    }

                }
            });
        },

        // ---------------------------------------

        addToSwitcher: function(templateNick, templateId, templateTitle)
        {
            var switcher = this.getSwitcher(templateNick);
            var optionGroup = this.getTemplatesGroup(templateNick);

            var optionValue = {
                mode: M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Template_Manager::MODE_TEMPLATE'),
                nick: templateNick,
                id: templateId
            };
            var option = document.createElement('option');

            option.value = base64_encode(Object.toJSON(optionValue));
            option.innerHTML = templateTitle;

            $(optionGroup).appendChild(option);

            switcher.up('td').show();
            switcher.value = option.value;
        },

        getTemplatesGroup: function(templateNick)
        {
            var switcher = this.getSwitcher(templateNick);
            var optionGroup = switcher.down('optgroup.templates-group');

            if (typeof optionGroup != 'undefined') {
                return optionGroup;
            }

            optionGroup = document.createElement('optgroup');
            optionGroup.className = 'templates-group';
            optionGroup.label = M2ePro.translator.translate('Policies');

            switcher.appendChild(optionGroup);

            return optionGroup;
        }

        // ---------------------------------------
    });

});
