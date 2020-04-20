define(['M2ePro/General/Common', 'underscore'], function(Common, _) {

    return Common.extend({
        constants: {},

        add: function(constants)
        {
            _.extend(this.constants, constants);
        },

        constant: function(name)
        {
            var nameParts = name.split(/::/);
            var className = nameParts[0];

            if (className.indexOf('_') > -1) {
                className = className.replace(/_/g, '\\');
                name = '\\' + className + '::' + nameParts[1];
            }

            if (typeof this.constants[name] == 'undefined') {
                alert('Constant not found : "' + name + '"');
                throw new Error('Constant not found : "' + name + '"');
            }

            return this.constants[name];
        }
    });

});