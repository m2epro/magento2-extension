define([
    'M2ePro/Common'
], function () {

    window.ControlPanel = Class.create(Common, {

        controlPanelUrl: null,

        // ---------------------------------------

        initialize: function()
        {
            var self = this;

            var cmdKeys = [67, 79, 78, 84, 82, 79, 76, 80, 65, 78, 69, 76];
            var cmdPressedKeys = [];

            window.document.observe('keyup', function(event) {

                if (cmdPressedKeys.length < cmdKeys.length) {
                    if (cmdKeys[cmdPressedKeys.length] == event.keyCode) {
                        cmdPressedKeys.push(event.keyCode);
                    } else {
                        cmdPressedKeys = [];
                    }
                }

                if (cmdPressedKeys.length == cmdKeys.length) {

                    cmdPressedKeys = [];

                    window.open(self.controlPanelUrl);
                }
            });
        },

        // ---------------------------------------

        setControlPanelUrl: function(controlPanelUrl) {
            this.controlPanelUrl = controlPanelUrl;
        }

        // ---------------------------------------
    });
});