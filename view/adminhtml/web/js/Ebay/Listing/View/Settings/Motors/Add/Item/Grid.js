define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Grid'
], function (modal) {

    window.EbayListingViewSettingsMotorsAddItemGrid = Class.create(Grid, {

        savedNotes: {},

        //----------------------------------

        initialize: function ($super, gridId) {
            $super(gridId);

            this.savedNotes = {};
        },

        //##################################

        prepareActions: function () {
            this.actions = {
                selectAction: this.selectItems.bind(this),
                setNoteAction: this.setNote.bind(this),
                resetNoteAction: this.resetNote.bind(this),
                saveAsGroupAction: this.saveAsGroup.bind(this)
            };
        },

        afterInitPage: function () {
            var self = this;

            Grid.prototype.afterInitPage.call(this);

            $(self.gridId).down('.data-grid-filters').on('change', function (e) {

                self.checkFilterValues();

            });

            $(self.gridId).down('.data-grid-filters').on('keyup', function (e) {

                self.checkFilterValues();

            });

            self.checkFilterValues();

            $H(self.savedNotes).each(function (note) {

                var noteEl = $('note_' + note.key);

                if (noteEl && note.value != '') {
                    noteEl.show();
                    noteEl.down('.note-view').innerHTML = note.value;
                }
            });

            var select = $(self.getGridMassActionObj().containerId + '-mass-select');
            self.bindEventAtFirstPosition(select, 'change',  self.massactionOnChange.bind(self));
        },

        checkFilterValues: function () {
            var self = this;

            $('save_filter_btn').addClassName('disabled');

            $(self.gridId).down('.data-grid-filters').select('select', 'input').each(function (el) {
                if (el.name == 'massaction') {
                    return;
                }

                if (el.value != '') {
                    $('save_filter_btn').removeClassName('disabled');
                    throw $break;
                }
            });
        },

        //##################################

        selectItems: function () {
            var self = this,
                items = self.getGridMassActionObj().checkedString.split(',');

            items.each(function (item) {

                for (var i = 0; i < EbayListingViewSettingsMotorsObj.selectedData.items.length; i++) {
                    if (EbayListingViewSettingsMotorsObj.selectedData.items[i] == item) {
                        return;
                    }
                }

                EbayListingViewSettingsMotorsObj.selectedData.items.push(item);
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

                                var note = $('motors_note').down('[name=note]').value;

                                note = note.trim();

                                self.getGridMassActionObj().getCheckedValues().split(',').each(function (id) {

                                    self.savedNotes[id] = note;

                                    var noteEl = $('note_' + id);

                                    if (noteEl) {
                                        noteEl.hide();

                                        if (note != '') {
                                            noteEl.show();
                                            noteEl.down('.note-view').innerHTML = note;
                                        }
                                    }
                                });

                                $(self.getGridMassActionObj().select).value = '';
                                self.notePopup.modal('closeModal');
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

            self.getGridMassActionObj().getCheckedValues().split(',').each(function (id) {

                self.savedNotes[id] = '';

                var noteEl = $('note_' + id);

                if (noteEl) {
                    noteEl.hide();
                    noteEl.down('.note-view').innerHTML = '';
                }
            });

            $(self.getGridMassActionObj().select).value = '';
            self.unselectAll();
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
                                data.items = self.getGridMassActionObj().checkedString.split(',');
                                data.type = EbayListingViewSettingsMotorsObj.motorsType;
                                data.mode = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Motor_Group::MODE_ITEM');

                                var items = {};
                                data.items.each(function (item) {
                                    items[item] = self.savedNotes[item];
                                });

                                data.items = Object.toQueryString(items);

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

        //##################################

        saveFilter: function () {
            var self = this;

            if ($('save_filter_btn').hasClassName('disabled')) {
                return;
            }

            new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/getSaveFilterForm'), {
                method: 'get',
                onSuccess: function (transport) {

                    var containerEl = $('ebay_settings_motors_save_filter');

                    if (containerEl) {
                        containerEl.remove();
                    }

                    $('html-body').insert({bottom: '<div id="ebay_settings_motors_save_filter"></div>'});
                    $('ebay_settings_motors_save_filter').insert({bottom: transport.responseText});

                    self.filterPopup = jQuery('#ebay_settings_motors_save_filter');

                    modal({
                        title: M2ePro.translator.translate('Save Filter'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Close'),
                            class: 'action-secondary',
                            click: function () {
                                self.filterPopup.modal('closeModal');
                            }
                        },{
                            text: M2ePro.translator.translate('Save'),
                            class: 'action-primary',
                            click: function () {
                                if (!self.validatePopupForm('motors_filter')) {
                                    return;
                                }

                                var data = $('motors_filter').serialize(true);
                                data.conditions = Form.serialize($(self.gridId).down('.data-grid-filters'));
                                data.type = EbayListingViewSettingsMotorsObj.motorsType;

                                new Ajax.Request(M2ePro.url.get('ebay_listing_settings_motors/saveFilter'), {
                                    method: 'post',
                                    parameters: data,
                                    onSuccess: function (transport) {

                                        if (transport.responseText == '0') {
                                            $(self.getGridMassActionObj().select).value = '';

                                            EbayListingViewSettingsMotorsAddFilterGridObj.unselectAllAndReload();
                                        }

                                        self.filterPopup.modal('closeModal');
                                    }
                                });
                            }
                        }]
                    }, self.filterPopup);

                    self.filterPopup.modal('openModal');

                    var conditionsEl = $('ebay_settings_motors_save_filter').down('.filter_conditions');
                    var conditionsData = {};
                    $(self.gridId).down('.data-grid-filters').select('select', 'input').each(function (el) {

                        if (el.name == 'massaction') {
                            return;
                        }

                        if (el.value != '') {
                            var li = new Element('li'),
                                valueText = '',
                                valueName = el.name.capitalize().replace('_', ' ');

                            if (el.name == 'product_type') {
                                valueText = el[el.selectedIndex].text;
                                valueName = M2ePro.translator.translate('Type');
                            } else {
                                valueText = el.value;
                            }

                            if (el.name == 'epid') {
                                valueName = M2ePro.translator.translate('ePID');
                            }

                            if (el.name == 'ktype') {
                                valueName = M2ePro.translator.translate('kType');
                            }

                            if (el.name == 'body_style') {
                                valueName = M2ePro.translator.translate('Body Style');
                            }

                            if (el.name == 'year[from]') {
                                valueName = M2ePro.translator.translate('Year From');
                            }

                            if (el.name == 'year[to]') {
                                valueName = M2ePro.translator.translate('Year To');
                            }

                            li.update('<b>' + valueName + '</b>: ' + valueText);

                            conditionsData[el.name] = el.value;

                            conditionsEl.insert({bottom: li});
                        }
                    });

                    self.initFormValidation('#motors_filter');
                }
            });
        },

        validatePopupForm: function (formId) {

            return jQuery('#' + formId).valid();

        },

        //##################################

        showFilterResult: function (comnditions) {
            var self = this;

            $(self.gridId).down('.data-grid-filters').select('input', 'select').each(function (el) {
                el.value = '';
            });

            $H(comnditions).each(function (item) {
                $(self.gridId).down('.data-grid-filters').select('[name^=' + item.key + ']').each(function (el) {
                    if (item.key != 'year') {
                        el.value = item.value;
                        return null;
                    }

                    if (typeof item.value == 'string') {
                        el.value = item.value;
                        return null;
                    }

                    $(self.gridId).down('.data-grid-filters').down('[name=year[from]]').value = item.value.from;
                    $(self.gridId).down('.data-grid-filters').down('[name=year[to]]').value = item.value.to;

                });
            });

            self.getGridObj().doFilter();
        },

        //##################################

        massactionOnChange: function(event)
        {
            if (event.target.value != 'selectAll'){
                return true;
            }

            var gridIds = this.getGridMassActionObj().getGridIds().split(',');
            if (gridIds.length < M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Motors::MAX_ITEMS_COUNT_FOR_ATTRIBUTE')) {
                return true;
            }

            this.alert(M2ePro.translator.translate('It is impossible to select all the items.'));

            event.stopImmediatePropagation();
            event.target.value = '';
        }

        //##################################
    });
});