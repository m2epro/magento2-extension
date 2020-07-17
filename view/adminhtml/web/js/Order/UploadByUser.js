define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages'
], function (modal, MessageObj) {

    window.UploadByUser = Class.create(Common, {

        component: null,
        gridId: null,

        messageManager: null,

        //---------------------------------------

        initialize: function(component, gridId)
        {
            this.component = component;
            this.gridId = gridId;

            this.messageManager = MessageObj;
            this.messageManager.setContainer('#uploadByUser_messages');
        },

        //---------------------------------------

        openPopup: function()
        {
            new Ajax.Request(M2ePro.url.get('order_uploadByUser/getPopupHtml'), {
                method: 'post',
                parameters: {
                    component: this.component
                },
                onSuccess: function(transport) {

                    if (!$('orders_upload_by_user_modal')) {
                        $('html-body').insert({ bottom: '<div id="orders_upload_by_user_modal"></div>' });
                    }

                    var modalBlock = $('orders_upload_by_user_modal');
                    modalBlock.update(transport.responseText);

                    var popup = jQuery(modalBlock).modal({
                        title: M2ePro.translator.translate('Order Reimport'),
                        type: 'popup',
                        modalClass: 'width-100',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                this.closePopup();
                            }.bind(this)
                        }]
                    });

                    popup.modal('openModal');

                }.bind(this)
            });
        },

        closePopup: function()
        {
            jQuery('#orders_upload_by_user_modal').modal('closeModal');
        },

        //----------------------------------------

        reloadGrid: function()
        {
            window[this.gridId + 'JsObject'].reload();
        },

        //----------------------------------------

        resetUpload: function(accountId)
        {
            new Ajax.Request(M2ePro.url.get('order_uploadByUser/reset'), {
                method: 'post',
                parameters: {
                    component  : this.component,
                    account_id : accountId
                },
                onSuccess: function(transport) {
                    var json = this.processJsonResponse(transport.responseText);
                    if (json === false) {
                        return;
                    }

                    this.reloadGrid();

                    if (json.result) {
                        this.messageManager.addSuccess(M2ePro.translator.translate('Order importing is canceled.'));
                    }
                }.bind(this)
            });
        },

        configureUpload: function(accountId)
        {
            var fromId = accountId + '_from_date',
                toId   = accountId + '_to_date';

            this.initFormValidation('#' + fromId + '_form');
            this.initFormValidation('#' + toId + '_form');
            if (!jQuery('#' + fromId + '_form').valid() ||
                !jQuery('#' + toId + '_form').valid()
            ) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('order_uploadByUser/configure'), {
                method: 'post',
                parameters: {
                    component  : this.component,
                    account_id : accountId,
                    from_date  : $(fromId).value,
                    to_date    : $(toId).value
                },
                onSuccess: function(transport) {
                    var json = this.processJsonResponse(transport.responseText);
                    if (json === false) {
                        return;
                    }

                    this.reloadGrid();

                    if (json.result) {
                        this.messageManager.addSuccess(M2ePro.translator.translate('Order importing in progress.'));
                    }
                }.bind(this)
            });
        },

        // ---------------------------------------

        processJsonResponse: function(responseText)
        {
            if (!responseText.isJSON()) {
                alert(responseText);
                return false;
            }

            var response = responseText.evalJSON();
            if (typeof response.result === 'undefined') {
                alert('Invalid response.');
                return false;
            }

            this.messageManager.clearAll();
            if (typeof response.messages !== 'undefined') {
                response.messages.each(function(msg) {
                    this.messageManager['add' + msg.type[0].toUpperCase() + msg.type.slice(1)](msg.text);
                }.bind(this));
            }

            return response;
        }

        // ---------------------------------------
    });
});