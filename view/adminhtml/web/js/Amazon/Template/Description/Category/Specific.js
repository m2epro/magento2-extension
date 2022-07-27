define([
    'jquery',
    'M2ePro/Common'
], function (jQuery) {

    window.AmazonTemplateDescriptionCategorySpecific = Class.create(Common, {

        // ---------------------------------------

        initialize: function()
        {
            var self = this;

            self.dictionaryHelper = new AmazonTemplateDescriptionCategorySpecificDictionary();

            self.specificsContainer   = $('specifics_container');
            self.specificsHiddenInput = $('encoded_specifics_data');

            // ---------------------------------------

            self.categoryInfo    = null;
            self.productDataNick = null;

            self.formDataSpecifics = [];
            self.selectedSpecifics = {};
            self.renderedSpecifics = [];

            self.themeAttributes  = [];

            // ---------------------------------------

            self.initValidation();
        },

        initValidation: function()
        {
            var self = this;

            jQuery.validator.addMethod('M2ePro-specifics-validation', function(value, element) {

                if (!element.visible()) {
                    return true;
                }

                var params = self.dictionaryHelper.dictionarySpecifics[element.getAttribute('specific_id')].params;

                if (params.type == 'int') return self.intTypeValidator(value, params, element);
                if (params.type == 'float') return self.floatTypeValidator(value, params, element);
                if (params.type == 'string') return self.stringTypeValidator(value, params, element);
                if (params.type == 'date_time') return self.dateTimeTypeValidator(value, params, element);

                return true;
            }, M2ePro.translator.translate('The value is incorrect.'));

            jQuery.validator.addMethod('M2ePro-specificAttributes-validation', function(value, element) {

                if (!element.visible()) {
                    return true;
                }

                var specific = self.dictionaryHelper.dictionarySpecifics[element.getAttribute('specific_id')];
                var params   = specific.params.attributes[element.getAttribute('attribute_index')];

                if (params.type == 'int') return self.intTypeValidator(value, params, element);
                if (params.type == 'float') return self.floatTypeValidator(value, params, element);
                if (params.type == 'string') return self.stringTypeValidator(value, params, element);
                if (params.type == 'date_time') return self.dateTimeTypeValidator(value, params, element);

                return true;
            }, M2ePro.translator.translate('The value is incorrect.'));
        },

        intTypeValidator: function(value, params, element) {

            value = value.replace(',', '.');

            if (isNaN(parseInt(value)) || substr_count(value, '.') > 0) {
                return false;
            }

            var validators = {
                'min_value': function(value, restriction) {
                    return parseInt(value) >= parseInt(restriction);
                },
                'max_value': function(value, restriction) {
                    return parseInt(value) <= parseInt(restriction);
                },
                'total_digits': function(value, restriction) {
                    return value.length <= parseInt(restriction);
                }
            };

            for (var paramName in params) {
                if (params.hasOwnProperty(paramName) && validators[paramName]) {
                    if (!validators[paramName](value, params[paramName])) {
                        return false;
                    }
                }
            }

            return true;
        },

        floatTypeValidator: function(value, params, element) {

            value = value.replace(',', '.');

            if (isNaN(parseFloat(value)) || substr_count(value, '.') > 1 || value.substr(-1) == '.') {
                return false;
            }

            var validators = {
                'min_value': function(value, restriction) {
                    return parseFloat(value) >= parseFloat(restriction);
                },
                'max_value': function(value, restriction) {
                    return parseFloat(value) <= parseFloat(restriction);
                },
                'total_digits': function(value, restriction) {
                    return value.replace('.', '').length <= restriction;
                },
                'decimal_places': function(value, restriction) {
                    return value.indexOf('.') != -1 ? (value.replace(/\d*\./,'').length <= restriction.value) : true;
                }
            };

            for (var paramName in params) {
                if (params.hasOwnProperty(paramName) && validators[paramName]) {
                    if (!validators[paramName](value, params[paramName])) {
                        return false;
                    }
                }
            }

            return true;
        },

        dateTimeTypeValidator: function(value, params, element) {
            return /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/g.test(value);
        },

        stringTypeValidator: function(value, params, element) {

            var validators = {
                'min_length': function(value, restriction) {
                    return value.length >= parseInt(restriction);
                },
                'max_length': function(value, restriction) {
                    return value.length <= parseInt(restriction);
                },
                'pattern': function(value, restriction) {
                    return value.match(new RegExp('^' + restriction + '$'));
                }
            };

            for (var paramName in params) {
                if (params.hasOwnProperty(paramName) && validators[paramName]) {
                    if (!validators[paramName](value, params[paramName])) {
                        return false;
                    }
                }
            }

            return true;
        },

        // ---------------------------------------

        setFormDataSpecifics: function(values)
        {
            this.formDataSpecifics = values;
        },

        resetFormDataSpecifics: function()
        {
            this.formDataSpecifics = [];
        },

        //########################################

        run: function(categoryInfo, productDataNick)
        {
            this.specificsContainer.update();

            this.categoryInfo    = categoryInfo;
            this.productDataNick = productDataNick;

            this.initThemeAttributes();
            this.initSpecifics();
        },

        reset: function()
        {
            this.categoryInfo    = null;
            this.productDataNick = null;

            this.selectedSpecifics = {};
            this.renderedSpecifics = [];

            this.themeAttributes = {};

            this.specificsContainer.update();
        },

        isReady: function()
        {
            return this.categoryInfo != null && this.productDataNick != null;
        },

        //########################################

        initThemeAttributes: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_template_description/getVariationThemeAttributes'), {
                method: 'get',
                asynchronous: false,
                parameters: {
                    marketplace_id:    $('marketplace_id').value,
                    product_data_nick: self.productDataNick
                },
                onSuccess: function(transport) {
                    self.themeAttributes = transport.responseText.evalJSON();
                }
            });
        },

        initSpecifics: function()
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_template_description/getAllSpecifics'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    marketplace_id:     $('marketplace_id').value,
                    product_data_nick:  self.productDataNick
                },
                onSuccess: function(transport) {
                    try {
                        self.dictionaryHelper.setDictionarySpecifics(transport.responseText.evalJSON());

                        self.renderRootContainer();
                        self.renderRequiredSpecifics();
                        self.renderSpecifics(self.formDataSpecifics);

                    } catch (e) {
                        console.log(e.message);
                        console.log(e.stack);
                        self.alert(e.name + ': ' + e.message);
                    }
                }
            });
        },

        // ---------------------------------------

        renderRootContainer: function()
        {
            var specifics = this.dictionaryHelper.getChildSpecifics({'parent_specific_id': null});

            if (specifics.length <= 0) {
                return;
            }

            this.renderSpecific(specifics[0].xpath +'-1');
        },

        renderRequiredSpecifics: function()
        {
            var self      = this,
                specifics = [];

            if (this.categoryInfo.required_attributes.length <= 0) {
                return;
            }

            $H(this.categoryInfo.required_attributes).each(function(spInfo) {

                var xpath = spInfo.key;

                if (!xpath.match('/ProductData')) {
                    return false;
                }

                xpath = xpath.replace(/^\/ProductData\//, '').split('/');
                xpath = '/' + xpath.join('-1/');

                if (spInfo.value.length <= 0) {
                    specifics.push({xpath: xpath + '-1', value: ''});
                }

                spInfo.value.each(function(spValue, index) {
                    specifics.push({xpath: xpath + '-' + (index + 1), value: spValue});
                });
            });

            specifics.each(function(sp) {
                self.renderSpecific(sp.xpath, {force_value: sp.value});
            });
        },

        // ---------------------------------------

        renderSpecifics: function(specifics)
        {
            var self = this;

            if (specifics <= 0) {
                return '';
            }

            specifics.sort(function(spA, spB) {
                return spA.xpath == spB.xpath ? 0 : (spA.xpath > spB.xpath) ? 1 : -1;
            });

            specifics.each(function(specific) {
                self.renderSpecific(specific.xpath);
            });
        },

        renderSpecific: function(indexedXpath, params)
        {
            params = params || {};
            var renderer = null;

            var dictionarySpecific = this.dictionaryHelper.getDictionarySpecific(indexedXpath);
            if (!dictionarySpecific) {
                console.log('Specific not found in dictionary. ' + indexedXpath);
                return;
            }

            if (this.dictionaryHelper.isSpecificTypeContainer(dictionarySpecific)) {
                renderer = new AmazonTemplateDescriptionCategorySpecificBlockRenderer();
            }

            if (this.dictionaryHelper.isSpecificTypeSelect(dictionarySpecific) ||
                this.dictionaryHelper.isSpecificTypeText(dictionarySpecific)) {
                renderer = new AmazonTemplateDescriptionCategorySpecificGridRowRenderer();
            }

            try {
                renderer.setSpecificsHandler(this);
                renderer.setIndexedXpath(indexedXpath);
                renderer.setParams(params);

                renderer.process();
            } catch (e) {
                console.log(e.message);
                console.log(e.stack);
                this.alert(e.name + ' ' + e.message);
            }
        },

        //########################################

        isSpecificRendered: function(xPathWithIndex)
        {
            return (this.renderedSpecifics.indexOf(xPathWithIndex) >= 0);
        },

        markSpecificAsRendered: function(xPathWithIndex)
        {
            this.renderedSpecifics.push(xPathWithIndex);
        },

        unMarkSpecificAsRendered: function(xPathWithIndex)
        {
            var regexpObj = new RegExp('^' + xPathWithIndex);

            for (var i = 0; i < this.renderedSpecifics.length; i++) {

                if (this.renderedSpecifics[i].match(regexpObj)) {
                    this.renderedSpecifics.splice(i, 1);
                    i--;
                }
            }
        },

        getRealXpathesOfRenderedSpecifics: function()
        {
            var realRenderedXpathes = [];
            this.renderedSpecifics.each(function(sp) {

                var realPath = sp.replace(/\-\d*/g, '');
                if (realRenderedXpathes.indexOf(realPath) >= 0) {
                    return true;
                }

                realRenderedXpathes.push(realPath);
            });

            return realRenderedXpathes;
        },

        getXpathesOfTheSameRenderedSpecific: function(xPathWithIndex)
        {
            var xpathes = [];

            var tempXpath = xPathWithIndex.replace(/-\d+$/,'');
            this.renderedSpecifics.each(function(xPath) {

                if (tempXpath == xPath.replace(/-\d+$/, '')) {
                    xpathes.push(xPath);
                }
            });

            return xpathes;
        },

        getLatestXpathFromTheSame: function(xPathWithIndex)
        {
            var latestIndex = 0;

            this.getXpathesOfTheSameRenderedSpecific(xPathWithIndex).each(function(xpath) {
                var index = parseInt(xpath.replace(/^.*-/, ''));
                if (index > latestIndex) {
                    latestIndex = index;
                }
            });

            return xPathWithIndex.replace(/\d+$/, '') + latestIndex;
        },

        // ---------------------------------------

        markSpecificAsSelected: function(xPathWithIndex, data)
        {
            var self = this;

            if (!this.selectedSpecifics.hasOwnProperty(xPathWithIndex)) {
                this.selectedSpecifics[xPathWithIndex] = data;
                return '';
            }

            $H(data).each(function(dataItem) {
                self.selectedSpecifics[xPathWithIndex][dataItem.key] = dataItem.value;
            });
        },

        unMarkSpecificAsSelected: function(xPathWithIndex)
        {
            var self = this;
            var regexpObj = new RegExp('^' + xPathWithIndex);

            // for removing all child specifics
            $H(this.selectedSpecifics).each(function(el) {

                var specificKey = el.key;
                if (specificKey.match(regexpObj)) delete self.selectedSpecifics[specificKey];
            });
        },

        isMarkedAsSelected: function(xPathWithIndex)
        {
            return this.selectedSpecifics.hasOwnProperty(xPathWithIndex);
        },

        getSelectionInfo: function(xPathWithIndex)
        {
            if (this.isMarkedAsSelected(xPathWithIndex)) {
                return this.selectedSpecifics[xPathWithIndex];
            }

            return this.isInFormData(xPathWithIndex);
        },

        isInFormData: function(xPathWithIndex)
        {
            var searchResult = false;
            this.formDataSpecifics.each(function(el) {

                if (el.xpath == xPathWithIndex) {
                    searchResult = el;
                    return false;
                }
            });

            return searchResult;
        },

        // ---------------------------------------

        prepareSpecificsDataToPost: function()
        {
            this.specificsHiddenInput.value = Object.toJSON(this.selectedSpecifics);
        },

        //########################################

        canSpecificBeOverwrittenByVariationTheme: function(specific)
        {
            return typeof this.themeAttributes[specific.xml_tag] != 'undefined' &&
                this.themeAttributes[specific.xml_tag].length > 0;
        }

        // ---------------------------------------
    });
});