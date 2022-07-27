define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(jQuery, modal, MessageObj) {

    window.Synchronization = Class.create(Common, {

        // ---------------------------------------

        saveSettings: function()
        {
            MessageObj.clear();
            CommonObj.scrollPageToTop();

            new Ajax.Request(M2ePro.url.get('synch_formSubmit'), {
                method: 'post',
                parameters: {
                    instructions_mode: $('instructions_mode').value
                },
                asynchronous: true,
                onSuccess: function(transport) {
                    MessageObj.addSuccess(M2ePro.translator.translate('Synchronization Settings have been saved.'));
                }
            });
        }

        // ---------------------------------------
    });
});
