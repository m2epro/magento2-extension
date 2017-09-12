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

        initObservers: function()
        {
            $('template_name_mode')
                .observe('change', this.templateNameModeChange)
                .simulate('change');
        },

        // ---------------------------------------

        duplicateClick: function($headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Amazon\\Template\\ShippingTemplate', 'title', 'id', ''
            );

            CommonObj.duplicateClick($headId, M2ePro.translator.translate('Add Shipping Template Policy'));
        },

        // ---------------------------------------

        templateNameModeChange: function()
        {
            $('template_name_custom_value_tr').hide();
            $('template_name_attribute').value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingTemplate::TEMPLATE_NAME_VALUE')) {
                $('template_name_custom_value_tr').show();
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_ShippingTemplate::TEMPLATE_NAME_ATTRIBUTE')) {
                CommonObj.updateHiddenValue(this, $('template_name_attribute'));
            }
        }

        // ---------------------------------------
    });

});