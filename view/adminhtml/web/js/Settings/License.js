define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (jQuery, modal, MessagesObj) {
    window.License = Class.create(Common, {

        // ---------------------------------------

        changeLicenseKeyPopup: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('settings_license/change'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {

                    var content = transport.responseText;
                    var title = M2ePro.translator.translate('Use Existing License');

                    LicenseObj.openPopup(title, content, LicenseObj.confirmLicenseKey.bind(self));
                }
            });
        },

        // ---------------------------------------

        confirmLicenseKey: function()
        {
            var self = this;

            if (!this.isValidForm()) {
                return false;
            }

            var formData = $('edit_form').serialize(true);

            new Ajax.Request(M2ePro.url.get('settings_license/change'), {
                method: 'post',
                asynchronous: true,
                parameters: formData,
                onSuccess: self.processOnSuccess
            });

            return true;
        },

        processOnSuccess: function(transport) {
            var self = window.LicenseObj;
            var result = transport.responseText;
            if (!result.isJSON()) {
                MessagesObj.addError(result);
            }

            result = JSON.parse(result);
            MessagesObj.clear();

            if (result.success) {
                MessagesObj.addSuccess(result.message);
            } else {
                MessagesObj.addError(result.message);
            }
            self.reloadLicenseTab();
        },

        // ---------------------------------------

        completeStep: function()
        {
            var self = this;
            var checkResult = false;

            new Ajax.Request(M2ePro.url.get('adminhtml_configuration_license/checkLicense'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {
                    checkResult = transport.responseText.evalJSON()['ok'];
                    if (checkResult) {
                        window.opener.completeStep = 1;
                        window.close();
                    } else {
                        MessageObj.addError(M2ePro.translator.translate('You must get valid Trial or Live License Key.'));
                    }
                }
            });
        },

        // ---------------------------------------

        openPopup: function(title, content, confirmCallback, type) {
            type = type || 'popup';
            var modalPopup = $('modal_popup');
            if (modalPopup) {
                modalPopup.remove();
            }

            var modalDialogMessage = new Element('div', {
                id: 'modal_popup'
            });

            var popup = jQuery(modalDialogMessage).modal({
                title: title,
                modalClass: type === 'popup' ? 'width-500' : '',
                type: type,
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: type === 'popup' ? 'action-secondary action-dismiss' : 'action-default action-dismiss',
                    click: function() {
                        this.closeModal();
                    }
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    id: 'save_popup_button',
                    click: function () {

                        if (confirmCallback) {
                            var result = confirmCallback();
                            result && this.closeModal();
                        } else {
                            this.closeModal();
                        }
                    }
                }],
                closed: function() {
                    modalDialogMessage.innerHTML = '';

                    return true;
                }
            });
            popup.modal('openModal');

            modalDialogMessage.insert(content);
            modalDialogMessage.innerHTML.evalScripts();

            this.initFormValidation(popup.find('form'));

            return popup;
        },

        // ---------------------------------------

        refreshStatus: function()
        {
            var self = this;
            new Ajax.Request(M2ePro.url.get('settings_license/refreshStatus'), {
                method: 'post',
                asynchronous: true,
                onSuccess: self.processOnSuccess
            });
        },

        // ---------------------------------------

        reloadLicenseTab: function()
        {
            BlockNoticeObj.removeInitializedBlock('block_notice_configuration_license');
            new Ajax.Request(M2ePro.url.get('settings_license/section'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {
                    var container = $$('#container > div.admin__scope-old')[0];
                    container.innerHTML = transport.responseText;
                    container.innerHTML.evalScripts();
                    CommonObj.scrollPageToTop();
                }
            });
        }
    });
});
