define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Grid'
], function (modal) {

    window.EbayListingViewSettingsMotorsAddFilterGrid = Class.create(Grid, {

        filtersConditions: {},

        //----------------------------------

        initialize: function ($super, gridId) {
            $super(gridId);
        },

        //##################################

        prepareActions: function () {
            this.actions = {
                selectAction: this.selectFilters.bind(this),
                setNoteAction: this.setNote.bind(this),
                resetNoteAction: this.resetNote.bind(this),
                saveAsGroupAction: this.saveAsGroup.bind(this),
                removeFilterAction: this.removeFilter.bind(this)
            };
        },

        //##################################

        selectFilters: function () {
            var self = this,
                filters = self.getGridMassActionObj().checkedString.split(',');

            filters.each(function (filter) {

                for (var i = 0; i < EbayListingViewSettingsMotorsObj.selectedData.filters.length; i++) {
                    if (EbayListingViewSettingsMotorsObj.selectedData.filters[i] == filter) {
                        return;
                    }
                }

                EbayListingViewSettingsMotorsObj.selectedData.filters.push(filter);
            });

            self.unselectAll();
            EbayListingViewSettingsMotorsObj.updateSelectedData();
        },

        //----------------------------------

        setNote: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/getNoteForm'), {
                method: 'get',
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_note_form');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_note_form"></div>'});
                    $('ebay_settings_motors_note_form').insert({bottom: transport.responseText});

                    self.notePopup = jQuery('#ebay_settings_motors_note_form');

                    modal({
                        title: M2ePro.translator.translate('Set Note'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.notePopup.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Save'),
                            class: 'action-primary',
                            click: function () {
                                if (!self.validatePopupForm('motors_note')) {
                                    return;
                                }

                                var data = $('motors_note').serialize(true);
                                data['filters_ids'] = self.getGridMassActionObj().checkedString;

                                new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/setNoteToFilters'), {
                                    method: 'post',
                                    parameters: data,
                                    onSuccess: function (transport) {

                                        if (transport.responseText == '0') {
                                            self.unselectAllAndReload();
                                        }

                                        self.notePopup.modal('closeModal');
                                    }
                                });
                            }
                        }]
                    }, self.notePopup);

                    self.notePopup.modal('openModal');

                    self.initFormValidation('#motors_note');
                }
            });
        },

        resetNote: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/setNoteToFilters'), {
                method: 'post',
                parameters: {
                    filters_ids: self.getGridMassActionObj().checkedString,
                    note: ''
                },
                onSuccess: function (transport) {

                    if (transport.responseText == '0') {
                        self.unselectAllAndReload();
                    }

                    self.notePopup.modal('closeModal');
                }
            });
        },

        //----------------------------------

        saveAsGroup: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/getSaveAsGroupForm'), {
                method: 'get',
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_save_group');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_save_group"></div>'});
                    $('ebay_settings_motors_save_group').insert({bottom: transport.responseText});

                    self.groupPopup = jQuery('#ebay_settings_motors_save_group');

                    modal({
                        title: M2ePro.translator.translate('Save as Group'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.groupPopup.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Save'),
                            class: 'action-primary',
                            click: function () {
                                if (!self.validatePopupForm('motors_group')) {
                                    return;
                                }

                                var data = $('motors_group').serialize(true);
                                data.items = self.getGridMassActionObj().checkedString;
                                data.type = EbayListingViewSettingsMotorsObj.motorsType;
                                data.mode = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Motor_Group::MODE_FILTER');

                                new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/saveAsGroup'), {
                                    method: 'post',
                                    parameters: data,
                                    onSuccess: function (transport) {

                                        if (transport.responseText == '0') {
                                            self.unselectAll();
                                            $(self.getGridMassActionObj().select).value = '';

                                            EbayListingViewSettingsMotorsAddGroupGridObj.unselectAllAndReload();
                                        }

                                        self.groupPopup.modal('closeModal');
                                    }
                                });
                            }
                        }]
                    }, self.groupPopup);

                    self.groupPopup.modal('openModal');

                    self.initFormValidation('#motors_group');
                }
            });
        },

        //----------------------------------

        removeFilter: function () {
            var self = this,
                filters = self.getGridMassActionObj().checkedString.split(',');

            filters.each(function (filter) {

                var index = EbayListingViewSettingsMotorsObj.selectedData.filters.indexOf(filter);

                if (index > -1) {
                    EbayListingViewSettingsMotorsObj.selectedData.filters.splice(index, 1);
                }
            });

            EbayListingViewSettingsMotorsObj.updateSelectedData();

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeFilter'), {
                method: 'post',
                parameters: {
                    filters_ids: self.getGridMassActionObj().checkedString
                },
                onSuccess: function (transport) {

                    if (transport.responseText == '0') {
                        self.unselectAllAndReload();
                        EbayListingViewSettingsMotorsAddGroupGridObj.unselectAllAndReload();
                    }
                }
            });
        },

        //##################################

        showFilterResult: function (filterId) {
            jQuery('#ebayMotorAddTabs').tabs({
                active: 0
            });
            EbayListingViewSettingsMotorsAddItemGridObj.showFilterResult(this.filtersConditions[filterId]);
        },

        //##################################

        validatePopupForm: function (formId) {

            return jQuery('#' + formId).valid();

        },

        //##################################

    });

});