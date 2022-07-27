define([
    'M2ePro/Common',
    'extjs/ext-tree-checkbox'
], function () {
    window.ListingCategoryTree = Class.create(Common, {

        // ---------------------------------------

        initialize: function() {},

        // ---------------------------------------

        tree_buildCategory: function(parent, config)
        {
            if (!config) {
                return;
            }

            if (parent && config && config.length) {
                for (var i = 0; i < config.length; i++) {

                    config[i].uiProvider = Ext.tree.CheckboxNodeUI;

                    var node = new Ext.tree.TreeNode(config[i]);

                    for (var j=0;j<initTreeSelectedNodes.length;j++) {
                        if (config[i].id == initTreeSelectedNodes[j][0]) {
                            initTreeSelectedNodes[j][1] = node;
                            initTreeSelectedNodes[j][1].attributes.checked = true;
                            break;
                        }
                    }

                    for (var k=0;k<initTreeHighlightedNodes.length;k++) {
                        if (config[i].id == initTreeHighlightedNodes[k][0]) {
                            initTreeHighlightedNodes[k][1] = node;
                            break;
                        }
                    }

                    parent.appendChild(node);

                    if (config[i].children) {
                        ListingCategoryTreeObj.tree_buildCategory(node, config[i].children);
                    }
                }
            }
        },

        tree_processChildren: function(node, state)
        {
            if (!node.hasChildNodes()) {
                return false;
            }

            for (var i = 0; i < node.childNodes.length; i++) {
                node.childNodes[i].ui.check(state);
                if (node.childNodes[i].hasChildNodes()) {
                    ListingCategoryTreeObj.tree_processChildren(node.childNodes[i], state);
                }
            }

            return true;
        },

        tree_categoryAdd: function(id)
        {
            categories_selected_items.push(id);
            array_unique(categories_selected_items);
        },

        tree_categoryRemove: function(id)
        {
            while (categories_selected_items.indexOf(id) != -1) {
                categories_selected_items.splice(categories_selected_items.indexOf(id), 1);
            }

            array_unique(categories_selected_items);
        },

        getCategoryTitleById: function(id)
        {
            return tree.getNodeById(id).ui.getTextEl().innerHTML;
        }

        // ---------------------------------------
    });
});