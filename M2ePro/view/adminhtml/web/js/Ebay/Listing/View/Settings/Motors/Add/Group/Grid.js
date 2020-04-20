define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Grid'
], function (modal) {

    window.EbayListingViewSettingsMotorsAddGroupGrid = Class.create(Grid, {

        //----------------------------------

        initialize: function ($super, gridId) {
            $super(gridId);
        },

        //##################################

        prepareActions: function () {
            this.actions = {
                selectAction: this.selectGroups.bind(this),
                removeGroupAction: this.removeGroup.bind(this)
            };
        },

        //##################################

        selectGroups: function () {
            var self = this,
                groups = self.getGridMassActionObj().checkedString.split(',');

            groups.each(function (group) {

                for (var i = 0; i < EbayListingViewSettingsMotorsObj.selectedData.groups.length; i++) {
                    if (EbayListingViewSettingsMotorsObj.selectedData.groups[i] == group) {
                        return;
                    }
                }

                EbayListingViewSettingsMotorsObj.selectedData.groups.push(group);
            });

            self.unselectAll();
            EbayListingViewSettingsMotorsObj.updateSelectedData();
        },

        //----------------------------------

        removeGroup: function () {
            var self = this,
                groups = self.getGridMassActionObj().checkedString.split(',');

            groups.each(function (group) {
                var index = EbayListingViewSettingsMotorsObj.selectedData.groups.indexOf(group);

                if (index > -1) {
                    EbayListingViewSettingsMotorsObj.selectedData.groups.splice(index, 1);
                }
            });

            EbayListingViewSettingsMotorsObj.updateSelectedData();

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeGroup'), {
                method: 'post',
                parameters: {
                    groups_ids: self.getGridMassActionObj().checkedString
                },
                onSuccess: function (transport) {

                    if (transport.responseText == '0') {
                        self.unselectAllAndReload();
                    }
                }
            });
        },

        //##################################

        viewGroupContentPopup: function (groupId, title) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/getGroupContentView'), {
                method: 'get',
                parameters: {
                    group_id: groupId
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_group_content');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_group_content"></div>'});
                    $('ebay_settings_motors_group_content').insert({bottom: transport.responseText});

                    self.goupContentPopup = jQuery('#ebay_settings_motors_group_content');

                    modal({
                        title: title,
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.goupContentPopup.modal('closeModal');
                            }
                        }],
                        closed: function() {
                            EbayListingViewSettingsMotorsAddGroupGridObj.unselectAllAndReload();
                        }
                    }, self.goupContentPopup);

                    self.goupContentPopup.modal('openModal');
                }
            });
        },

        removeItemFromGroup: function (el, itemId, groupId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeItemFromGroup'), {
                method: 'post',
                parameters: {
                    items_ids: itemId,
                    group_id: groupId
                },
                onSuccess: function (transport) {

                    if (transport.responseText == '0') {
                        if ($(el).up('tbody').select('tr').length == 1) {
                            var index = EbayListingViewSettingsMotorsObj.selectedData.groups.indexOf('' + groupId);

                            if (index > -1) {
                                EbayListingViewSettingsMotorsObj.selectedData.groups.splice(index, 1);
                                EbayListingViewSettingsMotorsObj.updateSelectedData();
                            }

                            self.goupContentPopup.modal('closeModal');
                            return;
                        }

                        $(el).up('tr').remove();
                    }
                }
            });
        },

        removeFilterFromGroup: function (el, filterId, groupId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeFilterFromGroup'), {
                method: 'post',
                parameters: {
                    filters_ids: filterId,
                    group_id: groupId
                },
                onSuccess: function (transport) {

                    if (transport.responseText == '0') {
                        if ($(el).up('tbody').select('tr').length == 1) {
                            var index = EbayListingViewSettingsMotorsObj.selectedData.groups.indexOf('' + groupId);

                            if (index > -1) {
                                EbayListingViewSettingsMotorsObj.selectedData.groups.splice(index, 1);
                                EbayListingViewSettingsMotorsObj.updateSelectedData();
                            }

                            self.goupContentPopup.modal('closeModal');
                            return;
                        }

                        $(el).up('tr').remove();
                    }
                }
            });
        }

        //##################################

    });

});