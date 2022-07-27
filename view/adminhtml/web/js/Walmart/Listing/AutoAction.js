define([
    'jquery',
    'M2ePro/Listing/AutoAction',
    'prototype'
], function (jQuery) {

    window.WalmartListingAutoAction = Class.create(ListingAutoAction, {

        // ---------------------------------------

        getController: function()
        {
            return 'walmart_listing_autoAction';
        },

        // ---------------------------------------

        addingModeChange: function(el)
        {
            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_ADD')) {
                $('adding_category_template_id').value = '';
            }

            if (el.target.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_NONE')) {
                $$('[id$="adding_add_not_visible_field"]')[0].show();
                $('auto_action_walmart_add_and_assign_category_template').show();
            } else {
                $('auto_action_walmart_add_and_assign_category_template').hide();
                $$('[id$="adding_add_not_visible"]')[0].value = M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES');
                $$('[id$="adding_add_not_visible_field"]')[0].hide();
            }
        },

        // ---------------------------------------

        collectData: function($super)
        {
            $super();
            if ($('auto_mode')) {
                ListingAutoActionObj.internalData = Object.extend(
                    ListingAutoActionObj.internalData,
                    {
                        adding_category_template_id : $('adding_category_template_id').value
                    }
                );
            }
        },

        reloadCategoryTemplates: function()
        {
            var select = $('adding_category_template_id');

            new Ajax.Request(M2ePro.url.get(ListingAutoActionObj.getController() + '/getCategoryTemplatesList'), {
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
            return this.openWindow(url, callback);
        }
    });

    // ---------------------------------------
});
