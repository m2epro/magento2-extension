define([
    'jquery',
    'M2ePro/Plugin/Messages',
    'mage/translate',
    'M2ePro/Template/Edit',
    'M2ePro/Common',
    'Magento_Ui/js/modal/modal'
], function (jQuery, MessagesObj, $t) {
    window.Settings = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {

            this.messageObj = Object.create(MessagesObj);
            this.messageObj.setContainer('#anchor-content');

            this.templateEdit = new TemplateEdit();

            this.initFormValidation();
        },

        // ---------------------------------------

        saveSettings: function ()
        {
            let isFormValid = true;
            const uiTabs = jQuery.find('div.ui-tabs-panel');
            uiTabs.forEach(item => {
                const elementId = item.getAttribute('data-ui-id').split('-').pop();
                if (isFormValid) {
                    const form = jQuery(item).find('form');
                    if (form.length) {
                        if (!form.valid()) {
                            isFormValid = false;
                            return;
                        }

                        if (!M2ePro.url.urls[elementId]) {
                            return;
                        }

                        jQuery("a[name='" + elementId + "']").removeClass('_changed _error');
                        var formData = form.serialize(true);
                        formData.tab = elementId;
                        const result = this.submitTab(M2ePro.url.get(elementId), formData);
                        this.afterSaveSettings(elementId, result);
                    }
                }
            });
        },

        afterSaveSettings: function (tabId, response) {

        },

        restoreAllHelpsAndRememberedChoices: function ()
        {
            const self = this;
            let modalDialogMessage = $('modal_interface_dialog');

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('div', {
                    id: 'modal_interface_dialog'
                });
            }

            jQuery(modalDialogMessage).confirm({
                title: $t('Are you sure?'),
                actions: {
                    confirm: function() {

                        new Ajax.Request(M2ePro.url.get('settings_interface/restoreRememberedChoices'), {
                            method: 'get',
                            asynchronous: true,
                            onSuccess: function(transport) {

                                BlockNoticeObj.deleteAllHashedStorage();
                                self.templateEdit.forgetSkipSaveConfirmation();

                                self.writeMessage($t('Help Blocks have been restored.'), true);
                            }
                        });
                    }
                },
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $t('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        // ---------------------------------------

        submitTab: function(url, formData)
        {
            const self = this;

            let submitResult = null;

            new Ajax.Request(url, {
                method: 'post',
                asynchronous: false,
                parameters: formData || {},
                onSuccess: function(transport) {
                    const response = transport.responseText;

                    if (!response.isJSON()) {
                        self.writeMessage(response, false);

                        submitResult = false;

                        return;
                    }

                    const result = JSON.parse(response);
                    submitResult = result;

                    if (typeof result.view_show_block_notices_mode !== 'undefined') {
                        BLOCK_NOTICES_SHOW = result.view_show_block_notices_mode;
                        BlockNoticeObj.initializedBlocks = [];
                        BlockNoticeObj.init();
                    }

                    if (result.messages && Array.isArray(result.messages) && result.messages.length) {
                        self.scrollPageToTop();
                        result.messages.forEach(function(el) {
                            const key = Object.keys(el).shift();
                            self.messageObj['add'+key.capitalize()](el[key]);
                        });

                        return;
                    }

                    if (result.success) {
                        self.writeMessage($t('Settings saved'), true);

                        return;
                    }
                    self.writeMessage($t('Error'), false);
                }
            });

            return submitResult;
        },

        writeMessage: function (text, isSuccess) {
            this.messageObj.clear();
            if (isSuccess) {
                this.messageObj.addSuccess(text);
            } else {
                this.messageObj.addError(text);
            }
        }

        // ---------------------------------------
    });
});
