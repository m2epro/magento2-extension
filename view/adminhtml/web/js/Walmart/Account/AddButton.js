define([
    'Magento_Ui/js/modal/modal',
    'jquery',
    'M2ePro/Walmart/Account'
], function (modal, $) {
    'use strict';

    return function (config) {
        const addAccountPopup = $('.walmart-account-ca-popup');

        $('#add-account-ca').on('click', function () {
            modal({
                type: 'popup',
                buttons: []
            }, addAccountPopup);

            addAccountPopup.modal('openModal');

            new WalmartAccount().initTokenValidation(config.checkAuthUrl);

            const popup = $('#account_credentials');
            popup.validate({
                onkeyup: false,
                onclick: false,
                onfocusout: false
            });

            $('body').off('submit', '#account_credentials').on('submit', '#account_credentials', function (e) {
                if (!$(this).valid()) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    };
});

