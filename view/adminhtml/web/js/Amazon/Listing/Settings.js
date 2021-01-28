define([
    'M2ePro/Common'
], function() {

    window.AmazonListingSettings = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {
        },

        initObservers: function() {
            $('template_selling_format_id').observe('change', function() {
                if ($('template_selling_format_id').value) {
                    $('edit_selling_format_template_link').show();
                } else {
                    $('edit_selling_format_template_link').hide();
                }
            });
            $('template_selling_format_id').simulate('change');

            $('template_synchronization_id').observe('change', function() {
                if ($('template_synchronization_id').value) {
                    $('edit_synchronization_template_link').show();
                } else {
                    $('edit_synchronization_template_link').hide();
                }
            });
            $('template_synchronization_id').simulate('change');

            $('template_shipping_id').observe('change', function() {
                if ($('template_shipping_id').value) {
                    $('edit_shipping_template_link').show();
                } else {
                    $('edit_shipping_template_link').hide();
                }
            });
            $('template_shipping_id').simulate('change');

            $('template_selling_format_id').observe('change', function() {
                AmazonListingSettingsObj.checkSellingFormatMessages();
                AmazonListingSettingsObj.hideEmptyOption(this);
            });
            if ($('template_selling_format_id').value) {
                $('template_selling_format_id').simulate('change');
            }

            $('template_synchronization_id').observe('change', function() {
                AmazonListingSettingsObj.hideEmptyOption(this);
            });
            if ($('template_synchronization_id').value) {
                $('template_synchronization_id').simulate('change');
            }

            $('template_shipping_id').observe('change', function() {
                AmazonListingSettingsObj.hideEmptyOption(this);
            });
            if ($('template_shipping_id').value) {
                $('template_shipping_id').simulate('change');
            }

            $('sku_mode').observe('change', AmazonListingCreateSellingObj.sku_mode_change);

            $('sku_modification_mode')
                .observe('change', AmazonListingCreateSellingObj.sku_modification_mode_change)
                .simulate('change');

            $('condition_mode').observe('change', AmazonListingCreateSellingObj.condition_mode_change)
                .simulate('change');

            $('condition_note_mode').observe('change', AmazonListingCreateSellingObj.condition_note_mode_change);

            $('image_main_mode')
                .observe('change', AmazonListingCreateSellingObj.image_main_mode_change)
                .simulate('change');

            $('gallery_images_mode')
                .observe('change', AmazonListingCreateSellingObj.gallery_images_mode_change)
                .simulate('change');

            $('gift_wrap_mode')
                .observe('change', AmazonListingCreateSellingObj.gift_wrap_mode_change)
                .simulate('change');

            $('gift_message_mode')
                .observe('change', AmazonListingCreateSellingObj.gift_message_mode_change)
                .simulate('change');

            $('handling_time_mode')
                .observe('change', AmazonListingCreateSellingObj.handling_time_mode_change)
                .simulate('change');

            $('restock_date_mode')
                .observe('change', AmazonListingCreateSellingObj.restock_date_mode_change)
                .simulate('change');
        },

        // ---------------------------------------

        saveClick: function(url, skipValidation) {

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

            this.submitForm(url);
        },

        saveAndEditClick: function(url, lastActiveTab) {
            if (typeof categories_selected_items != 'undefined' && !this.isValidForm()) {
                array_unique(categories_selected_items);

                var selectedCategories = implode(',', categories_selected_items);

                $('selected_categories').value = selectedCategories;
            }

            if (lastActiveTab && url) {
                var tabsUrl = '|tab=' + jQuery('#amazonListingEditTabs').data().tabs.active.find('a')[0].id.split('_').pop();
                url = url + 'back/' + base64_encode('edit' + tabsUrl) + '/';
            }

            this.submitForm(url);
        },

        // ---------------------------------------

        checkSellingFormatMessages: function() {
            var storeId = $('store_id').value;
            var marketplaceId = $('marketplace_id').value;

            if (storeId.empty() || storeId < 0 || marketplaceId.empty() || marketplaceId < 0) {
                return;
            }

            var id = $('template_selling_format_id').value,
                nick = 'selling_format',
                storeId = storeId,
                marketplaceId = marketplaceId,
                container = 'template_selling_format_messages',
                callback = function() {
                    var refresh = $(container).down('a.refresh-messages');
                    if (refresh) {
                        refresh.observe('click', function() {
                            this.checkSellingFormatMessages();
                        }.bind(this));
                    }
                }.bind(this);

            TemplateManagerObj.checkMessages(
                id,
                nick,
                '',
                storeId,
                marketplaceId,
                container,
                callback
            );
        },

        // ---------------------------------------

        reload: function(url, id, addEmptyOption = false) {
            new Ajax.Request(url, {
                asynchronous: false,
                onSuccess: function(transport) {

                    var data = transport.responseText.evalJSON(true);

                    var options = '';

                    if (addEmptyOption) {
                        options += '<option value=""></option>\n';
                    }

                    var firstItemValue = '';
                    var currentValue = $(id).value;

                    data.each(function(paris) {
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

        addNewTemplate: function(url, callback) {
            var win = window.open(url);

            var intervalId = setInterval(function() {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                callback && callback();

            }, 1000);
        },

        editTemplate: function(url, id, callback) {
            var win = window.open(url + 'id/' + id);

            var intervalId = setInterval(function() {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                callback && callback();

            }, 1000);
        },

        // ---------------------------------------

        newSellingFormatTemplateCallback: function() {
            var noteEl = $('template_selling_format_note');

            AmazonListingSettingsObj.reload(M2ePro.url.get('getSellingFormatTemplates'), 'template_selling_format_id');
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

        newSynchronizationTemplateCallback: function() {
            var noteEl = $('template_synchronization_note');

            AmazonListingSettingsObj.reload(M2ePro.url.get('getSynchronizationTemplates'), 'template_synchronization_id');
            if ($('template_synchronization_id').children.length > 0) {
                $('template_synchronization_id').show();
                noteEl && $('template_synchronization_note').show();
                $('template_synchronization_label').hide();
            } else {
                $('template_synchronization_id').hide();
                noteEl && $('template_synchronization_note').hide();
                $('template_synchronization_label').show();
            }
        },

        newShippingTemplateCallback: function() {
            var noteEl = $('template_shipping_note');

            AmazonListingSettingsObj.reload(M2ePro.url.get('getShippingTemplates'), 'template_shipping_id', true);
            if ($('template_shipping_id').children.length > 0) {
                $('template_shipping_id').show();
                noteEl && $('template_shipping_note').show();
                $('template_shipping_label').hide();
            } else {
                $('template_shipping_id').hide();
                noteEl && $('template_shipping_note').hide();
                $('template_shipping_label').show();
            }
        }

        // ---------------------------------------
    });
});
