define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function (modal) {

    window.Log = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {},

        // ---------------------------------------

        showFullText: function(element, title)
        {
            var content = '<div style="padding: 0 10px; max-height: 300px;">' +
                element.next().innerHTML +
                '</div>';

            title = title || M2ePro.translator.translate('Description');

            modal({
                title: title,
                type: 'popup',
                modalClass: 'width-800',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    click: function () {
                        this.closeModal();
                    }
                }]
            }, content).openModal();
        }

        // ---------------------------------------
    });
});