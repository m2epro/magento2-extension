define([
    'jquery',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (jQuery, MessagesObj) {
    window.Settings = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {
            this.initFormValidation();
        },

        // ---------------------------------------

        saveSettingsTab: function()
        {
            var tab = jQuery('div[aria-labelledby^=configuration_settings_tabs].ui-tabs-panel:visible');

            var elementId = tab.attr('id').toLowerCase()
                .replace(/^configuration_settings_tabs_/, '')
                .replace(/_content$/, ''),
                form = tab.find('form');

            if (form.length) {
                if (!form.valid()) {
                    return false;
                }

                if (!M2ePro.url.urls[elementId]) {
                    return false;
                }

                jQuery('.ui-tabs-active > a').removeClass('_changed _error');

                var formData = form[0].serialize(true);
                formData.tab = elementId;

                this.submitTab(M2ePro.url.get(elementId), formData);
            }
        },

        // ---------------------------------------

        submitTab: function(url, formData)
        {
            new Ajax.Request(url, {
                method: 'post',
                asynchronous: true,
                parameters: formData || {},
                onSuccess: function(transport) {
                    var result = transport.responseText;

                    MessagesObj.clear();
                    if (!result.isJSON()) {
                        MessagesObj.addErrorMessage(result);
                    }

                    result = JSON.parse(result);

                    if (typeof result['block_notices_show'] !== 'undefined') {
                        BLOCK_NOTICES_SHOW = result['block_notices_show'];
                        BlockNoticeObj.initializedBlocks = [];
                        BlockNoticeObj.init();
                    }

                    if (result.messages && Array.isArray(result.messages) && result.messages.length) {
                        result.messages.forEach(function(el) {
                            var key = Object.keys(el).shift();
                            MessagesObj['add'+key.capitalize()+'Message'](el[key]);
                        });
                        return;
                    }

                    if (result.success) {
                        MessagesObj.addSuccessMessage(M2ePro.translator.translate('Settings successfully saved'));
                    } else {
                        MessagesObj.addErrorMessage(M2ePro.translator.translate('Error'));
                    }

                }
            });
        }

        // ---------------------------------------
    });
});
