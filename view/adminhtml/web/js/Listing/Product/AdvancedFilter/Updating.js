define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Plugin/Confirm',
    'M2ePro/Listing/Product/AdvancedFilter',
], function (modal, MessageObj, confirm) {

    window.ListingProductAdvancedFilterUpdating = Class.create({

        init: function (ruleEntityId, viewStateKey, prefix) {
            this.ruleEntityId = ruleEntityId;
            this.viewStateKey = viewStateKey;
            this.prefix = prefix;

            this.advancedFilter = new ListingProductAdvancedFilterObj();
        },

        openUpdateFilterPopup: function () {
            const self = this;
            const content = jQuery('#update_filter_popup_content');
            content.removeClass('hidden');

            modal({
                title: M2ePro.translator.translate('Update Filter'),
                type: 'popup',
                modalClass: 'width-500',
                buttons: [
                    {
                        text: M2ePro.translator.translate('Cancel'),
                        class: 'action-secondary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: M2ePro.translator.translate('Update'),
                        class: 'action-primary',
                        click: function () {
                            const modal = this;
                            const success = function () {
                                self.advancedFilter.submitForm();
                                content.remove();
                            };
                            const validationFail = function (message) {
                                MessageObj.clear();
                                MessageObj.addError(message);
                            };
                            self.sendForm(success, validationFail);
                            modal.closeModal();
                        }
                    },
                ],
                closed: function () {
                    content.addClass('hidden')
                },
            }, content);

            content.modal('openModal');
        },

        sendForm: function (successCallback, validationFailCallback) {
            new Ajax.Request(M2ePro.url.get('listing_product_advanced_filter/update'), {
                method: 'post',
                parameters: {
                    title: jQuery('#advanced_filter_name_input_update').val(),
                    form_data: jQuery('#rule_form').serialize(),
                    prefix: this.prefix,
                    rule_entity_id: this.ruleEntityId,
                    view_state_key: this.viewStateKey,
                },
                onSuccess: function (response) {
                    const result = JSON.parse(response.transport.response);
                    if (result['result']) {
                        successCallback();

                        return;
                    }

                    validationFailCallback(result['message']);
                }
            });
        },

        delete: function () {
            const self = this;
            confirm({
                actions: {
                    confirm: function() {
                        new Ajax.Request(M2ePro.url.get('listing_product_advanced_filter/delete'), {
                            method: 'post',
                            parameters: {
                                rule_entity_id: self.ruleEntityId,
                                view_state_key: self.viewStateKey,
                            },
                            onSuccess: function () {
                                self.advancedFilter.addClearRuleFormInput();
                                self.advancedFilter.submitForm();
                            }
                        });
                    },
                    cancel: function() {
                        return false;
                    }
                }
            })
        },

        back: function () {
            this.advancedFilter.addUpdatingBackInput();
            this.advancedFilter.submitForm();
        },
    });
});
