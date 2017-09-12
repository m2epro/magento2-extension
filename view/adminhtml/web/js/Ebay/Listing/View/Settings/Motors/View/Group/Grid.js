define([
    'M2ePro/Grid'
], function () {

    window.EbayListingViewSettingsMotorsViewGroupGrid = Class.create(Grid, {

        //----------------------------------

        initialize: function ($super, gridId, entityId) {
            $super(gridId);

            this.entityId = entityId;
        },

        //##################################

        prepareActions: function () {
            this.actions = {
                removeGroupAction: this.removeGroup.bind(this)
            };
        },

        //##################################

        removeGroup: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeGroupFromListingProduct'), {
                method: 'post',
                parameters: {
                    groups_ids: self.getGridMassActionObj().checkedString,
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