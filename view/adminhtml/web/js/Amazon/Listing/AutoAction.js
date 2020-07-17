define([
    'jquery',
    'M2ePro/Listing/AutoAction',
    'prototype'
], function (jQuery) {

    window.AmazonListingAutoAction = Class.create(ListingAutoAction, {

        // ---------------------------------------

        getController: function()
        {
            return 'amazon_listing_autoAction';
        },

        // ---------------------------------------

        addingModeChange: function(el)
        {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_ADD') &&
                ListingAutoActionObj.showCreateNewAsin) {

                $('auto_action_amazon_add_and_create_asin').show();
            } else {
                $('auto_action_amazon_add_and_create_asin').hide();
                $('auto_action_amazon_add_and_assign_description_template').hide();
                $('auto_action_create_asin').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO');
                $('adding_description_template_id').value = '';
            }

            if (el.target.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_NONE')) {
                $$('[id$="adding_add_not_visible_field"]')[0].show();
            } else {
                $$('[id$="adding_add_not_visible"]')[0].value = M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES');
                $$('[id$="adding_add_not_visible_field"]')[0].hide();
            }
        },

        createAsinChange: function(el)
        {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES')) {
                $('auto_action_amazon_add_and_assign_description_template').show();
            } else {
                $('auto_action_amazon_add_and_assign_description_template').hide();
                $('adding_description_template_id').value = '';
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
                        adding_description_template_id : $('adding_description_template_id').value
                    }
                );
            }
        },

        reloadDescriptionTemplates: function()
        {
            var select = $('adding_description_template_id');

            new Ajax.Request(M2ePro.url.get(ListingAutoActionObj.getController() + '/getDescriptionTemplatesList'), {
                onSuccess: function(transport) {

                    var data = transport.responseText.evalJSON(true);

                    var options = '<option></option>';

                    var firstItem = null;
                    var currentValue = select.value;

                    data.each(function(item) {
                        var key = item.id;
                        var val = item.title;
                        var disabled = item.is_new_asin_accepted == 0 ? ' disabled="disabled"' : '';

                        options += '<option value="' + key + '"' + disabled + '>' + val + '</option>\n';

                        if (!firstItem) {
                            firstItem = item;
                        }
                    });

                    select.update();
                    select.insert(options);

                    if (currentValue != '') {
                        $('adding_description_template_id').value = currentValue;
                    } else if (typeof id !== 'undefined' && M2ePro.formData[id] > 0) {
                        select.value = M2ePro.formData[id];
                    } else if (firstItem.is_new_asin_accepted != 0) {
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
