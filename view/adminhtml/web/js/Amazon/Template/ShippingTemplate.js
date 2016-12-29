define([
    'M2ePro/Amazon/Template/Edit'
], function () {

    window.AmazonTemplateShippingTemplate = Class.create(AmazonTemplateEdit,  {

        initialize: function()
        {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Amazon\\Template\\ShippingTemplate', 'title', 'id',
                M2ePro.formData.id
            );
        },

        // ---------------------------------------

        duplicateClick: function($headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Amazon\\Template\\ShippingTemplate', 'title', 'id', ''
            );

            CommonObj.duplicateClick($headId, M2ePro.translator.translate('Add Shipping Template Policy'));
        }

        // ---------------------------------------

    });

});