define([
    'jquery',
    'M2ePro/Plugin/Messages',
    'M2ePro/Template/Edit',
    'M2ePro/Common',
    'Magento_Ui/js/modal/modal'
], function (jQuery, MessagesObj) {
    window.Mapping = Class.create(Common, {

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
            var isFormValid = true;
            var uiTabs = jQuery.find('div.ui-tabs-panel');
            uiTabs.forEach(item => {
                var elementId = item.getAttribute('data-ui-id').split('-').pop();
                if (isFormValid) {
                    var form = jQuery(item).find('form');
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
                        this.submitTab(M2ePro.url.get(elementId), formData);
                    }
                }
            })
        },

        submitTab: function(url, formData)
        {
            if (!formData) {
                return;
            }

            var self = this;

            new Ajax.Request(url, {
                method: 'post',
                asynchronous: false,
                parameters: formData,
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    self.messageObj.clear();
                    if (!result.isJSON()) {
                        self.messageObj.addError(result);
                    }

                    result = JSON.parse(result);

                    if (typeof result['view_show_block_notices_mode'] !== 'undefined') {
                        BLOCK_NOTICES_SHOW = result['view_show_block_notices_mode'];
                        BlockNoticeObj.initializedBlocks = [];
                        BlockNoticeObj.init();
                    }

                    if (result.messages && Array.isArray(result.messages) && result.messages.length) {
                        self.scrollPageToTop();
                        result.messages.forEach(function(el) {
                            var key = Object.keys(el).shift();
                            self.messageObj['add'+key.capitalize()](el[key]);
                        });
                        return;
                    }

                    if (result.success) {
                        self.messageObj.addSuccess(M2ePro.translator.translate('Settings saved'));
                    } else {
                        self.messageObj.addError(M2ePro.translator.translate('Error'));
                    }

                }
            });
        }

        // ---------------------------------------
    });
});
