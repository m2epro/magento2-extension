define([
    'M2ePro/Template/Edit'
], function () {

    window.AmazonTemplateEdit = Class.create(TemplateEdit, {

        // ---------------------------------------

        getComponent: function()
        {
            return 'amazon';
        },

        // ---------------------------------------

        saveAndCloseClick: function()
        {
            if (!this.isValidForm()) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('formSubmit'), {
                method: 'post',
                parameters: Form.serialize($('edit_form')),
                onSuccess: function(transport) {
                    var result = transport.responseText.evalJSON();

                    if (result.status) {
                        window.close();
                    } else {
                        console.error('Policy Saving Error');
                    }
                }
            });
        }

        // ---------------------------------------
    });
});