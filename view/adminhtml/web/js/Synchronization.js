define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function(jQuery, modal, MessageObj) {

    window.Synchronization = Class.create(Common, {

        // ---------------------------------------

        initialize: function(synchProgressObj)
        {
            this.synchProgressObj = synchProgressObj;
        },

        // ---------------------------------------

        completeStep: function()
        {
            window.opener.completeStep = 1;
            window.close();
        },

        saveSettings: function(runSynch, components)
        {
            MessageObj.clear();
            runSynch  = runSynch || '';
            components = components || '';

            components = Object.isString(components)
                         ? [components]
                         : components;
            components = Object.toJSON(components);

            CommonObj.scrollPageToTop();

            var self = this;
            new Ajax.Request(M2ePro.url.get('synch_formSubmit'), {
                method: 'post',
                parameters: {
                    components: components,
                    instructions_mode: $('instructions_mode').value
                },
                asynchronous: true,
                onSuccess: function(transport) {
                    MessageObj.addSuccessMessage(M2ePro.translator.translate('Synchronization Settings have been saved.'));
                    if (runSynch != '') {
                        self[runSynch](components);
                    }
                }
            });
        },

        // ---------------------------------------

        moveChildBlockContent: function(childBlockId, destinationBlockId)
        {
            if (childBlockId == '' || destinationBlockId == '') {
                return;
            }

            $(destinationBlockId).appendChild($(childBlockId));
        }

        // ---------------------------------------
    });
});