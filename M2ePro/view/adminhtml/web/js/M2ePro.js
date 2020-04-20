define([
    'jquery',
    'M2ePro/Url',
    'M2ePro/Php',
    'M2ePro/Translator',
    'M2ePro/Common',
    'prototype',
    'M2ePro/Plugin/BlockNotice',
    'M2ePro/Plugin/Prototype/Event.Simulate',
    'M2ePro/Plugin/Fieldset',
    'M2ePro/Plugin/Validator',
    'M2ePro/General/PhpFunctions',
    'mage/loader_old'
], function(jQuery, Url, Php, Translator) {

    jQuery('body').loader();

    Ajax.Responders.register({
        onException: function(event, error) {
            console.error(error);
        }
    });

    return {
        url: Url,
        php: Php,
        translator: Translator
    };

});
