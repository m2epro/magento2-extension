define([
    'M2ePro/Common'
], function() {

    window.TemplateHandler = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {
        },

        // ---------------------------------------

        checkMessages: function (id, nick, data, storeId, marketplaceId, checkAttributesAvailability, container, callback) {
            var parameters = '';

            parameters += 'id=' + encodeURIComponent(id);
            parameters += '&nick=' + encodeURIComponent(nick);
            parameters += '&store_id=' + encodeURIComponent(storeId);
            parameters += '&marketplace_id=' + encodeURIComponent(marketplaceId);
            parameters += '&check_attributes_availability=' + encodeURIComponent(checkAttributesAvailability);
            parameters += '&'+ data;

            new Ajax.Request(M2ePro.url.get('templateCheckMessages'), {
                method: 'post',
                asynchronous: true,
                parameters: parameters,
                onSuccess: function (transport) {

                    var messages = transport.responseText.evalJSON()['messages'];

                    if (messages.length == 0) {
                        $(container).innerHTML = '';
                        return;
                    }

                    $(container).innerHTML = messages;

                    if (typeof callback == 'function') {
                        callback();
                    }

                }.bind(this)
            });
        }

        // ---------------------------------------
    });

});