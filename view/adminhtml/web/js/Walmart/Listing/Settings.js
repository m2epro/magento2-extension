define([
    'M2ePro/Common'
], function () {

    window.WalmartListingSettings = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {
        },

        // ---------------------------------------

        saveClick: function (url, skipValidation) {

            if (typeof skipValidation == 'undefined' && !this.isValidForm()) {
                return;
            }

            if (typeof categories_selected_items != 'undefined') {
                array_unique(categories_selected_items);

                var selectedCategories = implode(',', categories_selected_items);

                $('selected_categories').value = selectedCategories;
            }

            if (typeof url == 'undefined' || url == '') {
                url = M2ePro.url.formSubmit + 'back/' + base64_encode('list') + '/';
            }

            Common.prototype.saveClick.call(this, url);
        },

        reloadSellingFormatTemplates: function () {
            WalmartListingSettingsObj.reload(M2ePro.url.get('getSellingFormatTemplates')  + 'marketplace_id/' + $('marketplace_id').value, 'template_selling_format_id');
        },

        reloadDescriptionTemplates: function () {
            WalmartListingSettingsObj.reload(M2ePro.url.get('getDescriptionTemplates'), 'template_description_id');
        },

        reloadSynchronizationTemplates: function () {
            WalmartListingSettingsObj.reload(M2ePro.url.get('getSynchronizationTemplates'), 'template_synchronization_id');
        },

        // ---------------------------------------

        selling_format_template_id_simulate_change: function () {
            var intervalRestartLimit = 20;
            var intervalRestartCount = 0;

            var intervalId = setInterval(function simulateSellingFormatTemplateChange() {
                intervalRestartCount++;

                if (intervalRestartCount >= intervalRestartLimit || Ajax.activeRequestCount == 0) {
                    $('template_selling_format_id').value && $('template_selling_format_id').simulate('change');

                    clearInterval(intervalId);
                }
            }, 250);
        },

        selling_format_template_id_change: function () {
            WalmartListingSettingsObj.checkMessages();
            WalmartListingSettingsObj.hideEmptyOption(this);
        },

        // ---------------------------------------

        checkMessages: function () {
            if ($('store_id').value == '' ||
                $('marketplace_id').value == '' ||
                $('template_selling_format_id').value == '') {
                return;
            }

            var id = $('template_selling_format_id').value,
                nick = 'selling_format',
                storeId = $('store_id').value,
                marketplaceId = $('marketplace_id').value,
                checkAttributesAvailability = false,
                container = 'template_selling_format_messages',
                callback = function () {
                    var refresh = $(container).down('a.refresh-messages');
                    if (refresh) {
                        refresh.observe('click', function () {
                            this.checkMessages();
                        }.bind(this))
                    }
                }.bind(this);

            TemplateHandlerObj.checkMessages(
                id,
                nick,
                '',
                storeId,
                marketplaceId,
                checkAttributesAvailability,
                container,
                callback
            );
        },

        // ---------------------------------------

        description_template_id_change: function () {
            WalmartListingSettingsObj.hideEmptyOption(this);
        },

        // ---------------------------------------

        synchronization_template_id_change: function () {
            WalmartListingSettingsObj.hideEmptyOption(this);
        },

        // ---------------------------------------

        reload: function (url, id) {
            new Ajax.Request(url, {
                asynchronous: false,
                onSuccess: function (transport) {

                    var data = transport.responseText.evalJSON(true);

                    var options = '';

                    var firstItemValue = '';
                    var currentValue = $(id).value;

                    data.each(function (paris) {
                        var key = (typeof paris.key != 'undefined') ? paris.key : paris.id;
                        var val = (typeof paris.value != 'undefined') ? paris.value : paris.title;
                        options += '<option value="' + key + '">' + val + '</option>\n';

                        if (firstItemValue == '') {
                            firstItemValue = key;
                        }
                    });

                    $(id).update();
                    $(id).insert(options);

                    if (currentValue != '') {
                        $(id).value = currentValue;
                    } else {
                        if (M2ePro.formData[id] > 0) {
                            $(id).value = M2ePro.formData[id];
                        } else {
                            $(id).value = firstItemValue;
                        }
                    }
                    $(id).simulate('change');
                }
            });
        },

        // ---------------------------------------

        addNewTemplate: function (url, callback) {
            var win = window.open(url);

            var intervalId = setInterval(function () {

                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                callback && callback();

            }, 1000);
        },

        // ---------------------------------------

        newSellingFormatTemplateCallback: function () {
            var noteEl = $('template_selling_format_note');

            WalmartListingSettingsObj.reloadSellingFormatTemplates();
            if ($('template_selling_format_id').children.length > 0) {
                $('template_selling_format_id').show();
                noteEl && $('template_selling_format_note').show();
                $('template_selling_format_label').hide();
            } else {
                $('template_selling_format_id').hide();
                noteEl && $('template_selling_format_note').hide();
                $('template_selling_format_label').show();
            }
        },

        // ---------------------------------------

        newDescriptionTemplateCallback: function () {
            var noteEl = $('template_description_note');

            WalmartListingSettingsObj.reloadDescriptionTemplates();
            if ($('template_description_id').children.length > 0) {
                $('template_description_id').show();
                noteEl && $('template_description_note').show();
                $('template_description_label').hide();
            } else {
                $('template_description_id').hide();
                noteEl && $('template_description_note').hide();
                $('template_description_label').show();
            }
        },

        // ---------------------------------------

        newSynchronizationTemplateCallback: function () {
            var noteEl = $('template_synchronization_note');

            WalmartListingSettingsObj.reloadSynchronizationTemplates();
            if ($('template_synchronization_id').children.length > 0) {
                $('template_synchronization_id').show();
                noteEl && $('template_synchronization_note').show();
                $('template_synchronization_label').hide();
            } else {
                $('template_synchronization_id').hide();
                noteEl && $('template_synchronization_note').hide();
                $('template_synchronization_label').show();
            }
        }

        // ---------------------------------------
    });
});