define([
    'M2ePro/Common'
], function () {

    OrderEditShippingAddress = Class.create(Common, {

        // ---------------------------------------

        initialize: function(countryElementId, regionContainerElementId, regionElementName)
        {
            this.countryElementId = countryElementId;
            this.regionContainerElementId = regionContainerElementId;
            this.regionElementName = regionElementName;
        },

        initObservers: function()
        {
            $('country_code')
                .observe('change', OrderEditShippingAddressObj.countryCodeChange)
                .simulate('change');
        },

        // ---------------------------------------

        countryCodeChange: function()
        {
            var self = OrderEditShippingAddressObj;

            new Ajax.Request(M2ePro.url.get('order/getCountryRegions'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    country: $(self.countryElementId).value
                },
                onSuccess: function(transport) {
                    self.renderRegions(transport.responseText.evalJSON(true));
                }
            });
        },

        renderRegions: function(data)
        {
            var self = OrderEditShippingAddressObj,
                regionContainer = $(self.regionContainerElementId).select('.control')[0],
                html = '';

            if (data.length == 0) {
                html = '<input type="text" name="%name%" class="input-text admin__control-text" value="%value%" />'
                    .replace(/%name%/, self.regionElementName)
                    .replace(/%value%/, M2ePro.formData.region);
            } else {
                html += '<select class="admin__control-select" name="'+self.regionElementName+'">';
                data.each(function(item) {
                    var selected = (item.value == M2ePro.formData.region) ? 'selected="selected"' : '';
                    html += '<option value="'+item.value+'" '+selected+'>'+item.label+'</option>';
                });
                html += '</select>';
            }

            regionContainer.innerHTML = html;
        }

        // ---------------------------------------
    });
});