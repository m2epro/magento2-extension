define([
    'M2ePro/Common'

], function () {
    window.EbayListingProductCategorySettingsChooserTabsBrowse = Class.create();
    EbayListingProductCategorySettingsChooserTabsBrowse.prototype = Object.extend(new Common(), {

        // ---------------------------------------

        initialize: function () {
            this.marketplaceId = null;
            this.observers = {
                "leaf_selected": [],
                "not_leaf_selected": [],
                "any_selected": []
            };
        },

        // ---------------------------------------

        setMarketplaceId: function (marketplaceId) {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;
            self.marketplaceId = marketplaceId;
        },

        getMarketplaceId: function () {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            if (self.marketplaceId === null) {
                self.alert('You must set Site');
            }

            return self.marketplaceId;
        },

        getCategoriesSelectElementId: function (categoryId) {
            if (categoryId === null) categoryId = 0;
            return 'category_chooser_select_' + categoryId;
        },

        getCategoryChildrenElementId: function (categoryId) {
            if (categoryId === null) categoryId = 0;
            return 'category_chooser_children_' + categoryId;
        },

        getSelectedCategories: function () {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            var categoryId = 0;
            var selectedCategories = [];
            var isLastCategory = false;

            while (!isLastCategory) {
                var categorySelect = $(self.getCategoriesSelectElementId(categoryId));
                if (!categorySelect || categorySelect.selectedIndex == -1) {
                    break;
                }

                categoryId = selectedCategories[selectedCategories.length]
                    = categorySelect.options[categorySelect.selectedIndex].value;

                if (categorySelect.options[categorySelect.selectedIndex].getAttribute('is_leaf') == 1) {
                    isLastCategory = true;
                }
            }

            return selectedCategories;
        },

        // ---------------------------------------

        renderTopLevelCategories: function (containerId) {
            this.prepareDomStructure(null, $(containerId));
            this.renderChildCategories(null);
        },

        renderChildCategories: function (parentCategoryId) {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            new Ajax.Request(M2ePro.url.get('ebay_category/getChildCategories'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    "parent_category_id": parentCategoryId,
                    "marketplace_id": self.getMarketplaceId(),
                    "account_id": EbayListingProductCategorySettingsChooserObj.getAccountId(),
                    "category_type": $('category_type').value
                },
                onSuccess: function (transport) {

                    if (transport.responseText.length <= 2) {
                        self.simulate('leaf_selected', self.getSelectedCategories());
                        return;
                    }

                    var categories = JSON.parse(transport.responseText);
                    var optionsHtml = '';
                    var arrowString = '';
                    categories.each(function (category) {
                        if (parseInt(category.is_leaf) == 0) {
                            arrowString = ' > ';
                        } else {
                            arrowString = '';
                        }

                        optionsHtml += '<option is_leaf="' + category.is_leaf + '" value="' + category.category_id + '">' +
                            category.title + arrowString +
                            '</option>';
                    });

                    $(self.getCategoriesSelectElementId(parentCategoryId)).innerHTML = optionsHtml;
                    $(self.getCategoriesSelectElementId(parentCategoryId)).style.display = 'inline-block';
                    $('chooser_browser').scrollLeft = $('chooser_browser').scrollWidth;
                }
            });
        },

        onSelectCategory: function (select) {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            var parentCategoryId = select.id.replace(self.getCategoriesSelectElementId(""), "");
            var categoryId = select.options[select.selectedIndex].value;
            var is_leaf = select.options[select.selectedIndex].getAttribute('is_leaf');

            var selectedCategories = self.getSelectedCategories();

            var parentDiv = $(self.getCategoryChildrenElementId(parentCategoryId));
            parentDiv.innerHTML = '';

            self.simulate('any_selected', selectedCategories);

            if (is_leaf == 1) {
                self.simulate('leaf_selected', selectedCategories);
                return;
            }

            self.simulate('not_leaf_selected', selectedCategories);

            self.prepareDomStructure(categoryId, parentDiv);
            self.renderChildCategories(categoryId);
        },

        prepareDomStructure: function (categoryId, parentDiv) {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            var childrenSelect = document.createElement('select');
            childrenSelect.id = self.getCategoriesSelectElementId(categoryId);
            childrenSelect.style.minWidth = '200px';
            childrenSelect.style.maxHeight = 'none';
            childrenSelect.size = 10;
            childrenSelect.className = 'multiselect admin__control-multiselect';
            childrenSelect.onchange = function () {
                EbayListingProductCategorySettingsChooserTabsBrowseObj.onSelectCategory(this);
            };
            childrenSelect.style.display = 'none';
            parentDiv.appendChild(childrenSelect);

            var childrenDiv = document.createElement('div');
            childrenDiv.id = self.getCategoryChildrenElementId(categoryId);
            childrenDiv.className = 'category-children-block';
            parentDiv.appendChild(childrenDiv);
        },

        // ---------------------------------------

        observe: function (event, observer) {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            if (typeof observer != 'function') {
                self.alert('Observer must be a function!');
                return;
            }

            if (typeof self.observers[event] == 'undefined') {
                self.alert('Event does not supported!');
                return;
            }

            self.observers[event][self.observers[event].length] = observer;
        },

        simulate: function (event, parameters) {
            var self = EbayListingProductCategorySettingsChooserTabsBrowseObj;

            parameters = parameters || null;

            if (typeof self.observers[event] == 'undefined') {
                self.alert('Event does not supported!');
                return;
            }

            if (self.observers[event].length == 0) {
                return;
            }

            self.observers[event].each(function (observer) {
                if (parameters == null) {
                    (observer)();
                } else {
                    (observer)(parameters);
                }
            });
        }

        // ---------------------------------------
    });
});