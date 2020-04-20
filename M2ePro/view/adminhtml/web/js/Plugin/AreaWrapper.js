define([], function () {

    window.AreaWrapper = Class.create();
    AreaWrapper.prototype = {

        // ---------------------------------------

        initialize: function (containerId) {
            if (typeof containerId == 'undefined') {
                containerId = '';
            }

            this.containerId = containerId;
            this.containerPosition = $(this.containerId).getStyle('position');

            this.wrapperId = this.containerId + '_wrapper';

            this.makeWrapperHtml();
        },

        // ---------------------------------------

        makeWrapperHtml: function () {
            var html = '<div id="' + this.wrapperId + '" class="area_wrapper" style="display: none;">&nbsp;</div>';
            $(this.containerId).insert({'top': html});
        },

        addDivClearBothToContainer: function () {
            $(this.containerId).innerHTML += '<div style="clear: both;"></div>';
        },

        // ---------------------------------------

        lock: function () {
            $(this.containerId).setStyle({position: 'relative'});
            $(this.wrapperId).show();
        },

        unlock: function () {
            $(this.wrapperId).hide();
            $(this.containerId).setStyle({position: this.containerPosition});
        }

        // ---------------------------------------
    };
});