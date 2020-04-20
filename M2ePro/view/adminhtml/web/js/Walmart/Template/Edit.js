define([
           'M2ePro/Template/Edit'
       ], function () {

    window.WalmartTemplateEdit = Class.create(TemplateEdit, {

        // ---------------------------------------

        getComponent: function()
        {
            return 'walmart';
        },

        // ---------------------------------------

        saveAndCloseClick: function(confirmText, templateNick)
        {
            if (!this.isValidForm()) {
                return;
            }

            if (confirmText && this.showConfirmMsg) {
                this.confirm(templateNick, confirmText, this.saveFormUsingAjax);
                return;
            }

            this.saveFormUsingAjax();
        },

        saveFormUsingAjax: function ()
        {
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