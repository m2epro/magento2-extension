define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/Product/AdvancedFilter',
], function (modal, MessageObj) {

    window.ListingProductAdvancedFilterCreating = Class.create({

        init: function (ruleNick, prefix, viewStateKey) {
            this.ruleNick = ruleNick;
            this.prefix = prefix;
            this.viewStateKey = viewStateKey;
            this.advancedFilter = new ListingProductAdvancedFilterObj();
        },

        openSaveFilterPopup: function () {
            const self = this;
            const content = jQuery('#new_filter_popup_content');
            content.removeClass('hidden');

            modal({
                title: M2ePro.translator.translate('Save Filter'),
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
                        text: M2ePro.translator.translate('Save'),
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
            new Ajax.Request(M2ePro.url.get('listing_product_advanced_filter/save'), {
                method: 'post',
                parameters: {
                    title: jQuery('#advanced_filter_name_input_create').val(),
                    form_data: jQuery('#rule_form').serialize(),
                    rule_nick: this.ruleNick,
                    prefix: this.prefix,
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

        back: function () {
            this.advancedFilter.addCreatingBackInput();
            this.advancedFilter.submitForm();
        },
    });
});
