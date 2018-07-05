define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function (jQuery, modal) {

    window.EbayListingProductCategorySettingsMode = Class.create();
    EbayListingProductCategorySettingsMode.prototype = {

        // ---------------------------------------

        initialize: function (lastModeValue) {

            $$('input[name="mode"]').each(function(element) {
                element.observe('change', function() {
                    var modeSameRememberCheckbox = $('mode_same_remember_checkbox');
                    modeSameRememberCheckbox.checked = false;
                    modeSameRememberCheckbox.disabled = true;
                    this.value == 'same' && (modeSameRememberCheckbox.disabled = false);
                })
            });

            var modeElement = $$('input[value="'+lastModeValue+'"]').shift();

            modeElement.checked = true;
            modeElement.simulate('change');

            $('mode_same_remember_checkbox').observe('click', function(event) {

                if (!this.checked) {
                    return;
                }

                event.preventDefault();

                var content = jQuery('#mode_same_remember_pop_up_content');

                modal({
                    title: M2ePro.translator.translate('Apply Settings'),
                    type: 'popup',
                    buttons: [{
                        text: M2ePro.translator.translate('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function () {
                            content.modal('closeModal');
                        }
                    },{
                        text: M2ePro.translator.translate('Continue'),
                        class: 'action-primary action-accept',
                        click: function () {
                            var modeSameRememberCheckbox = $('mode_same_remember_checkbox');
                            modeSameRememberCheckbox.checked = true;
                            modeSameRememberCheckbox.disabled = false;
                            $('categories_mode_form').submit();
                        }
                    }]
                }, content);

                content.modal('openModal');
            });

        },

        // ---------------------------------------
    };
});