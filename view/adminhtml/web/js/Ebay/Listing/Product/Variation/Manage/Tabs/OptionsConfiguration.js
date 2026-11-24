define([
    'jquery',
    'M2ePro/Plugin/Confirm',
    'M2ePro/Plugin/Messages',
    'mage/translate',
    'M2ePro/Common'
], function ($, Confirm, Messages, $t) {
    'use strict';

    window.EbayListingProductVariationManageTabsOptionsConfiguration = Class.create(Common, {
        initialize: function (config) {
            this.form = $('#' + config.formId);
            this.saveButton = $('#' + config.saveButtonId);
            Messages.setContainer('#' + config.messagesBlockId)

            this.initObservers();
            this.initValidators();
        },

        initObservers: function () {
            this.saveButton.on('click', () => this.saveButtonClick());
        },

        initValidators: function () {
            $.validator.addMethod('M2ePro-validate-mapping-option', function(value, el) {
                if (!value) {
                    return true;
                }

                const $el = $(el);
                const $fieldset = $el.closest('fieldset');
                const $selects = $fieldset.find('select.M2ePro-validate-mapping-option');

                let isUnique = true;

                $selects.each(function() {
                    if (this === el) {
                        return true;
                    }

                    if ($(this).val() === value) {
                        isUnique = false;
                        return false;
                    }
                });

                return isUnique;
            }, $t('This eBay value is already mapped to another option.'));

            const validator = this.form.validate({
                errorClass: 'mage-error ebay-mapping-option-error'
            });

            this.form.on('change', 'fieldset select.M2ePro-validate-mapping-option', function() {
                const $fieldset = $(this).closest('fieldset');

                $fieldset.find('label.ebay-mapping-option-error').remove();

                $fieldset.find('select.M2ePro-validate-mapping-option').each(function() {
                    validator.element(this);
                });
            });
        },

        saveButtonClick: function () {
            if (!this.form.valid()) {
                return;
            }

            const self = this;

            Confirm({
                title: $t('Apply Option Mapping to Other Products?'),
                content: $t('Would you like to save this mapping for all products with this eBay category or only for this one?'),
                buttons: [
                    {
                        text: $t('All Products'),
                        class: 'action-secondary',
                        click: function (event) {
                            self.saveOptions(true);
                            this.closeModal(event, true);
                        }
                    },
                    {
                        text: $t('This Product Only'),
                        class: 'action-primary',
                        click: function (event) {
                            self.saveOptions();
                            this.closeModal(event);
                        }
                    }
                ]
            });
        },

        saveOptions: function (isSaveForAllProducts = false) {
            let formData = this.form.serializeArray();

            formData.push({
                name: 'form_key',
                value: window.FORM_KEY
            });

            if (isSaveForAllProducts) {
                formData.push({
                    name: 'save_for_all_products',
                    value: true
                });
            }

            $.ajax({
                url: this.form.attr('action'),
                data: formData,
                type: 'POST',
                dataType: 'json',
                beforeSend: () => {
                    this.disableSaveButton();
                    Messages.clearAll();
                },
                success: (response)=> {
                    this.enableSaveButton();
                    Messages.addSuccess($t('Options Saved'));
                },
                error: () => this.enableSaveButton()
            });
        },

        disableSaveButton: function () {
            this.saveButton.prop('disabled', true)
        },

        enableSaveButton: function () {
            this.saveButton.prop('disabled', false)
        }
    });
});
