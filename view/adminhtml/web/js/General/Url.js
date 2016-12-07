define(['M2ePro/General/Common', 'underscore'], function(Common, _) {

    return Common.extend({
        urls: {},

        add: function(urls)
        {
            _.extend(this.urls, urls);
        },

        get: function(route, params)
        {
            params = params || {};

            if (!this.urls[route]) {
                alert('Route "' + route +'" not found');
                throw new Error('Route "' + route +'" not found');
            }

            var returnUrl = this.urls[route];

            for (var key in params) {
                if (!params.hasOwnProperty(key)) {
                    continue
                }
                returnUrl += key + '/' + params[key] + '/';
            }

            return returnUrl;
        }
    });

});