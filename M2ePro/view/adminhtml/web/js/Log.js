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
            var content = '<div class="log-description-full">' +
                element.next().innerHTML +
                '</div>';

            title = title || M2ePro.translator.translate('Message');

            modal({
                title: title,
                type: 'popup',
                modalClass: 'width-800',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    class: 'action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }]
            }, content).openModal();
        }

        // ---------------------------------------
    });
});