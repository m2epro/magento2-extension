define([
    'M2ePro/Grid'
], function () {

    window.EbayListingViewSettingsMotorsViewFilterGrid = Class.create(Grid, {

        entityId: '',
        //----------------------------------

        initialize: function ($super, gridId, entityId) {
            $super(gridId);

            this.entityId = entityId;
        },

        //##################################

        prepareActions: function () {
            this.actions = {
                removeFilterAction: this.removeFilter.bind(this)
            };
        },

        //##################################

        removeFilter: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeFilterFromProduct'), {
                method: 'post',
                parameters: {
                    filters_ids: self.getGridMassActionObj().checkedString,
                    entity_id: self.entityId,
                    motors_type: EbayListingViewSettingsMotorsObj.motorsType
                },
                onSuccess: function (transport) {

                    if (transport.responseText == '0') {
                        self.unselectAllAndReload();
                    }
                }
            });
        },

        //##################################

    });

});