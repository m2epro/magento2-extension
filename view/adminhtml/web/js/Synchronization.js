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
                    templates_mode: $('templates_mode').value
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

        runAllEnabledNow: function(components)
        {
            this.synchProgressObj.runTask(
                M2ePro.translator.translate('Running All Enabled Tasks'),
                M2ePro.url.get('runAllEnabledNow'),
                components
            );
        },

        // ---------------------------------------

        moveChildBlockContent: function(childBlockId, destinationBlockId)
        {
            if (childBlockId == '' || destinationBlockId == '') {
                return;
            }

            $(destinationBlockId).appendChild($(childBlockId));
        },

        // ---------------------------------------

        showReviseAllConfirmPopup: function(component)
        {
            window.ReviseAllConfirmPopup = modal({
                title: M2ePro.translator.translate('Revise All'),
                type: 'popup',
                buttons: []
            }, $(component + '_revise_all_confirm_popup'));
            ReviseAllConfirmPopup.openModal();
        },

        // ---------------------------------------

        runReviseAll: function(component)
        {
            new Ajax.Request(M2ePro.url.get('runReviseAll'), {
                parameters: {component: component},
                onSuccess: function(transport) {
                    this.initReviseAllInfo(
                        true, transport.responseText.evalJSON()['start_date'],
                        null, component
                    );
                }.bind(this)
            });
        },

        initReviseAllInfo: function(inProgress, startDate, endDate, component)
        {
            $(component + '_revise_all_end').hide();
            if (inProgress) {
                $(component + '_revise_all_start').show();
                $(component + '_revise_all_start_date').update(startDate);
            } else {
                $(component + '_revise_all_start').hide();
                if (endDate) {
                    $(component + '_revise_all_end').show();
                    $(component + '_revise_all_end_date').update(endDate);
                }
            }
        }

        // ---------------------------------------
    });
});