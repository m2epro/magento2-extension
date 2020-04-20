define([
    'jquery',
    'M2ePro/Common'
], function (jQuery) {
    window.EbayTemplatePayment = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-validate-payment-methods', function(value) {

                if ($('pay_pal_mode').checked) {
                    return true;
                }

                return $$('input[name="payment[services][]"]').any(function(o) {
                    return o.checked;
                });
            }, M2ePro.translator.translate('Payment method should be specified.'));

            jQuery.validator.addMethod('M2ePro-validate-ebay-payment-email', function(value, el) {

                var advice = Validation.getAdvice('M2ePro-validate-ebay-payment-email',el);
                advice && advice.remove();

                if (!Validation.get('M2ePro-required-when-visible').test(value,el)) {
                    this.error = Validation.get('M2ePro-required-when-visible').error;
                    return false;
                }

                this.error = Validation.get('validate-email').error;
                return Validation.get('validate-email').test(value,el);
            }, M2ePro.translator.translate('Email is not valid.'));
        },

        // ---------------------------------------

        initObservers: function()
        {
            jQuery('#pay_pal_mode')
                .on('change', EbayTemplatePaymentObj.payPalModeChange)
                .trigger('change');
            jQuery('#pay_pal_immediate_payment')
                .on('change', EbayTemplatePaymentObj.immediatePaymentChange)
                .trigger('change');
        },

        // ---------------------------------------

        payPalModeChange: function()
        {
            if (this.checked) {
                $('pay_pal_email_address_container').show();
                $('pay_pal_immediate_payment_container').show();
            } else {
                $('pay_pal_email_address').setValue('');
                $('pay_pal_email_address_container').hide();
                $('pay_pal_immediate_payment_container').hide();
                $('pay_pal_immediate_payment').checked = false;
                jQuery('#pay_pal_immediate_payment').trigger('change');
            }
        },

        // ---------------------------------------

        immediatePaymentChange: function()
        {
            var wrapper = $('magento_block_ebay_template_payment_form_data_additional_service');
            if (this.checked) {
                wrapper.hide();

                $$('input.additional-payment-service').each(function(payment) {
                    payment.checked = false;
                });
            } else {
                wrapper && wrapper.show();
            }
        }

        // ---------------------------------------
    });
});