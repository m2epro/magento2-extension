define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (MessageObj) {

    window.AmazonOrder = Class.create(Common, {

        // ---------------------------------------

        resendInvoice: function(orderId, documentType)
        {
            new Ajax.Request(M2ePro.url.get('amazon_order/resendInvoice'), {
                method: 'post',
                parameters: {
                    order_id: orderId,
                    document_type: documentType
                },
                onSuccess: function(transport) {
                    var response = transport.responseText.evalJSON();

                    MessageObj.clear();
                    MessageObj['add' + response.msg.type[0].toUpperCase() + response.msg.type.slice(1)](response.msg.text);
                }
            });
        },

        // ---------------------------------------

    });

});
