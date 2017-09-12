define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function(modal){

    window.EbayListingViewSettingsMotors  = Class.create(Common, {

        listingId: null,
        motorsType: null,
        saveAsGroupPopupHtml: '',
        setNotePopupHtml: '',

        selectedData: {
            items: [],
            filters: [],
            groups: []
        },

        // ---------------------------------------

        initialize: function (listingId, motorsType) {
            this.listingId = listingId;
            this.motorsType = motorsType;
        },

        // ---------------------------------------

        openAddPopUp: function (listingProductsIds) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/addView'), {
                method: 'get',
                parameters: {
                    motors_type: self.motorsType
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_manage_popup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_manage_popup"></div>'});
                    $('ebay_settings_motors_manage_popup').update(transport.responseText);

                    self.addPopUp = jQuery('#ebay_settings_motors_manage_popup');

                    var instructions = $('add_custom_motors_record_instruction_container'),
                        popupType = 'slide',
                        closedAction = function () {
                            EbayListingViewSettingsGridObj.unselectAllAndReload();

                            self.selectedData = {
                                items: [],
                                filters: [],
                                groups: []
                            };
                        },
                        buttons = [];

                    if (instructions) {
                        popupType = 'popup';
                        closedAction = '';
                        buttons.push({
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'action-primary',
                            click: function () {
                                self.closeInstruction(listingProductsIds);
                            }
                        })
                    }

                    modal({
                        title: M2ePro.translator.translate('Add Compatible Vehicles'),
                        type: popupType,
                        buttons: buttons,
                        closed: closedAction
                    }, self.addPopUp);

                    self.addPopUp.modal('openModal');

                    self.addPopUp.listingProductsIds = listingProductsIds;
                }
            });
        },

        //----------------------------------

        openViewItemPopup: function (entityId, grid) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/viewItem'), {
                method: 'get',
                parameters: {
                    entity_id: entityId,
                    motors_type: self.motorsType
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_view_item');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_view_item"></div>'});
                    $('ebay_settings_motors_view_item').update(transport.responseText);

                    self.viewItemPopup = jQuery('#ebay_settings_motors_view_item');

                    modal({
                        title: M2ePro.translator.translate('View Items'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.viewItemPopup.modal('closeModal')
                            }
                        }],
                        closed: function () {
                            grid.unselectAllAndReload();
                        }
                    }, self.viewItemPopup);

                    self.viewItemPopup.modal('openModal');
                }
            });
        },

        openViewFilterPopup: function (entityId, grid) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/viewFilter'), {
                method: 'get',
                parameters: {
                    entity_id: entityId,
                    motors_type: self.motorsType
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_view_filter');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_view_filter"></div>'});
                    $('ebay_settings_motors_view_filter').update(transport.responseText);

                    self.viewFilterPopup = jQuery('#ebay_settings_motors_view_filter');

                    modal({
                        title: M2ePro.translator.translate('View Filters'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.viewFilterPopup.modal('closeModal')
                            }
                        }],
                        closed: function () {
                            grid.unselectAllAndReload();
                        }
                    }, self.viewFilterPopup);

                    self.viewFilterPopup.modal('openModal');
                }
            });
        },

        openViewGroupPopup: function (entityId, grid) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/viewGroup'), {
                method: 'get',
                parameters: {
                    entity_id: entityId,
                    motors_type: self.motorsType
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_view_group');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_view_group"></div>'});
                    $('ebay_settings_motors_view_group').update(transport.responseText);

                    self.viewGroupPopup = jQuery('#ebay_settings_motors_view_group');

                    modal({
                        title: M2ePro.translator.translate('View Groups'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.viewGroupPopup.modal('closeModal')
                            }
                        }],
                        closed: function () {
                            grid.unselectAllAndReload();
                        }
                    }, self.viewGroupPopup);

                    self.viewGroupPopup.modal('openModal');
                }
            });
        },

        // ---------------------------------------

        updateSelectedData: function () {
            var self = this,
                emptyText = $('motors_empty_text'),
                addbtn = $('motors_add'),
                overrideBtn = $('motors_override'),
                dataEl = $('selected_motors_data');

            addbtn.addClassName('disabled');
            overrideBtn.addClassName('disabled');
            emptyText.show();

            dataEl.down('.items').hide();
            if (self.selectedData.items.length > 0) {
                emptyText.hide();
                addbtn.removeClassName('disabled');
                overrideBtn.removeClassName('disabled');

                dataEl.down('.items').show();
                dataEl.down('.items .count').innerHTML = self.selectedData.items.length;
            }

            dataEl.down('.filters').hide();
            if (self.selectedData.filters.length > 0) {
                emptyText.hide();
                addbtn.removeClassName('disabled');
                overrideBtn.removeClassName('disabled');

                dataEl.down('.filters').show();
                dataEl.down('.filters .count').innerHTML = self.selectedData.filters.length;
            }

            dataEl.down('.groups').hide();
            if (self.selectedData.groups.length > 0) {
                emptyText.hide();
                addbtn.removeClassName('disabled');
                overrideBtn.removeClassName('disabled');

                dataEl.down('.groups').show();
                dataEl.down('.groups .count').innerHTML = self.selectedData.groups.length;
            }
        },

        //----------------------------------

        viewSelectedItemPopup: function () {
            var self = this;

            var containerEl = $('selected_items_popup');

            if (containerEl) {
                containerEl.remove();
            }

            $('html-body').insert({
                bottom: '<div id="selected_items_popup"></div>'
            });

            self.viewSelectedItemsPopup = jQuery('#selected_items_popup');

            modal({
                title: M2ePro.translator.translate('Selected Items'),
                type: 'popup',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    class: 'action-secondary',
                    click: function () {
                        self.viewSelectedItemsPopup.modal('closeModal');
                    }
                }]
            }, self.viewSelectedItemsPopup);

            self.viewSelectedItemsPopup.modal('openModal');

            var table = new Element('table', {
                cellspacing: '0',
                cellpadding: '0',
                class: 'data-grid data-grid-not-hovered'
            });

            table.update(
                '<thead> ' +
                    '<tr class="headings">' +
                        '<th class="data-grid-th" style="width: 75px; font-weight: bold;">' +
                            M2ePro.translator.translate('Motor Item') +
                        '</th>' +
                        '<th class="data-grid-th" style="font-weight: bold;">' +
                            M2ePro.translator.translate('Note') +
                        '</th>' +
                        '<th class="data-grid-th type-butt last" style="width: 20px;"></th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody></tbody>'
            );

            var tbody = table.down('tbody');

            self.selectedData.items.each(function (item) {
                var tr = new Element('tr'),
                    tdItem = new Element('td', {
                        style: 'text-align: center'
                    }),
                    tdNote = new Element('td', {}),
                    tdRemove = new Element('td', {});

                tdItem.innerHTML = item;
                if (EbayListingViewSettingsMotorsAddItemGridObj.savedNotes[item]) {
                    tdNote.innerHTML = EbayListingViewSettingsMotorsAddItemGridObj.savedNotes[item];
                }

                var removeLink = new Element('a', {
                    title: M2ePro.translator.translate('Remove'),
                    href: 'javascript:void(0)',
                    class: 'ebay-listing-view-icon ebay-listing-view-remove'
                });

                removeLink.observe('click', function () {
                    tr.remove();

                    var index = self.selectedData.items.indexOf(item);
                    self.selectedData.items.splice(index, 1);

                    EbayListingViewSettingsMotorsObj.updateSelectedData();

                    if (self.selectedData.items.length == 0) {
                        self.viewSelectedItemsPopup.modal('closeModal');
                    }
                }, self);

                tdRemove.insert({bottom: removeLink});

                tr.insert({bottom: tdItem});
                tr.insert({bottom: tdNote});
                tr.insert({bottom: tdRemove});

                tbody.insert({bottom: tr});
            });

            var dialogContent = $('selected_items_popup');

            dialogContent.insert({
                bottom: new Element('div', {
                    class: 'admin__data-grid-wrap admin__data-grid-wrap-static'
                }).insert({bottom: table})
            });
        },

        viewSelectedFilterPopup: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('general/modelGetAll'), {
                method: 'get',
                parameters: {
                    model: 'Ebay_Motor_Filter',
                    id_field: 'id',
                    data_field: 'title'
                },
                onSuccess: function (transport) {
                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
                        return;
                    }

                    var filters = transport.responseText.evalJSON();

                    var containerEl = $('selected_filters_popup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({
                        bottom: '<div id="selected_filters_popup"></div>'
                    });

                    self.viewSelectedFiltersPopup = jQuery('#selected_filters_popup');

                    modal({
                        title: M2ePro.translator.translate('Selected Filters'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.viewSelectedFiltersPopup.modal('closeModal')
                            }
                        }]
                    }, self.viewSelectedFiltersPopup);

                    self.viewSelectedFiltersPopup.modal('openModal');

                    var table = new Element('table', {
                        cellspacing: '0',
                        cellpadding: '0',
                        class: 'data-grid data-grid-not-hovered'
                    });

                    table.update(
                        '<thead> ' +
                            '<tr class="headings">' +
                                '<th class="data-grid-th" style="font-weight: bold;">' +
                                    M2ePro.translator.translate('Filter') +
                                '</th>' +
                                '<th class="data-grid-th type-butt last" style="width: 20px;"></th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody></tbody>'
                    );

                    var tbody = table.down('tbody');

                    filters.each(function (filter) {
                        if (self.selectedData.filters.indexOf(filter.id) == -1) {
                            return;
                        }

                        var tr = new Element('tr'),
                            tdTitle = new Element('td', {}),
                            tdRemove = new Element('td', {});

                        tdTitle.innerHTML = filter.title;

                        var removeLink = new Element('a', {
                            title: M2ePro.translator.translate('Remove'),
                            href: 'javascript:void(0)',
                            class: 'ebay-listing-view-icon ebay-listing-view-remove'
                        });

                        removeLink.observe('click', function () {
                            tr.remove();

                            var index = self.selectedData.filters.indexOf(filter.id);
                            self.selectedData.filters.splice(index, 1);

                            EbayListingViewSettingsMotorsObj.updateSelectedData();

                            if (self.selectedData.filters.length == 0) {
                                self.viewSelectedFiltersPopup.modal('closeModal');
                            }
                        }, self);

                        tdRemove.insert({bottom: removeLink});

                        tr.insert({bottom: tdTitle});
                        tr.insert({bottom: tdRemove});

                        tbody.insert({bottom: tr});
                    });

                    var dialogContent = $('selected_filters_popup');

                    dialogContent.insert({
                        bottom: new Element('div', {
                            class: 'admin__data-grid-wrap admin__data-grid-wrap-static'
                        }).insert({bottom: table})
                    });
                }
            });
        },

        viewSelectedGroupPopup: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('general/modelGetAll'), {
                method: 'get',
                parameters: {
                    model: 'Ebay_Motor_Group',
                    id_field: 'id',
                    data_field: 'title'
                },
                onSuccess: function (transport) {
                    if (!transport.responseText.isJSON()) {
                        alert(transport.responseText);
                        return;
                    }

                    var groups = transport.responseText.evalJSON();

                    var containerEl = $('selected_groups_popup');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({
                        bottom: '<div id="selected_groups_popup"></div>'
                    });

                    self.viewSelectedGroupsPopup = jQuery('#selected_groups_popup');

                    modal({
                        title: M2ePro.translator.translate('Selected Groups'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.viewSelectedGroupsPopup.modal('closeModal')
                            }
                        }]
                    }, self.viewSelectedGroupsPopup);

                    self.viewSelectedGroupsPopup.modal('openModal');

                    var table = new Element('table', {
                        cellspacing: '0',
                        cellpadding: '0',
                        class: 'data-grid data-grid-not-hovered'
                    });

                    table.update(
                        '<thead> ' +
                            '<tr class="headings">' +
                                '<th class="data-grid-th" style="font-weight: bold;">' +
                                    M2ePro.translator.translate('Filter') +
                                '</th>' +
                                '<th class="data-grid-th type-butt last" style="width: 20px;"></th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody></tbody>'
                    );

                    var tbody = table.down('tbody');

                    groups.each(function (group) {
                        if (self.selectedData.groups.indexOf(group.id) == -1) {
                            return;
                        }

                        var tr = new Element('tr'),
                            tdTitle = new Element('td', {}),
                            tdRemove = new Element('td', {});

                        tdTitle.innerHTML = group.title;

                        var removeLink = new Element('a', {
                            title: M2ePro.translator.translate('Remove'),
                            href: 'javascript:void(0)',
                            class: 'ebay-listing-view-icon ebay-listing-view-remove'
                        });

                        removeLink.observe('click', function () {
                            tr.remove();

                            var index = self.selectedData.groups.indexOf(group.id);
                            self.selectedData.groups.splice(index, 1);

                            EbayListingViewSettingsMotorsObj.updateSelectedData();

                            if (self.selectedData.groups.length == 0) {
                                self.viewSelectedGroupsPopup.modal('closeModal');
                            }
                        }, self);

                        tdRemove.insert({bottom: removeLink});

                        tr.insert({bottom: tdTitle});
                        tr.insert({bottom: tdRemove});

                        tbody.insert({bottom: tr});
                    });

                    var dialogContent = $('selected_groups_popup');

                    dialogContent.insert({
                        bottom: new Element('div', {
                            class: 'admin__data-grid-wrap admin__data-grid-wrap-static'
                        }).insert({bottom: table})
                    });
                }
            });
        },

        //----------------------------------

        updateMotorsData: function (override) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        var items = {};
                        self.selectedData.items.each(function (item) {
                            items[item] = EbayListingViewSettingsMotorsAddItemGridObj.savedNotes[item];
                        });

                        new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/updateMotorsData'), {
                            method: 'post',
                            parameters: {
                                listing_id: self.listingId,
                                'listing_products_ids[]': self.addPopUp.listingProductsIds,
                                motors_type: self.motorsType,
                                overwrite: override,
                                items: Object.toQueryString(items),
                                filters_ids: implode(',', self.selectedData.filters),
                                groups_ids: implode(',', self.selectedData.groups)
                            },
                            onSuccess: function (transport) {

                                if (transport.responseText == '0') {
                                    self.addPopUp.modal('closeModal');
                                }
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        //----------------------------------

        openAddRecordPopup: function () {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/getAddCustomItemForm'), {
                method: 'get',
                parameters: {
                    motors_type: self.motorsType
                },
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_custom_item_form');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_custom_item_form"></div>'});
                    $('ebay_settings_motors_custom_item_form').update(transport.responseText);

                    self.addRecordPopUp = jQuery('#ebay_settings_motors_custom_item_form');

                    modal({
                        title: M2ePro.translator.translate('Add Custom Compatible Vehicle'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.addRecordPopUp.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Confirm'),
                            class: 'action-primary',
                            click: function () {
                                if (!jQuery('#motors_custom_item').valid()) {
                                    return;
                                }

                                new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/saveCustomItem'), {
                                    method: 'post',
                                    parameters: $$('#ebay_settings_motors_custom_item_form form').first().serialize(true),
                                    onSuccess: function (transport) {

                                        var result = transport.responseText.evalJSON();

                                        if (!result.result) {
                                            return self.alert(result.message);
                                        }

                                        self.addRecordPopUp.modal('closeModal');
                                        EbayListingViewSettingsMotorsAddItemGridObj.unselectAllAndReload();
                                    }
                                });
                            }
                        }]
                    }, self.addRecordPopUp);

                    self.addRecordPopUp.modal('openModal');

                    self.initFormValidation('#motors_custom_item');
                }
            });
        },

        removeCustomMotorsRecord: function (motorsType, keyId) {
            var self = this;

            var index = self.selectedData.items.indexOf('' + keyId);

            if (index > -1) {
                self.selectedData.items.splice(index, 1);
            }

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/removeCustomItem'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    motors_type: motorsType,
                    key_id: keyId
                },
                onSuccess: function (transport) {

                    var result = transport.responseText.evalJSON();

                    if (!result.result) {
                        return alert(result.message);
                    }

                    self.updateSelectedData();
                    EbayListingViewSettingsMotorsAddItemGridObj.unselectAllAndReload();
                    EbayListingViewSettingsMotorsAddGroupGridObj.unselectAllAndReload();
                }
            });
        },

        // ---------------------------------------

        closeInstruction: function (listingProductsIds) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/closeInstruction'), {
                method: 'post',
                onSuccess: function (transport) {
                    self.addPopUp.modal('closeModal');
                    self.openAddPopUp(listingProductsIds);
                }
            });
        }

        // ---------------------------------------
    });

});