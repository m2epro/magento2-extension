define([
    'M2ePro/Common'
], function() {
    window.EbayListingProductSettings = Class.create(Common, {

        // ---------------------------------------

        initialize: function ()
        {
            jQuery.validator.addMethod('M2ePro-validate-ebay-template-switcher', function (value, $element)
            {
                var label = $element.options[$element.selectedIndex].label;

                return !!label;

            }, M2ePro.translator.translate('This is a required field.'));
        },

        // ---------------------------------------

        initObservers: function ()
        {
            this.observeTemplate(
                'template_shipping_id',
                'add_shipping_template_link',
                'edit_shipping_template_link',
                'specify_shipping_template_link'
            );
            this.observeTemplate(
                'template_return_policy_id',
                'add_return_policy_template_link',
                'edit_return_policy_template_link',
                'specify_return_policy_template_link'
            );
            this.observeTemplate(
                'template_selling_format_id',
                'add_selling_format_template_link',
                'edit_selling_format_template_link',
                'specify_selling_template_link'
            );
            this.observeTemplate(
                'template_description_id',
                'add_description_template_link',
                'edit_description_template_link',
                'specify_description_template_link'
            );
            this.observeTemplate(
                'template_synchronization_id',
                'add_synchronization_template_link',
                'edit_synchronization_template_link',
                'specify_synchronization_template_link'
            );
        },

        // ---------------------------------------

        observeTemplate: function (id, add, edit, specify)
        {
            $(id).observe('change', function () {
                if ($(id).value && $(id).value != 0) {
                    $(edit).show();
                    $(add).show();
                    $(specify).hide();
                } else {
                    if (!$(id).value) {
                        $(add).show();
                        $(specify).hide();
                    }
                    else {
                        $(add).hide();
                        $(specify).show();
                    }
                    $(edit).hide();
                }
            });

            $(id).simulate('change');

            $(id).observe('change', function () {
                if (id == 'template_selling_format_id') {
                    EbayListingProductSettingsObj.checkSellingFormatMessages();
                }
                EbayListingProductSettingsObj.hideEmptyOption(this);
            });
            if ($(id).value) {
                $(id).simulate('change');
            }
        },

        // ---------------------------------------

        checkSellingFormatMessages: function ()
        {
            var storeId = $('store_id').value;
            var marketplaceId = $('marketplace_id').value;

            if (storeId.empty() || storeId < 0 || marketplaceId.empty() || marketplaceId < 0) {
                return;
            }

            var id = $('template_selling_format_id').value,
                    nick = 'selling_format',
                    container = 'template_selling_format_messages',
                    callback = function () {
                        var refresh = $(container).down('a.refresh-messages');
                        if (refresh) {
                            refresh.observe('click', function () {
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

        reload: function (url, id)
        {
            new Ajax.Request(url, {
                asynchronous: false,
                onSuccess: function (transport) {

                    var data = transport.responseText.evalJSON(true);

                    var options = '';
                    var firstItemValue = '';
                    var currentValue = $(id).value;

                    data.each(function (pairs) {
                        var key = (typeof pairs.key != 'undefined') ? pairs.key : pairs.id;
                        var val = (typeof pairs.value != 'undefined') ? pairs.value : pairs.title;
                        options += '<option value="' + key + '">' + val + '</option>\n';

                        if (firstItemValue == '') {
                            firstItemValue = key;
                        }
                    });

                    $(id).children[$(id).children.length - 1].update();
                    $(id).children[$(id).children.length - 1].insert(options);

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

        addNewTemplate: function (url, callback)
        {
            var win = window.open(url);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                callback && callback();
            }, 1000);
        },

        editTemplate: function(url, id, callback)
        {
            var win = window.open(url + 'id/' + id);

            var intervalId = setInterval(function () {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                callback && callback();
            }, 1000);
        },

        // ---------------------------------------

        newShippingTemplateCallback: function ()
        {
            var noteEl = $('template_shipping_note');

            EbayListingProductSettingsObj.reload(M2ePro.url.get('getShippingTemplates'), 'template_shipping_id');
            if ($('template_shipping_id').children.length > 0) {
                $('template_shipping_id').show();
                if (noteEl) {
                    noteEl.show();
                }
                $('template_shipping_label').hide();
            } else {
                $('template_shipping_id').hide();
                if (noteEl) {
                    noteEl.hide();
                }
                $('template_shipping_label').show();
            }
        },

        // ---------------------------------------

        newReturnPolicyTemplateCallback: function ()
        {
            var noteEl = $('template_return_policy_note');

            EbayListingProductSettingsObj.reload(M2ePro.url.get('getReturnPolicyTemplates'), 'template_return_policy_id');
            if ($('template_return_policy_id').children.length > 0) {
                $('template_return_policy_id').show();
                if (noteEl) {
                    noteEl.show();
                }
                $('template_return_policy_label').hide();
            } else {
                $('template_return_policy_id').hide();
                if (noteEl) {
                    noteEl.hide();
                }
                $('template_return_policy_label').show();
            }
        },

        // ---------------------------------------

        newSellingFormatTemplateCallback: function ()
        {
            var noteEl = $('template_selling_format_note');

            EbayListingProductSettingsObj.reload(M2ePro.url.get('getSellingFormatTemplates'), 'template_selling_format_id');
            if ($('template_selling_format_id').children.length > 0) {
                $('template_selling_format_id').show();
                if (noteEl) {
                    noteEl.show();
                }
                $('template_selling_format_label').hide();
            } else {
                $('template_selling_format_id').hide();
                if (noteEl) {
                    noteEl.hide();
                }
                $('template_selling_format_label').show();
            }
        },

        // ---------------------------------------

        newDescriptionTemplateCallback: function()
        {
            var noteEl = $('template_description_note');

            EbayListingProductSettingsObj.reload(M2ePro.url.get('getDescriptionTemplates'), 'template_description_id');
            if ($('template_description_id').children.length > 0) {
                $('template_description_id').show();
                if (noteEl) {
                    noteEl.show();
                }
                $('template_description_label').hide();
            } else {
                $('template_description_id').hide();
                if (noteEl) {
                    noteEl.hide();
                }
                $('template_description_label').show();
            }
        },

        // ---------------------------------------

        newSynchronizationTemplateCallback: function ()
        {
            var noteEl = $('template_synchronization_note');

            EbayListingProductSettingsObj.reload(M2ePro.url.get('getSynchronizationTemplates'), 'template_synchronization_id');
            if ($('template_synchronization_id').children.length > 0) {
                $('template_synchronization_id').show();
                if (noteEl) {
                    noteEl.show();
                }
                $('template_synchronization_label').hide();
            } else {
                $('template_synchronization_id').hide();
                if (noteEl) {
                    noteEl.hide();
                }
                $('template_synchronization_label').show();
            }
        },

        // ---------------------------------------

        save: function (callback)
        {
            var validationResult = jQuery('#edit_form').valid();

            if (!validationResult) {
                return;
            }

            $('edit_form').request({
                method: 'post',
                asynchronous: true,
                parameters: {},
                onSuccess: function (transport) {

                    var params = {};

                    $$('.template-switcher').each(function (switcher) {
                        params[switcher.name] = switcher.value;
                    });

                    if (typeof callback == 'function') {
                        callback(params);
                    }

                }.bind(this)
            });
        },

        // ---------------------------------------
    });
});
