define([], function () {

    window.AmazonTemplateDescriptionCategorySpecificDictionary = Class.create();
    AmazonTemplateDescriptionCategorySpecificDictionary.prototype = {

        // ---------------------------------------

        TYPE_TEXT      : 1,
        TYPE_SELECT    : 2,
        TYPE_CONTAINER : 3,

        dictionarySpecifics: {},

        // ---------------------------------------

        initialize: function() {},

        // ---------------------------------------

        setDictionarySpecifics: function(specifics)
        {
            this.dictionarySpecifics = specifics;
        },

        // ---------------------------------------

        isSpecificRequired: function(specific)
        {
            return specific.min_occurs >= 1;
        },

        isSpecificDesired: function(specific)
        {
            return specific.data_definition.hasOwnProperty('is_desired') ? specific.data_definition.is_desired : false;
        },

        isSpecificTypeContainer: function(specific)
        {
            return parseInt(specific.type) == this.TYPE_CONTAINER;
        },

        isSpecificTypeText: function(specific)
        {
            return parseInt(specific.type) == this.TYPE_TEXT;
        },

        isSpecificTypeTextArea: function(specific)
        {
            return this.isSpecificTypeText(specific) &&
                specific.params.max_length && specific.params.max_length >= 100;
        },

        isSpecificTypeSelect: function(specific)
        {
            return parseInt(specific.type) == this.TYPE_SELECT;
        },

        // ---------------------------------------

        hasCalendarWidget: function(specific)
        {
            return this.hasCalendarDateTimeWidget(specific) || this.hasCalendarDateWidget(specific);
        },

        hasCalendarDateTimeWidget: function(specific)
        {
            return specific.params.type === 'date_time';
        },

        hasCalendarDateWidget: function(specific)
        {
            return specific.params.type === 'date';
        },

        // ---------------------------------------

        getParentSpecific: function(specific)
        {
            if (specific.parent_specific_id == null) {
                return null;
            }

            return this.dictionarySpecifics[specific.parent_specific_id];
        },

        getChildSpecifics: function(parentSpecific)
        {
            var self = this,
                specifics = [];

            if (self.dictionarySpecifics.length <= 0) {
                return specifics;
            }

            $H(self.dictionarySpecifics).each(function(specificData) {

                var specific = specificData.value;
                specific.parent_specific_id == parentSpecific.specific_id && specifics.push(specific);
            });

            if (specifics.length <= 0) {
                return specifics;
            }

            specifics.sort(this.sortSpecific);
            return specifics;
        },

        getAllChildSpecifics: function(parentSpecific)
        {
            var specifics = [],
                regexpObj = new RegExp('^' + parentSpecific.xpath + '/');

            $H(this.dictionarySpecifics).each(function(specificData) {

                var specific = specificData.value;
                specific.xpath.match(regexpObj) && specifics.push(specific);
            });

            return specifics;
        },

        // ---------------------------------------

        sortSpecific: function(spA, spB)
        {
            var aIsDesired = parseInt(spA.data_definition.is_desired);
            var bIsDesired = parseInt(spB.data_definition.is_desired);

            if (aIsDesired && !bIsDesired) {
                return -1;
            }

            if (bIsDesired && !aIsDesired) {
                return 1;
            }

            return spA.title == spB.title ? 0 : (spA.title > spB.title) ? 1 : -1;
        },

        getDictionarySpecific: function(xPathWithIndex)
        {
            var self = this;

            var dictionarySpecific;

            xPathWithIndex = xPathWithIndex.replace(/\/\-\d{1,}\//g,'/')
                .replace(/\-\d*/g,'');

            $H(self.dictionarySpecifics).each(function(specificData) {

                var specific = specificData.pop();
                if (specific.xpath == xPathWithIndex) {
                    dictionarySpecific = specific;
                    throw $break;
                }
            });

            return dictionarySpecific;
        }

        // ---------------------------------------
    };
});