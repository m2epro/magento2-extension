define([
    'Magento_Ui/js/modal/alert'
], function (alert) {

    window.AmazonTemplateDescriptionCategorySpecificRenderer = Class.create();
    AmazonTemplateDescriptionCategorySpecificRenderer.prototype = {

        // ---------------------------------------

        MODE_NONE              : 'none',
        MODE_RECOMMENDED_VALUE : 'recommended_value',
        MODE_CUSTOM_VALUE      : 'custom_value',
        MODE_CUSTOM_ATTRIBUTE  : 'custom_attribute',

        // ---------------------------------------

        indexedXPath : null,
        params       : {},

        specific       : null,
        parentSpecific : null,

        specificHandler  : null,
        dictionaryHelper : null,

        // ---------------------------------------

        initialize: function() {},

        //########################################

        load: function()
        {
            this.dictionaryHelper = this.specificHandler.dictionaryHelper;

            this.specific = this.dictionaryHelper.getDictionarySpecific(this.indexedXPath);

            if (this.specific === null) {
                alert({
                    content: 'Unable to load Dictionary Specific.'
                });
                return false;
            }

            this.parentSpecific = this.dictionaryHelper.getParentSpecific(this.specific);

            return true;
        },

        // ---------------------------------------

        setSpecificsHandler: function(handler)
        {
            this.specificHandler = handler;
        },

        setIndexedXpath: function(xPath)
        {
            this.indexedXPath = xPath;
        },

        setParams: function(params)
        {
            this.params = params;
        },

        //########################################

        getRootIndexedXpath: function()
        {
            var splitedPath = this.indexedXPath.replace(/^\//, '').split('/');
            return '/' + splitedPath[0];
        },

        getParentIndexedXpath: function()
        {
            if (this.specific.parent_specific_id === null) {
                return null;
            }

            var splitedPath = this.indexedXPath.replace(/^\//, '').split('/');
            splitedPath.pop();
            return '/' + splitedPath.join('/');
        },

        getChildIndexedPart: function(childSpecific)
        {
            return this.indexedXPath + '/' + childSpecific.xml_tag + '-1'
        },

        //########################################

        isValueForceSet: function()
        {
            return this.getForceSetValue() !== false;
        },

        getForceSetValue: function()
        {
            return this.params.hasOwnProperty('force_value') ? this.params['force_value'] : false;
        },

        //########################################

        isRemoveActionAllowed: function()
        {
            if (!this.specificHandler.isSpecificRendered(this.indexedXPath)) {
                return false;
            }

            return !this.isValueForceSet() &&
                this.specificHandler.getXpathesOfTheSameRenderedSpecific(this.indexedXPath).length > this.specific.min_occurs;
        },

        getRemoveButton: function()
        {
            var button = new Element('a', {
                'id'          : this.indexedXPath + '_remove_button',
                'indexedxpath': this.indexedXPath,
                'href'        : 'javascript:void(0);',
                'class'       : 'specific-remove-button',
                'style'       : 'vertical-align: middle;',
                'title'       : M2ePro.translator.translate('Delete specific')
            });
            button.observe('click', this.removeAction.bind(this));

            if (!this.isRemoveActionAllowed()) {
                this.hideButton(button);
            }

            return button;
        },

        removeAction: function(event)
        {
            if (!this.isRemoveActionAllowed()) {
                return null;
            }

            this.specificHandler.unMarkSpecificAsRendered(this.indexedXPath);
            this.specificHandler.unMarkSpecificAsSelected(this.indexedXPath);

            $(this.indexedXPath).remove();

            // make the latest clone button visible again
            var theSameSpecifics = this.specificHandler.getXpathesOfTheSameRenderedSpecific(this.indexedXPath);
            if (theSameSpecifics.length < this.specific.max_occurs) {

                var latestIndexedXpath = this.specificHandler.getLatestXpathFromTheSame(this.indexedXPath);

                var previousCloneButton = $(latestIndexedXpath + '_clone_button');
                previousCloneButton && this.showButton(previousCloneButton);
            }

            return this.indexedXPath;
        },

        // ---------------------------------------

        isCloneActionAllowed: function()
        {
            return this.specificHandler.isSpecificRendered(this.indexedXPath) &&
                this.specificHandler.getXpathesOfTheSameRenderedSpecific(this.indexedXPath).length < this.specific.max_occurs;
        },

        getCloneButton: function()
        {
            var button = new Element('a', {
                'id'          : this.indexedXPath + '_clone_button',
                'indexedxpath': this.indexedXPath,
                'href'        : 'javascript:void(0);',
                'class'       : 'specific-clone-button',
                'style'       : 'vertical-align: middle;',
                'title'       : M2ePro.translator.translate('Duplicate specific')
            });
            button.observe('click', this.cloneAction.bind(this));

            if (!this.isCloneActionAllowed()) {
                this.hideButton(button);
            }

            return button;
        },

        cloneAction: function(event)
        {
            if (!this.isCloneActionAllowed()) {
                return null;
            }

            this.hideButton(event.target);

            var newIndexedXpath = this.indexedXPath.replace(/\d$/, '');
            newIndexedXpath += parseInt(this.indexedXPath.match(/\d$/)[0]) + 1;

            this.specificHandler.renderSpecific(newIndexedXpath);
            return newIndexedXpath;
        },

        // ---------------------------------------

        hideButton: function(button)
        {
            button = (typeof button == 'object') ? button : $(this.indexedXPath + '_' + button + '_button');
            button.style.display = 'none';
        },

        showButton: function(button)
        {
            button = (typeof button == 'object') ? button : $(this.indexedXPath + '_' + button + '_button');
            button.style.display = 'inline-block';
        },

        // ---------------------------------------

        touchMyNeighbors: function()
        {
            var theSameSpecifics = this.specificHandler.getXpathesOfTheSameRenderedSpecific(this.indexedXPath);
            if (theSameSpecifics.length <= 0) {
                return;
            }

            var latestIndexedXpath = this.specificHandler.getLatestXpathFromTheSame(this.indexedXPath),
                myEvent = new CustomEvent('my-duplicate-is-rendered');

            theSameSpecifics.each(function(xpath) {
                latestIndexedXpath != xpath && $(xpath).dispatchEvent(myEvent);
            });
        },

        // ---------------------------------------

        onMyDuplicateRendered: function()
        {
            this.hideButton('clone');
        },

        onWhenUndeletebleSpecificAppears: function()
        {
            this.hideButton('remove');

            if (this.getParentIndexedXpath() != null) {
                var myEvent = new CustomEvent('undeleteble-specific-appear');
                $(this.getParentIndexedXpath()).dispatchEvent(myEvent);
            }
        },

        // ---------------------------------------

        // my awesome firefox... simulate('change') does not work is element has a 'disabled' attribute
        simulateAction: function(obj, action)
        {
            if (obj.hasAttribute('disabled')) {
                obj.removeAttribute('disabled');
                obj.simulate(action);
                obj.setAttribute('disabled', 'disabled');
            } else {
                obj.simulate(action);
            }
        }

        // ---------------------------------------
    };
});