
define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function(modal){

    window.EbaySettingsMotors = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {
        },

        // Manage Compatibility Dictionary
        // ---------------------------------------

        manageMotorsRecords: function (motorsType, title) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_settings_motors/getManagePopup'), {
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_manage_popup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    self.managePupup = jQuery('#ebay_settings_motors_manage_popup');

                    modal({
                        title: title,
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.managePupup.modal('closeModal')
                            }
                        }]
                    }, self.managePupup);

                    self.managePupup.modal('openModal');

                    $('motors_type').value = motorsType;

                    var spanStatEpids = $('database-custom-count');

                    if (motorsType == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Motors::TYPE_EPID_MOTOR')) {
                        spanStatEpids.innerHTML = $('epids_motor_custom_count').innerHTML;
                    } else if (motorsType == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Motors::TYPE_EPID_UK')) {
                        spanStatEpids.innerHTML = $('epids_uk_custom_count').innerHTML;
                    } else if (motorsType == M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Motors::TYPE_EPID_DE')) {
                        spanStatEpids.innerHTML = $('epids_de_custom_count').innerHTML;
                    } else {
                        spanStatEpids.innerHTML = $('ktypes_custom_count').innerHTML;
                    }

                    self.initFormValidation('#ebay_settings_motors_form');
                }
            });
        },

        importMotorsRecords: function () {
            if (!jQuery('#ebay_settings_motors_form').valid()) {
                return false;
            }

            $('ebay_settings_motors_form').submit();
        },

        clearAddedMotorsRecords: function () {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        var url = M2ePro.url.get('ebay_settings_motors/clearAddedMotorsData');
                        self.postForm(url, {motors_type: $('motors_type').value});
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        }

        // ---------------------------------------
    });

});