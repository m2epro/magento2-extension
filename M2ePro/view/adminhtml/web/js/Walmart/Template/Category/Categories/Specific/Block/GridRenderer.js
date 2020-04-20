define([
           'M2ePro/Walmart/Template/Category/Categories/Specific/Renderer'
       ], function () {

    window.WalmartTemplateCategoryCategoriesSpecificBlockGridRenderer = Class.create(WalmartTemplateCategoryCategoriesSpecificRenderer, {

        // ---------------------------------------

        childRowsSpecifics: [],

        // ---------------------------------------

        process: function()
        {
            if (this.childRowsSpecifics.length <= 0) {
                return '';
            }

            if (!this.load()) {
                return '';
            }

            this.prepareDomStructure();
            this.renderRequiredRows();

            this.tuneStyles();
        },

        //########################################

        prepareDomStructure: function()
        {
            this.getContainer().appendChild(this.getTemplate());
            $(this.indexedXPath + '_grid').observe('child-specific-rendered' , this.onChildSpecificRendered.bind(this));
        },

        renderRequiredRows: function()
        {
            var self = this;

            this.childRowsSpecifics.each(function(specific) {

                if (!self.dictionaryHelper.isSpecificRequired(specific)) {
                    return true;
                }

                var renderer = new WalmartTemplateCategoryCategoriesSpecificGridRowRenderer();
                renderer.setSpecificsHandler(self.specificHandler);
                renderer.setIndexedXpath(self.getChildIndexedPart(specific));

                renderer.process();
            });
        },

        // ---------------------------------------

        onChildSpecificRendered: function()
        {
            this.tuneStyles();

            if (this.indexedXPath != this.getRootIndexedXpath() && $(this.getParentIndexedXpath() + '_grid')) {
                var myEvent = new CustomEvent('child-specific-rendered');
                $(this.getParentIndexedXpath() + '_grid').dispatchEvent(myEvent);
            }
        },

        // ---------------------------------------

        tuneStyles: function()
        {
            if (this.childRowsSpecifics.length <= 0) {
                return '';
            }

            var gridObj = $(this.indexedXPath + '_grid');
            this.isAnyOfChildSpecificsRendered() ? gridObj.show() : gridObj.hide();
        },

        isAnyOfChildSpecificsRendered: function()
        {
            var countOfRenderedSpecifics = 0;
            var realRenderedXpathes = this.specificHandler.getRealXpathesOfRenderedSpecifics();

            this.childRowsSpecifics.each(function(sp) {
                if (realRenderedXpathes.indexOf(sp.xpath) >= 0) countOfRenderedSpecifics++;
            });

            return countOfRenderedSpecifics > 0;
        },

        //########################################

        isAlreadyRendered: function()
        {
            return $(this.indexedXPath + '_grid') == null;
        },

        getTemplate: function()
        {
            var template = $('specifics_list_grid_template').down('table').cloneNode(true);

            template.setAttribute('id', this.indexedXPath + '_grid');
            return template;
        },

        getContainer: function()
        {
            return $(this.indexedXPath);
        }

        // ---------------------------------------
    });
});