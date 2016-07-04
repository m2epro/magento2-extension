define([
    'jquery',
    'M2ePro/Listing/AutoAction',
    'prototype'
], function (jQuery) {

    window.EbayListingAutoAction = Class.create(ListingAutoAction, {

        // ---------------------------------------

        controller: 'ebay_listing_autoAction',

        // ---------------------------------------

        addingModeChange: function()
        {
            var mode = ListingAutoActionObj.getPopupMode();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY')) {
                $(mode+'confirm_button').hide();
                $(mode+'continue_button').show();
                $(mode+'breadcrumb_container').show();
            } else {
                $(mode+'continue_button').hide();
                $(mode+'breadcrumb_container').hide();
                $(mode+'confirm_button').show();
            }

            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_NONE')) {
                $$('[id$="adding_add_not_visible_field"]')[0].show();
            } else {
                $$('[id$="adding_add_not_visible"]')[0].value = M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES');
                $$('[id$="adding_add_not_visible_field"]')[0].hide();
            }
        },

        categoryAddingMode: function ()
        {
            var popupMode = ListingAutoActionObj.getPopupMode();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY')) {
                $(popupMode+'confirm_button').hide();
                $(popupMode+'continue_button').show();
                $(popupMode+'breadcrumb_container').show();
            } else {
                $(popupMode+'continue_button').hide();
                $(popupMode+'breadcrumb_container').hide();
                $(popupMode+'confirm_button').show();
            }

            if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_NONE')) {
                $$('[id$="adding_add_not_visible_field"]')[0].show();
            } else {
                $$('[id$="adding_add_not_visible"]')[0].value = M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES');
                $$('[id$="adding_add_not_visible_field"]')[0].hide();
            }
        },

        // ---------------------------------------

        loadCategoryChooser: function(callback)
        {
            var mode = $('auto_mode').value;
            new Ajax.Request(M2ePro.url.get(ListingAutoActionObj.controller + '/getCategoryChooserHtml'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    auto_mode: mode,
                    group_id: this.internalData.id,
                    // this parameter only for auto_mode=category
                    magento_category_id: typeof categories_selected_items != 'undefined' ? categories_selected_items[0] : null
                },
                onSuccess: function(transport) {

                    if (mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Listing::AUTO_MODE_CATEGORY')) {
                        $('category_child_data_container').update(transport.responseText);
                    } else {
                        $('data_container').update(transport.responseText);
                    }

                    if (typeof callback == 'function') {
                        callback();
                    }
                }.bind(this)
            });
        },

        loadSpecific: function(callback)
        {
            var category = EbayListingProductCategorySettingsChooserObj.getSelectedCategory(0);

            if (!category.mode) {
                return;
            }

            new Ajax.Request(M2ePro.url.get(ListingAutoActionObj.controller + '/getCategorySpecificHtml'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    auto_mode: this.internalData.auto_mode,
                    category_mode: category.mode,
                    category_value: category.value,
                    group_id: this.internalData.id,
                    // this parameter only for auto_mode=category
                    magento_category_id: typeof categories_selected_items != 'undefined' ? categories_selected_items[0] : null
                },
                onSuccess: function(transport) {

                    var dataContainer;
                    if (this.internalData.auto_mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Listing::AUTO_MODE_CATEGORY')) {
                        dataContainer = $('category_child_data_container');
                    } else {
                        dataContainer = $('data_container');
                    }

                    jQuery(dataContainer).parent().parent().find('[id^="block_notice"]').eq(0).remove();

                    dataContainer.innerHTML = transport.responseText;
                    try {
                        dataContainer.innerHTML.evalScripts();
                    } catch (ignored) {}

                    if (typeof callback == 'function') {
                        callback();
                    }

                }.bind(this)
            });
        },

        // ---------------------------------------

        globalStepTwo: function()
        {
            ListingAutoActionObj.collectData();

            var callback = function() {
                jQuery('#'+ListingAutoActionObj.getPopupMode() + 'modal_auto_action > .block_notices:first')
                    .remove();

                jQuery('#'+ListingAutoActionObj.getPopupMode() + 'continue_button')
                    .off('click')
                    .on('click', ListingAutoActionObj.globalStepThree);

                ListingAutoActionObj.highlightBreadcrumbStep(2);
            };

            ListingAutoActionObj.loadCategoryChooser(callback);
        },

        globalStepThree: function()
        {
            if (!EbayListingProductCategorySettingsChooserObj.validate()) {
                return;
            }

            ListingAutoActionObj.collectData();

            var callback = function() {
                ListingAutoActionObj.highlightBreadcrumbStep(3);

                $(ListingAutoActionObj.getPopupMode() + 'confirm_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'reset_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'continue_button').hide();
            };

            ListingAutoActionObj.loadSpecific(callback);
        },

        // ---------------------------------------

        websiteStepTwo: function()
        {
            ListingAutoActionObj.collectData();

            var callback = function() {

                jQuery('#'+ListingAutoActionObj.getPopupMode() + 'modal_auto_action > .block_notices:first')
                    .remove();

                jQuery('#'+ListingAutoActionObj.getPopupMode() + 'continue_button')
                    .off('click')
                    .on('click', ListingAutoActionObj.websiteStepThree);

                ListingAutoActionObj.highlightBreadcrumbStep(2);
            };

            ListingAutoActionObj.loadCategoryChooser(callback);
        },

        websiteStepThree: function()
        {
            if (!EbayListingProductCategorySettingsChooserObj.validate()) {
                return;
            }

            ListingAutoActionObj.collectData();

            var callback = function() {
                ListingAutoActionObj.highlightBreadcrumbStep(3);

                $(ListingAutoActionObj.getPopupMode() + 'confirm_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'reset_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'continue_button').hide();
            };

            ListingAutoActionObj.loadSpecific(callback);
        },

        // ---------------------------------------

        categoryStepOne: function(groupId)
        {
            var mode = ListingAutoActionObj.getPopupMode();
            this.loadAutoCategoryForm(groupId, function() {
                $(mode+'close_button').hide();
            });
        },

        categoryStepTwo: function()
        {
            if (!ListingAutoActionObj.validate()) {
                return;
            }

            ListingAutoActionObj.collectData();

            var mode = ListingAutoActionObj.getPopupMode();
            var callback = function() {
                jQuery('#' + mode + 'continue_button')
                    .off('click')
                    .on('click', ListingAutoActionObj.categoryStepThree);

                ListingAutoActionObj.highlightBreadcrumbStep(2);
            };

            ListingAutoActionObj.loadCategoryChooser(callback);
        },

        categoryStepThree: function()
        {
            if (!EbayListingProductCategorySettingsChooserObj.validate()) {
                return;
            }

            ListingAutoActionObj.collectData();

            var mode = ListingAutoActionObj.getPopupMode();
            var callback = function() {
                ListingAutoActionObj.highlightBreadcrumbStep(3);

                $(mode+'confirm_button').show();
                $(mode+'reset_button').show();
                $(mode+'continue_button').hide();
            };

            ListingAutoActionObj.loadSpecific(callback);
        },

        // ---------------------------------------

        collectData: function()
        {
            if ($('auto_mode')) {
                switch (parseInt($('auto_mode').value)) {
                    case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_GLOBAL'):
                        ListingAutoActionObj.internalData = {
                            auto_mode: $('auto_mode').value,
                            auto_global_adding_mode: $('auto_global_adding_mode').value,
                            auto_global_adding_add_not_visible: $('auto_global_adding_add_not_visible').value,
                            auto_global_adding_template_category_id: null
                        };
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_WEBSITE'):
                        ListingAutoActionObj.internalData = {
                            auto_mode: $('auto_mode').value,
                            auto_website_adding_mode: $('auto_website_adding_mode').value,
                            auto_website_adding_add_not_visible: $('auto_website_adding_add_not_visible').value,
                            auto_website_adding_template_category_id: null,
                            auto_website_deleting_mode: $('auto_website_deleting_mode').value
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
                            categories: categories_selected_items
                        };
                        break;
                }
            }

            if ($('ebay_category_chooser')) {
                ListingAutoActionObj.internalData.template_category_data = EbayListingProductCategorySettingsChooserObj.getInternalData();
            }

            if ($('category_specific_form')) {
                ListingAutoActionObj.internalData.template_category_specifics_data = EbayListingProductCategorySettingsSpecificObj.getInternalData();
            }
        }

        // ---------------------------------------
    });

});