define([
    'jquery'
], function ($) {
    $(document).ready(function () {
        $$('.admin__collapsible-block-wrapper.opened').each(function (fieldset) {
            var toggleElement = fieldset.down('.admin__collapsible-title');
            if (toggleElement) {
                toggleElement.click();
            }
        });
    });
});
