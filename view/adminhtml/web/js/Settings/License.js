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
                asynchronous: false,
                parameters: formData,
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    if (!result.isJSON()) {
                        MessagesObj.addErrorMessage(result);
                    }

                    result = JSON.parse(result);

                    MessagesObj.clear();
                    self.appendGlobalMessages();

                    if (result.success) {
                        MessagesObj.addSuccessMessage(result.message);
                    } else {
                        MessagesObj.addErrorMessage(result.message);
                    }
                    CommonObj.scrollPageToTop();
                    self.reloadLicenseTab();
                }
            });

            return true;
        },

        // ---------------------------------------

        newLicenseKeyPopup: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('settings_license/create'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {

                    var content = transport.responseText;
                    var title = M2ePro.translator.translate('Create New License');

                    LicenseObj.openPopup(title, content, LicenseObj.newLicenseKey.bind(self), 'slide');
                }
            });
        },

        newLicenseKey: function()
        {
            var self = this;

            if (!this.isValidForm()) {
                return false;
            }

            new Ajax.Request(M2ePro.url.get('settings_license/create'), {
                method: 'post',
                asynchronous: true,
                parameters: $('edit_form').serialize(true),
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    if (!result.isJSON()) {
                        MessagesObj.addErrorMessage(result);
                    }

                    result = JSON.parse(result);

                    MessagesObj.clear();
                    self.appendGlobalMessages();

                    if (result.success) {
                        if (typeof result.license_key !== 'undefined') {
                            $('license_text_key_container').innerHTML = result.license_key;
                        }
                        MessagesObj.addSuccessMessage(result.message);
                    } else {
                        MessagesObj.addErrorMessage(result.message);
                    }
                    CommonObj.scrollPageToTop();
                    self.reloadLicenseTab();
                }
            });

            return true;
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
                        MagentoMessageObj.addError(M2ePro.translator.translate('You must get valid Trial or Live License Key.'));
                    }
                }
            });
        },

        // ---------------------------------------

        openPopup: function(title, content, confirmCallback, type)
        {
            var type = type || 'popup';
            if ($('modal_popup')) {
                $('modal_popup').remove();
            }

            var modalDialogMessage = new Element('div', {
                id: 'modal_popup'
            });

            var popup = jQuery(modalDialogMessage).modal({
                title: title,
                modalClass: type == 'popup' ? 'width-500' : '',
                type: type,
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: type == 'popup' ? 'action-secondary action-dismiss' : 'action-default action-dismiss',
                    click: function () {
                        this.closeModal();
                    }
                },{
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
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    if (!result.isJSON()) {
                        MessagesObj.addErrorMessage(result);
                    }

                    result = JSON.parse(result);
                    MessagesObj.clear();
                    self.appendGlobalMessages();

                    if (result.success) {
                        MessagesObj.addSuccessMessage(result.message);
                    } else {
                        MessagesObj.addErrorMessage(result.message);
                    }

                    CommonObj.scrollPageToTop();
                    self.reloadLicenseTab();
                }
            });
        },

        // ---------------------------------------

        appendGlobalMessages: function()
        {
            new Ajax.Request(M2ePro.url.get('getGlobalMessages'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    if (!result.isJSON()) {
                        return;
                    }

                    result = JSON.parse(result);

                    if (result && Array.isArray(result)) {
                        MessagesObj.clearGlobal();
                        result.forEach(function(item) {
                            var key = Object.keys(item)[0];
                            MessagesObj['addGlobal'+key.capitalize()+'Message'](item[key]);
                        });
                    }
                }
            });
        },

        reloadLicenseTab: function()
        {
            BlockNoticeObj.removeInitializedBlock('block_notice_configuration_license');

            new Ajax.Request(M2ePro.url.get('licenseTab'), {
                method: 'get',
                asynchronous: true,
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    $('configuration_settings_tabs_license_content').innerHTML = result;
                    $('configuration_settings_tabs_license_content').innerHTML.evalScripts();
                }
            });
        }

        // ---------------------------------------
    });
});