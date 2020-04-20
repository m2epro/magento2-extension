define([
    'M2ePro/Plugin/RandomColor',
    'M2ePro/Plugin/Messages'
], function (RandomColor, MessagesObj) {

    window.LogView = Class.create(Common, {

        notificationWasAdded: false,

        processColorMapping: function () {

            if (
                !LogViewObj.notificationWasAdded
                && !_.isUndefined(M2ePro.formData.maxAllowedLogsCountExceededNotification)
            ) {
                LogViewObj.notificationWasAdded = true;
                MessagesObj.addNoticeMessage(M2ePro.formData.maxAllowedLogsCountExceededNotification);
            }

            jQuery('.data-grid tbody tr:not(.data-grid-tr-no-data)').each(function () {

                var row = jQuery(this);
                var logHash = row.find('.log-hash').text().trim();

                if (!logHash.length) {
                    return;
                }

                var color = RandomColor({
                    seed: +logHash
                });

                row.find('td:first').css({
                    borderLeftWidth: '7px',
                    borderLeftColor: '#' + color
                });
            });
        }
    });

});