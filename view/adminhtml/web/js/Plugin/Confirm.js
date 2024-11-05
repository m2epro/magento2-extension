define([
    'jquery',
    'underscore',
    'mage/translate',
    'jquery/ui',
    'Magento_Ui/js/modal/confirm'
], function ($, _, $t) {
    'use strict';

    $.widget('mage.m2e-confirm', $.mage.confirm);

    return function (config) {

        config = _.extend({
            title: $t('Confirmation'),
            content: $t('Are you sure?'),
            buttons: [{
                text: $t('Cancel'),
                class: 'action-secondary action-dismiss',
                click: function (event) {
                    this.closeModal(event);
                }
            }, {
                text: $t('Confirm'),
                class: 'action-primary action-accept',
                click: function (event) {
                    this.closeModal(event, true);
                }
            }]
        }, config);

        return $('<div></div>').html(config.content).confirm(config);
    };
});
