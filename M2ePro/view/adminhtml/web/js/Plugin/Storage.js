define([
    'jquery',
    'underscore',
    'jquery/jquery-storageapi'
], function (jQuery, _) {
    var M2EPRO_STORAGE_KEY = 'm2epro_data',
        _storage = jQuery.localStorage;

    function initStorage() {
        var data = _storage.get(M2EPRO_STORAGE_KEY);

        if (!_.isNull(data) && _.isObject(data)) {
            return data;
        }

        return _storage.set(M2EPRO_STORAGE_KEY, {});
    }

    return {

        set: function(key, value) {
            var data = initStorage();

            data[key] = value;
            return _storage.set(M2EPRO_STORAGE_KEY, data);
        },

        get: function (key) {
            var data = initStorage();

            if (_.isUndefined(data[key])) {
                return;
            }

            return data[key];
        },

        remove: function(key) {
            var data = initStorage();

            if (_.isUndefined(data[key])) {
                return false
            }

            delete data[key];
            _storage.set(M2EPRO_STORAGE_KEY, data);
            return true;
        },

        removeAllByPrefix: function(prefix) {
            var data = initStorage();

            _.each(data, function(value, key) {
                if (key.indexOf(prefix) === -1) {
                    return;
                }

                delete data[key];
            });

            _storage.set(M2EPRO_STORAGE_KEY, data);
        },

        removeAllByPostfix: function(postfix) {
            var data = initStorage();

            _.each(data, function(value, key) {
                if (key.indexOf(postfix) === -1) {
                    return;
                }

                delete data[key];
            });

            _storage.set(M2EPRO_STORAGE_KEY, data);
        },

        removeAll: function () {
            _storage.set(M2EPRO_STORAGE_KEY, {});
            return true;
        }
    };
});