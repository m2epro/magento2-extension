define([
    'jquery',
    'M2ePro/Template/Edit'
],
function(jQuery) {

    window.EbayTemplateEdit = Class.create(TemplateEdit, {

        // ---------------------------------------

        templateNick: null,

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('validate-title-uniqueness', function(value) {

                var unique = false;
                var templateId = 0;

                if ($(EbayTemplateEditObj.templateNick + '_id')) {
                    templateId = $(EbayTemplateEditObj.templateNick + '_id').value;
                }

                new Ajax.Request(M2ePro.url.get('ebay_template/isTitleUnique'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        id_value: templateId,
                        title: value
                    },
                    onSuccess: function(transport)
                    {
                        unique = transport.responseText.evalJSON()['unique'];
                    }
                });

                return unique;
            }, M2ePro.translator.translate('Policy Title is not unique.'));
        },

        // ---------------------------------------

        initObservers: function ()
        {
            var $marketplace = jQuery('#marketplace_id');
            if ($marketplace.length) {
                $marketplace.on('change', this.loadTemplateData)
                    .trigger('change');
            } else {
                this.loadTemplateData(null);
            }
        },

        // ---------------------------------------

        getComponent: function()
        {
            return 'ebay';
        },

        // ---------------------------------------

        loadTemplateData: function(marketplaceId, callback)
        {
            if (typeof this.value != 'undefined' && this.value === '') {
                return;
            }

            var self = EbayTemplateEditObj;
            marketplaceId = this.value || marketplaceId;

            new Ajax.Request(M2ePro.url.get('ebay_template/getTemplateHtml'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    marketplace_id: marketplaceId
                },
                onSuccess: function(transport) {

                    var editFormData = $('edit_form_data');
                    if (!editFormData) {
                        editFormData = document.createElement('div');
                        editFormData.id = 'edit_form_data';

                        $('edit_form').appendChild(editFormData);
                    }

                    editFormData.innerHTML = transport.responseText;
                    editFormData.innerHTML.extractScripts()
                        .map(function(script) {
                            try {
                                eval(script);
                            } catch(e) {}
                        });

                    var titleInput = $$('input[name="'+self.templateNick+'[title]"]')[0];
                    var marketplaceIdInput = $$('input[name="'+self.templateNick+'[marketplace_id]"]')[0];

                    if ($('title').value.trim() == '') {
                        $('title').value = titleInput.value;
                    }

                    if (marketplaceIdInput) {
                        marketplaceIdInput.value = marketplaceId;
                    }

                    callback && callback();
                }
            });
        },

        // ---------------------------------------

        isValidForm: function()
        {
            var validationResult = true;

            validationResult &= jQuery('#edit_form').valid();
            validationResult &= Validation.validate($('title'));

            if ($('marketplace_id')) {
                validationResult &= Validation.validate($('marketplace_id'));
            }

            if ($('ebay_template_synchronization_edit_form_container')) {
                //EbayTemplateSynchronizationHandlerObj.checkVirtualTabValidation();
            }

            var titleInput = $$('input[name="'+EbayTemplateEditObj.templateNick+'[title]"]')[0];

            if (titleInput) {
                titleInput.value = $('title').value;
            }

            return validationResult;
        },

        // ---------------------------------------

        duplicateClick: function($super, headId, chapter_when_duplicate_text, templateNick)
        {
            $$('input[name="'+templateNick+'[id]"]')[0].value = '';

            // we don't need it here, but parent method requires the formSubmitNew url to be defined
            M2ePro.url.add({'formSubmitNew': ' '});

            $super(headId, chapter_when_duplicate_text);
        },

        // ---------------------------------------

        saveAndCloseClick: function(url, confirmText)
        {
            if (!this.isValidForm()) {
                return;
            }

            var self = this;

            if (confirmText && this.showConfirmMsg) {
                this.confirm(this.templateNick, confirmText, function () {
                    self.saveFormUsingAjax(url, self.templateNick);
                });
                return;
            }

            self.saveFormUsingAjax(url, self.templateNick);
        },

        saveFormUsingAjax: function (url, templateNick)
        {
            new Ajax.Request(url, {
                method: 'post',
                parameters: Form.serialize($('edit_form')),
                onSuccess: function(transport) {
                    var templates = transport.responseText.evalJSON();

                    if (templates.length && templates[0].nick == templateNick) {
                        window.close();
                    } else {
                        console.error('Policy Saving Error');
                    }
                }
            });
        }

        // ---------------------------------------
    });
});