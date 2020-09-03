define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function (modal) {
    window.ControlPanelInspection = Class.create(Common, {
        showMetaData: function (element) {
            var content = '<div style="padding: 10px 10px; max-height: 450px; overflow: auto;">' +
                element.next().innerHTML +
                '</div>' +
                '<div style="text-align: right; padding-right: 10px; margin-top: 10px; margin-bottom: 5px;">' +
                '</div>';

            modal({
                title: 'Details',
                type: 'popup',
                modalClass: 'width-1000',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    class: 'action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }]
            }, content).openModal();

        }
    });
});
