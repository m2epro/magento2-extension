define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Grid'
], function (modal, MessageObj) {

    window.EbayAccountGrid = Class.create(Grid, {

        // ---------------------------------------

        initialize: function($super,gridId) {
            this.messageObj = Object.create(MessageObj);
            this.messageObj.setContainer('#account_feedback_action_messages_container');

            $super(gridId);
        },

        // ---------------------------------------

        prepareActions: function()
        {
            jQuery.validator.addMethod('M2ePro-validate-feedback-response-max-length', function(value, el) {

                if (jQuery.validator.methods['M2ePro-required-when-visible'](null, el)) {
                    return true;
                }

                return value.length >= 2 && value.length <= 80;

            }, M2ePro.translator.translate('Should be between 2 and 80 characters long.'));
        },

        // ---------------------------------------

        openAccountFeedbackPopup: function(id)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_account_feedback/getPopup'), {
                method: 'GET',
                parameters: {
                    id: id
                },
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    var container = $('edit_feedback_popup_container');

                    if (container) {
                        container.remove();
                    }

                    $('html-body').insert({
                        bottom: '<div id="edit_feedback_popup_container">' + response.html + '</div>'
                    });

                    self.feedbackPopup = jQuery('#edit_feedback_popup_container');

                    modal({
                        title: response.title,
                        type: 'slide',
                        buttons: []
                    }, self.feedbackPopup);

                    self.feedbackPopup.modal('openModal');
                }
            });
        },

        // ---------------------------------------

        openSendResponsePopup: function(el, feedbackId, transactionId, itemId)
        {
            var self = this;

            self.messageObj.clear();

            new Ajax.Request(M2ePro.url.get('ebay_account_feedback/getSendResponseForm'), {
                method: 'GET',
                parameters: {
                    feedback_Id: feedbackId
                },
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    var container = $('send_response_popup_container');

                    if (container) {
                        container.remove();
                    }

                    $('html-body').insert({
                        bottom: '<div id="send_response_popup_container"></div>'
                    });

                    $('send_response_popup_container').update(response.html);

                    self.initFormValidation('#send_response_form');

                    self.sendResponsePopup = jQuery('#send_response_popup_container');

                    modal({
                        title: response.title,
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.sendResponsePopup.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Send'),
                            class: 'action-primary',
                            click: function () {
                                if (!jQuery('#send_response_form').valid()) {
                                    return false;
                                }

                                var data = $('send_response_form').serialize(true);

                                new Ajax.Request(M2ePro.url.get('ebay_account_feedback/sendResponse'), {
                                    parameters: {
                                        feedback_id: data.feedback_id,
                                        feedback_text: (data.feedback_template_type == 'custom') ?
                                            data.feedback_text :
                                            data.feedback_template
                                    },
                                    onSuccess: function(transport) {
                                        var response = transport.responseText.evalJSON();

                                        self.messageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.text);

                                        self.sendResponsePopup.modal('closeModal');
                                        window['ebayFeedbackGridJsObject'].reload();
                                    }
                                });
                            }
                        }]
                    }, self.sendResponsePopup);

                    self.sendResponsePopup.modal('openModal');
                }
            });
        },

        // ---------------------------------------
    });

});