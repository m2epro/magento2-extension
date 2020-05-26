define([
    'M2ePro/Grid'
], function () {
    
    window.OrderNote = Class.create(Grid, {

        // ---------------------------------------

        initialize: function (gridId) {
            this.gridId = gridId;
        },

        // ---------------------------------------

        openAddNotePopup: function (orderId) {
            new Ajax.Request(M2ePro.url.get('order/getNotePopupHtml'), {
                method: 'post',
                parameters: {
                    order_id: orderId
                },
                onSuccess: (function (transport) {
                    this.openPopup(transport.responseText);
                }).bind(this)
            });
        },

        openEditNotePopup: function (noteId) {
            new Ajax.Request(M2ePro.url.get('order/getNotePopupHtml'), {
                method: 'post',
                parameters: {
                    note_id: noteId
                },
                onSuccess: (function (transport) {
                    this.openPopup(transport.responseText);
                }).bind(this)
            });
        },

        openPopup: function (content) {
            if ($('modal_note_popup')) {
                $('modal_note_popup').remove();
            }
            var self = this;
            var modalDialogMessage = new Element('div', {
                id: 'modal_note_popup'
            });

            var popup = jQuery(modalDialogMessage).modal({
                title: M2ePro.translator.translate('Custom Note'),
                modalClass: 'width-500',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class:'action-secondary action-dismiss',
                    click: function () {
                        self.closePopup();
                    }
                },{
                    text: M2ePro.translator.translate('Save'),
                    class: 'action-primary action-accept',
                    id: 'save_popup_button',
                    click: function () {
                        self.saveNote();
                    }
                }]
            });

            popup.modal('openModal');
            modalDialogMessage.insert(content);
            modalDialogMessage.innerHTML.evalScripts();

            this.initFormValidation(popup.find('form'));
            return popup;
        },

        closePopup: function () {
            jQuery('#modal_note_popup').modal('closeModal');
            window[this.gridId + 'JsObject'].reload();

            return true;
        },

        // ---------------------------------------

        saveNote: function () {
            if (!jQuery('#order_note_popup').valid()) {
                return false;
            }

            new Ajax.Request(M2ePro.url.get('order/saveNote'), {
                method: 'post',
                parameters: $('order_note_popup').serialize(true),

                onSuccess: (function (transport) {

                    var result = transport.responseText.evalJSON()['result'];
                    if (!result) {

                        this.scrollPageToTop();
                        window.location.reload();
                        return;
                    }
                    this.closePopup();
                }).bind(this)
            });

        },

        deleteNote: function (noteId) {
            if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('order/deleteNote'), {
                method: 'post',
                parameters: {
                    note_id: noteId
                },
                onSuccess: (function (transport) {
                    var result = transport.responseText.evalJSON()['result'];
                    if (!result) {

                        this.scrollPageToTop();
                        window.location.reload();
                        return;
                    }

                    this.getGridObj().reload();
                }).bind(this)
            });
        }

        // ---------------------------------------
    });
});