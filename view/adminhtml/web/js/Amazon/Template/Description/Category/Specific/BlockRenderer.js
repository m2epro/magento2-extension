define([
    'M2ePro/Amazon/Template/Description/Category/Specific/Renderer'
], function () {

    window.AmazonTemplateDescriptionCategorySpecificBlockRenderer = Class.create(AmazonTemplateDescriptionCategorySpecificRenderer, {

        // ---------------------------------------

        isRootBlock : false,
        isFlatBlock : false,

        withOneBlockOnly       : false,
        parentWithOneBlockOnly : false,

        childFilteredSpecifics: {
            all        : [],
            containers : [],
            rows       : []
        },

        // ---------------------------------------

        process: function()
        {
            if (this.specificHandler.isSpecificRendered(this.indexedXPath)) {
                return '';
            }

            if (!this.load()) {
                return '';
            }

            this.prepareFilteredChildSpecifics();

            this.renderParentSpecific();
            this.renderSelf();
            this.renderChildRequiredBlocks();

            this.tuneStyles();
            this.throwEventsToParent();
        },

        load: function($super)
        {
            var loadResult = $super();

            if (this.specific.parent_specific_id == null) {
                this.isRootBlock = true;
                return loadResult;
            }

            var specifics = this.dictionaryHelper.getChildSpecifics(this.specific);
            if (specifics.length == 1 && this.dictionaryHelper.isSpecificTypeContainer(specifics[0])) {
                this.withOneBlockOnly = true;
                return loadResult;
            }

            // check nesting level
            if (this.indexedXPath.split('/').length >= 4) {
                this.isFlatBlock = true;
            }

            return loadResult;
        },

        //########################################

        prepareFilteredChildSpecifics: function()
        {
            var self       = this,
                all        = [],
                containers = [],
                rows       = [];

            var specifics = self.dictionaryHelper.getChildSpecifics(this.specific);
            specifics.each(function(specific) {

                self.dictionaryHelper.isSpecificTypeContainer(specific) ? containers.push(specific)
                                                                        : rows.push(specific);
                all.push(specific);
            });

            self.childFilteredSpecifics = {
                all        : all,
                containers : containers,
                rows       : rows
            };
        },

        // ---------------------------------------

        renderParentSpecific: function()
        {
            if (this.specific.parent_specific_id == null) {
                return '';
            }

            if (!this.dictionaryHelper.isSpecificTypeContainer(this.parentSpecific)) {
                return '';
            }

            var parentBlockRenderer = new AmazonTemplateDescriptionCategorySpecificBlockRenderer();
            parentBlockRenderer.setSpecificsHandler(this.specificHandler);
            parentBlockRenderer.setIndexedXpath(this.getParentIndexedXpath());

            parentBlockRenderer.process();
        },

        renderSelf: function()
        {
            this.prepareDomStructure();

            // ---------------------------------------
            $(this.indexedXPath).observe('my-duplicate-is-rendered', this.onMyDuplicateRendered.bind(this));
            $(this.indexedXPath).observe('undeleteble-specific-appear', this.onWhenUndeletebleSpecificAppears.bind(this));
            // ---------------------------------------

            this.specificHandler.markSpecificAsRendered(this.indexedXPath);

            var selectedObj = {};
            selectedObj['mode'] = this.MODE_NONE;
            selectedObj['is_required'] = this.dictionaryHelper.isSpecificRequired(this.specific) ? 1 : 0;

            this.specificHandler.markSpecificAsSelected(this.indexedXPath, selectedObj);

            this.renderButtons();

            this.renderAddSpecificsRow();
            this.renderGrid();
        },

        renderChildRequiredBlocks: function()
        {
            var self = this;

            this.childFilteredSpecifics.containers.each(function(specific) {

                if (!self.dictionaryHelper.isSpecificRequired(specific)) {
                    return true;
                }

                var renderer = new AmazonTemplateDescriptionCategorySpecificBlockRenderer();
                renderer.setSpecificsHandler(self.specificHandler);
                renderer.setIndexedXpath(self.getChildIndexedPart(specific));

                if(self.withOneBlockOnly) renderer.parentWithOneBlockOnly = true;
                renderer.process();
            });
        },

        // ---------------------------------------

        tuneStyles: function()
        {
            var block  = $(this.indexedXPath),
                header = $$('fieldset[id="' + this.indexedXPath + '"] legend').first();

            var blockStyles = {},
                headerStyles = {};

            if (this.isRootBlock) {
                headerStyles['display'] = 'none';
                blockStyles = {};
                blockStyles['width'] = '100%';
            }

            // container in container. like Product Type for example
            if (this.withOneBlockOnly) {
                headerStyles['display'] = 'none';
                blockStyles = {};
                blockStyles['width'] = '100%';
            }

            if (this.parentWithOneBlockOnly) {
                header.down('span').innerHTML = this.parentSpecific.title + ' > ' + header.down('span').innerHTML;
            }
            // ---------------------------------------

            // margin ParentGrid => Block
            var parentGrid = $(this.getParentIndexedXpath() + '_grid');
            if (parentGrid) {
                blockStyles['margin-top'] = '20px';
            }

            if (this.isFlatBlock && !this.parentWithOneBlockOnly) {
                delete(blockStyles['border-right']);
                delete(blockStyles['border-left']);
                delete(blockStyles['border-bottom']);
                blockStyles['padding'] = '0 15px 0 15px';
            }

            var compuledHeaderStyle = '';
            $H(headerStyles).each(function(el) { compuledHeaderStyle += el.key + ': ' + el.value + '; '; });
            header.setAttribute('style', compuledHeaderStyle);

            var compuledBlockStyle = '';
            $H(blockStyles).each(function(el) { compuledBlockStyle += el.key + ': ' + el.value + '; '; });
            block.setAttribute('style', compuledBlockStyle);
        },

        throwEventsToParent: function()
        {
            var parentXpath = this.getParentIndexedXpath();

            var myEvent = new CustomEvent('child-specific-rendered');
            parentXpath && $(parentXpath).dispatchEvent(myEvent);

            // my duplicate is already rendered
            this.touchMyNeighbors();
            // ---------------------------------------
        },

        //########################################

        prepareDomStructure: function()
        {
            var table = new Element('fieldset', {
                'id':           this.indexedXPath,
                'class':        'fieldset admin__fieldset m2epro-fieldset specifics-block',
                'cellspacing':  0,
                'cellpadding':  0
            });
            var td = table.appendChild(new Element('legend', {
                class: 'admin__legend legend'
            }));

            td.appendChild(new Element('span', {}))
                .insert(this.specific.title);

            this.getContainer().appendChild(table);
        },

        renderAddSpecificsRow: function()
        {
            var renderer = new AmazonTemplateDescriptionCategorySpecificBlockGridAddSpecificRenderer();
            renderer.setSpecificsHandler(this.specificHandler);
            renderer.setIndexedXpath(this.indexedXPath);
            renderer.setBlockRenderer(this);

            renderer.childAllSpecifics  = this.childFilteredSpecifics.all;
            renderer.childRowsSpecifics = this.childFilteredSpecifics.rows;

            renderer.process();
        },

        renderGrid: function()
        {
            var renderer = new AmazonTemplateDescriptionCategorySpecificBlockGridRenderer();
            renderer.setSpecificsHandler(this.specificHandler);
            renderer.setIndexedXpath(this.indexedXPath);
            renderer.childRowsSpecifics = this.childFilteredSpecifics.rows;

            renderer.process();
        },

        renderButtons: function()
        {
            var buttonsBlock = new Element('div', {
                'style': 'float: right; margin-top: 7px;'
            });

            buttonsBlock.appendChild(this.getAddSpecificButton());

            var cloneButton = this.getCloneButton();
            if(cloneButton !== null) buttonsBlock.appendChild(cloneButton);

            var removeButton = this.getRemoveButton();
            if(removeButton !== null) buttonsBlock.appendChild(removeButton);

            var div = $$('fieldset[id="' + this.indexedXPath + '"] legend').first();
            div.appendChild(buttonsBlock);
        },

        getAddSpecificButton: function()
        {
            var button = new Element('a', {
                'id'          : this.indexedXPath + '_add_button',
                'indexedxpath': this.indexedXPath,
                'href'        : 'javascript:void(0);',
                'class'       : 'specific-add-button',
                'style'       : 'vertical-align: middle;',
                'title'       : M2ePro.translator.translate('Add Specific into current container')
            });

            return button;
        },

        //########################################

        removeAction: function($super, event)
        {
            var deleteResult = $super(event);
            this.throwEventsToParent();

            // Product data injection to Specifics. Empty block should be removed
            var parentXpath = this.getParentIndexedXpath();
            if (parentXpath &&
                parentXpath === this.getRootIndexedXpath() + '/Product-1' &&
                this.getRenderedSpecificsInBlock(parentXpath).length === 0
            ) {
                $(parentXpath + '_remove_button').click();
            }

            return deleteResult;
        },

        //########################################

        getContainer: function()
        {
            if (this.isRootBlock) {
                return $('specifics_container');
            }

            return $(this.getParentIndexedXpath());
        }

        // ---------------------------------------
    });
});