define([
    'jquery',
    'mage/translate',
    'M2ePro/Plugin/Confirm',
    'M2ePro/Settings',
], ($, $t, confirm) => {
    window.EbaySettings = Class.create(Settings, {

        urlSetGpsrToCategory: null,

        initialize: function($super, urlSetGpsrToCategory) {
            $super();
            this.urlSetGpsrToCategory = urlSetGpsrToCategory;
        },

        afterSaveSettings: function(tabId, response) {
            if (tabId === 'mapping') { // Settings\Tabs::TAB_ID_MAPPING_ATTRIBUTES
                this.afterMappingProcess(response);
            }
        },

        afterMappingProcess: function(response) {
            if (!response.success) {
                return;
            }

            if (response.was_changed_gpsr) {
                this.gpsrHandle();

                return;
            }
        },

        gpsrHandle: function() {
            confirm(
                    {
                        title: $t('New Attribute Mapping Detected'),
                        content: $t('You\'ve successfully saved the default attribute mapping settings for eBay GPSR attributes. Would you like to apply these new default mappings to all your eBay categories now?'),
                        actions: {
                            confirm: () => {
                                this.gpsrSetToCategories(this.urlSetGpsrToCategory);
                            },
                            cancel: () => {},
                        },
                    },
            );
        },

        gpsrSetToCategories: function(url) {
            const self = this;

            new Ajax.Request(url, {
                method: 'post',
                asynchronous: true,
                parameters: {},
                onSuccess: function(transport) {
                    const response = transport.responseText;

                    if (!response.isJSON()) {
                        self.writeMessage(response, false);

                        return;
                    }

                    const result = JSON.parse(response);
                    if (!result.success) {
                        self.messageObj.addError($t('Error'));
                    }
                }
            });
        },

        onModeChange: function(channelAttributeCode, mode) {
            const customValueInput = $(`#${channelAttributeCode}_custom_value`);
            const customAttributeSelect = $(`#${channelAttributeCode}_custom_attribute`);

            if (mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\AttributeMapping\\Pair::VALUE_MODE_CUSTOM')) {
                customValueInput.show().removeAttr('disabled');
                customAttributeSelect.hide().attr('disabled', 'disabled');
            } else if (mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\AttributeMapping\\Pair::VALUE_MODE_ATTRIBUTE')) {
                customValueInput.hide().attr('disabled', 'disabled');
                customAttributeSelect.show().removeAttr('disabled');
            } else {
                customValueInput.hide().attr('disabled', 'disabled');
                customAttributeSelect.hide().attr('disabled', 'disabled');
            }
        },
    });
});
