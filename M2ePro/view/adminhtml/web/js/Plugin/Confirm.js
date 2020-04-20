define([
    'jquery',
    'underscore',
    'jquery/ui',
    'Magento_Ui/js/modal/confirm'
], function ($, _) {
    'use strict';

    $.widget('mage.m2e-confirm', $.mage.confirm);

    return function (config) {

        config = _.extend({
            title: M2ePro.translator.translate('Confirmation'),
            content: M2ePro.translator.translate('Are you sure?'),
            buttons: [{
                text: M2ePro.translator.translate('Cancel'),
                class: 'action-secondary action-dismiss',
                click: function (event) {
                    this.closeModal(event);
                }
            }, {
                text: M2ePro.translator.translate('Confirm'),
                class: 'action-primary action-accept',
                click: function (event) {
                    this.closeModal(event, true);
                }
            }]
        }, config);

        return $('<div></div>').html(config.content).confirm(config);
    };
});
