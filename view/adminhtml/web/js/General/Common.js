define([
    'underscore'
], function (_) {
    return {
        extend: function(extendBy) {
            return _.extend({}, this, extendBy);
        },
        init: function() {

        }
    };
});