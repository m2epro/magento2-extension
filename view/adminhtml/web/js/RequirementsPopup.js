define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (messagesObj) {

    window.RequirementsPopup = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            this.initErrorMessage();
        },

        initErrorMessage: function()
        {
            messagesObj.addGlobalErrorMessage(M2ePro.translator.translate(
                'System Configuration does not meet minimum requirements. Please check.'
            ));
        },

        // ---------------------------------------

        show: function()
        {
            var self = this;
            var modalDialogMessage = $('requirements_popup_content');

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('div', {id: 'requirements_popup_content'});
            }

            self.popupObj = jQuery(modalDialogMessage).modal({
                title: M2ePro.translator.translate('System Requirements'),
                type: 'popup',
                modalClass: 'width-50',
                buttons: [
                    {
                        text: M2ePro.translator.translate('Confirm and Close'),
                        class: 'action primary',
                        click: function () {
                            self.close();
                        }
                    }
                ],
                closed: function() {
                    self.close();
                }
            });

            self.popupObj.modal('openModal');
        },

        close: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('general/requirementsPopupClose'),
            {
                method: 'post',
                asynchronous : true,
                onSuccess: function()
                {
                    location.reload();
                }
            });
        }

        // ---------------------------------------
    });
});