define([
    'M2ePro/Common'
], function () {

    window.Support = Class.create(Common, {

        initialize: function()
        {
            this.initFormValidation();

            $('more_attachments_container').hide();
        },

        // ---------------------------------------

        toggleMoreButton: function()
        {
            $('more_attachments_container').show();
        },

        // ---------------------------------------

        moreAttachments: function()
        {
            var emptyField = false;

            $$('.field-files input').each(function(obj) {
                if (obj.value == '') {
                    emptyField = true;
                }
            });

            if (emptyField) {
                return;
            }

            var newAttachment = jQuery('#more_button_container').clone();
            newAttachment.removeAttr('id');

            var newAttachmentInput = newAttachment.find('input[type=file]');
            newAttachmentInput.removeAttr('id');
            newAttachmentInput.val("");

            newAttachment.insertAfter('.field-files:last');

            $('more_attachments_container').hide();
        }

        // ---------------------------------------
    });
});