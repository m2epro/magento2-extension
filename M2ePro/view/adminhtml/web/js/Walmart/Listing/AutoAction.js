define([
    'jquery',
    'M2ePro/Listing/AutoAction',
    'prototype'
], function (jQuery) {

    window.WalmartListingAutoAction = Class.create(ListingAutoAction, {

        // ---------------------------------------

        controller: 'walmart_listing_autoAction',

        // ---------------------------------------

        addingModeChange: function(el)
        {
            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_ADD')) {
                $('auto_action_walmart_add_and_assign_category_template').hide();
                $('adding_category_template_id').value = '';
            } else {
                $('auto_action_walmart_add_and_assign_category_template').show();
            }

            if (el.target.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_NONE')) {
                $$('[id$="adding_add_not_visible_field"]')[0].show();
            } else {
                $$('[id$="adding_add_not_visible"]')[0].value = M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES');
                $$('[id$="adding_add_not_visible_field"]')[0].hide();
            }
        },

        categoryStepOne: function(groupId)
        {
            this.loadAutoCategoryForm(groupId, function() {
                $('category_close_button').hide();
            });
        },

        collectData: function()
        {
            if ($('auto_mode')) {
                switch (parseInt($('auto_mode').value)) {
                    case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_GLOBAL'):
                        ListingAutoActionObj.internalData = {
                            auto_mode: $('auto_mode').value,
                            auto_global_adding_mode: $('auto_global_adding_mode').value,
                            auto_global_adding_add_not_visible: $('auto_global_adding_add_not_visible').value,
                            adding_category_template_id: $('adding_category_template_id').value
                        };
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_WEBSITE'):
                        ListingAutoActionObj.internalData = {
                            auto_mode: $('auto_mode').value,
                            auto_website_adding_mode: $('auto_website_adding_mode').value,
                            auto_website_adding_add_not_visible: $('auto_website_adding_add_not_visible').value,
                            auto_website_deleting_mode: $('auto_website_deleting_mode').value,
                            adding_category_template_id: $('adding_category_template_id').value
                        };
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_CATEGORY'):
                        ListingAutoActionObj.internalData = {
                            id: $('group_id').value,
                            title: $('group_title').value,
                            auto_mode: $('auto_mode').value,
                            adding_mode: $('adding_mode').value,
                            adding_add_not_visible: $('adding_add_not_visible').value,
                            deleting_mode: $('deleting_mode').value,
                            categories: categories_selected_items,
                            adding_category_template_id: $('adding_category_template_id').value
                        };
                        break;
                }
            }
        },

        reloadCategoryTemplates: function()
        {
            var select = $('adding_category_template_id');

            new Ajax.Request(M2ePro.url.get(ListingAutoActionObj.controller + '/getCategoryTemplatesList'), {
                onSuccess: function(transport) {

                    var data = transport.responseText.evalJSON(true);

                    var options = '<option></option>';

                    var firstItem = null;
                    var currentValue = select.value;

                    data.each(function(item) {
                        var key = item.id;
                        var val = item.title;

                        options += '<option value="' + key + '"' + '>' + val + '</option>\n';

                        if (!firstItem) {
                            firstItem = item;
                        }
                    });

                    select.update();
                    select.insert(options);

                    if (currentValue != '') {
                        $('adding_category_template_id').value = currentValue;
                    } else if (typeof id !== 'undefined' && M2ePro.formData[id] > 0) {
                        select.value = M2ePro.formData[id];
                    } else {
                        select.value = firstItem.id;
                    }

                    select.simulate('change');
                }
            });
        },

        addNewTemplate: function(url, callback)
        {
            var win = window.open(url);

            var intervalId = setInterval(function() {

                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                callback && callback();

            }, 1000);
        }
    });

    // ---------------------------------------
});