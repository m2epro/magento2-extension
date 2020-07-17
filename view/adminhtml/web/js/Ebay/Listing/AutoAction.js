define([
    'jquery',
    'M2ePro/Listing/AutoAction',
    'prototype'
], function (jQuery) {
    window.EbayListingAutoAction = Class.create(ListingAutoAction, {

        // ---------------------------------------

        getController: function()
        {
            return 'ebay_listing_autoAction';
        },

        // ---------------------------------------

        addingModeChange: function()
        {
            var mode = ListingAutoActionObj.getPopupMode();
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY')) {
                $(mode+'confirm_button').hide();
                $(mode+'continue_button').show();
            } else {
                $(mode+'continue_button').hide();
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
            } else {
                $(popupMode+'continue_button').hide();
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
            new Ajax.Request(M2ePro.url.get(ListingAutoActionObj.getController() + '/getCategoryChooserHtml'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    auto_mode: mode,
                    group_id: this.internalData.id,
                    // this parameter only for auto_mode=category
                    magento_category_id: typeof categories_selected_items != 'undefined' ? categories_selected_items[0] : null
                },
                onSuccess: function(transport) {
                    var dataContainer;
                    if (mode == M2ePro.php.constant('\\Ess\\M2ePro\\Model\\Listing::AUTO_MODE_CATEGORY')) {
                        dataContainer = $('category_child_data_container');
                    } else {
                        dataContainer = $('data_container');
                    }

                    dataContainer.update();
                    $('ebay_category_chooser').update(transport.responseText);

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

                $(ListingAutoActionObj.getPopupMode() + 'confirm_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'reset_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'continue_button').hide();
            };

            ListingAutoActionObj.loadCategoryChooser(callback);
        },

        websiteStepTwo: function()
        {
            ListingAutoActionObj.collectData();

            var callback = function() {

                jQuery('#'+ListingAutoActionObj.getPopupMode() + 'modal_auto_action > .block_notices:first')
                    .remove();

                $(ListingAutoActionObj.getPopupMode() + 'confirm_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'reset_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'continue_button').hide();
            };

            ListingAutoActionObj.loadCategoryChooser(callback);
        },

        categoryStepTwo: function()
        {
            if (!ListingAutoActionObj.validate()) {
                return;
            }

            ListingAutoActionObj.collectData();

            var callback = function() {
                $(ListingAutoActionObj.getPopupMode() + 'confirm_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'reset_button').show();
                $(ListingAutoActionObj.getPopupMode() + 'continue_button').hide();
            };

            ListingAutoActionObj.loadCategoryChooser(callback);
        },

        // ---------------------------------------

        collectData: function($super)
        {
            $super();
            if (typeof EbayTemplateCategoryChooserObj !== 'undefined') {
                var selectedCategories = EbayTemplateCategoryChooserObj.selectedCategories;
                var typeMain = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN');
                if (typeof selectedCategories[typeMain] !== 'undefined') {
                    selectedCategories[typeMain]['specific'] = EbayTemplateCategoryChooserObj.selectedSpecifics;
                }
                ListingAutoActionObj.internalData.template_category_data = selectedCategories;
            }
        }

        // ---------------------------------------
    });

});
