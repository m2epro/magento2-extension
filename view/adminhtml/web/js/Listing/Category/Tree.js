define([
    'M2ePro/Common',
    'jquery',
    'M2ePro/External/jstree/jstree.min'
], function ($) {
    window.ListingCategoryTree = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {},

        // ---------------------------------------

        tree_buildCategory: function(parent, config) {
            if (!config) {
                return;
            }

            if (parent && config && config.length) {
                for (var i = 0; i < config.length; i++) {
                    config[i].state = { 'selected': categories_selected_items.indexOf(config[i].id) !== -1 };

                    parent.appendChild(config[i]);

                    if (config[i].children) {
                        ListingCategoryTreeObj.tree_buildCategory(parent, config[i].children);
                    }
                }
            }
        },

        tree_processChildren: function(node, state) {
            if (!node.hasChildNodes()) {
                return false;
            }

            for (var i = 0; i < node.childNodes.length; i++) {
                node.childNodes[i].state = { 'selected': state };
                if (node.childNodes[i].hasChildNodes()) {
                    ListingCategoryTreeObj.tree_processChildren(node.childNodes[i], state);
                }
            }

            return true;
        },

        tree_categoryAdd: function(id) {
            categories_selected_items.push(id);
            array_unique(categories_selected_items);
        },

        tree_categoryRemove: function(id) {
            while (categories_selected_items.indexOf(id) !== -1) {
                categories_selected_items.splice(categories_selected_items.indexOf(id), 1);
            }

            array_unique(categories_selected_items);
        },

        getCategoryTitleById: function(id) {
            return tree.jstree('get_node', id).text;
        }

        // ---------------------------------------
    });
});
