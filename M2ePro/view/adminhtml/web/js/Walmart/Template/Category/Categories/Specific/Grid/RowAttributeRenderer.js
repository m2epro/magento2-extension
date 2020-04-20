define([
           'M2ePro/Walmart/Template/Category/Categories/Specific/Grid/RowRenderer'
       ], function () {

    window.WalmartTemplateCategoryCategoriesSpecificGridRowAttributeRenderer = Class.create(WalmartTemplateCategoryCategoriesSpecificGridRowRenderer, {

        // ---------------------------------------

        attribute      : null,
        attributeIndex : null,

        // ---------------------------------------

        process: function()
        {
            if (!this.load()) {
                return '';
            }

            this.renderSelf();
            this.observeToolTips(this.indexedXPath);

            this.checkSelection();
        },

        load: function($super)
        {
            try {
                var values = this.attribute.hasOwnProperty('values') ? this.attribute.values : '[]';
                JSON.parse(values);
                this.attribute.values = values.evalJSON();
            } catch (e) {}

            return $super();
        },

        //########################################

        renderSelf: function()
        {
            this.renderLabel();
            this.renderChooseMode();
            this.renderValueInputs();
            this.renderButtons();

            $(this.indexedXPath).observe('parent-specific-row-is-cloned', this.onParentClonedAction.bind(this));
            $(this.indexedXPath).observe('parent-specific-row-is-removed', this.onParentRemovedAction.bind(this));
        },

        // ---------------------------------------

        renderLabel: function()
        {
            var td = new Element('td');
            var title = this.specific.title + ' (' + this.attribute.title + ')';

            if (this.isAttributeRequired()) {
                title += ' <span class="required">*</span>';
            }

            td.appendChild((new Element('span').insert(title)));

            var note = this.getDefinitionNote(this.attribute.data_definition);
            if (note) {
                var toolTip = this.getToolTipBlock(this.indexedXPath + '_attribute_' + this.attribute.title + '_definition_note', note);
                toolTip.show();
                td.appendChild(toolTip);
            }

            this.getRowContainer().appendChild(td);
        },

        // ---------------------------------------

        renderChooseMode: function()
        {
            var select = new Element('select', {
                'id'          : this.indexedXPath + '_attribute_' + this.attribute.title + '_mode',
                'indexedxpath': this.indexedXPath,
                'class'       : 'select admin__control-select ' + (this.isAttributeRequired() ? 'M2ePro-required-when-visible' : ''),
                'style'       : 'width: 85%;'
            });

            select.appendChild(new Element('option', {'style': 'display: none'}));
            select.appendChild(new Element('option', {'value': this.MODE_CUSTOM_VALUE})).insert(M2ePro.translator.translate('Custom Value'));
            select.appendChild(new Element('option', {'value': this.MODE_CUSTOM_ATTRIBUTE})).insert(M2ePro.translator.translate('Custom Attribute'));

            select.observe('change', this.onChangeChooseMode.bind(this));

            this.getRowContainer().appendChild(new Element('td')).appendChild(select);
        },

        onChangeChooseMode: function(event)
        {
            var customAttribute     = $(this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_ATTRIBUTE),
                customAttributeNote = $(this.indexedXPath + '_attribute_' + this.attribute.title + '_custom_attribute_note');

            var customValue      = $(this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_VALUE),
                customValueNote  = $(this.indexedXPath + '_attribute_' + this.attribute.title + '_custom_value_note');

            customAttribute     && customAttribute.hide();
            customAttributeNote && customAttributeNote.hide();

            customValue     && customValue.hide();
            customValueNote && customValueNote.hide();

            if (event.target.value == this.MODE_CUSTOM_VALUE) {
                customValue     && customValue.show();
                customValueNote && customValueNote.show();
            }
            if (event.target.value == this.MODE_CUSTOM_ATTRIBUTE) {
                customAttribute     && customAttribute.show();
                customAttributeNote && customAttributeNote.show();
            }
        },

        // ---------------------------------------

        renderValueInputs: function()
        {
            var td = this.getRowContainer().appendChild(new Element('td'));

            if (this.isAttributeTypeText()) {

                var note = this.getCustomValueTypeNote();
                if (note) td.appendChild(this.getToolTipBlock(this.indexedXPath + '_attribute_' + this.attribute.title + '_custom_value_note', note));

                td.appendChild(this.getTextTypeInput());
            }

            if (this.isAttributeTypeSelect()) {
                td.appendChild(this.getSelectTypeInput());
            }

            // ---------------------------------------
            var note = this.getCustomAttributeTypeNote();
            if (note) {
                var tooltip = td.appendChild(this.getToolTipBlock(this.indexedXPath + '_attribute_' + this.attribute.title + '_custom_attribute_note', note));
                tooltip.hide();
            }

            td.appendChild(this.getCustomAttributeSelect());
            // ---------------------------------------
        },

        getTextTypeInput: function()
        {
            var input = new Element('input', {
                'id'              : this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_VALUE,
                'name'            : this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_VALUE,
                'indexedxpath'    : this.indexedXPath,
                'specific_id'     : this.specific.specific_id,
                'attribute_index' : this.attributeIndex,
                'mode'            : this.MODE_CUSTOM_VALUE,
                'type'            : 'text',
                'class'           : 'input-text admin__control-text M2ePro-specificAttributes-validation ' + (this.isAttributeRequired() ? ' M2ePro-required-when-visible' : ''),
                'style'           : 'display: none; width: 85%;'
            });

            input.observe('change', this.onChangeValue.bind(this));
            return input;
        },

        getSelectTypeInput: function()
        {
            var select = new Element('select', {
                'id'              : this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_VALUE,
                'name'            : this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_VALUE,
                'indexedxpath'    : this.indexedXPath,
                'specific_id'     : this.specific.specific_id,
                'attribute_index' : this.attributeIndex,
                'mode'            : this.MODE_CUSTOM_VALUE,
                'class'           : 'select admin__control-select ' + (this.isAttributeRequired() ? 'M2ePro-required-when-visible' : ''),
                'style'           : 'width: 85%; display: none;'
            });

            select.appendChild(new Element('option', {'style': 'display: none'}));

            this.attribute.values.each(function(value) {
                select.appendChild(new Element('option', {'value': value}).insert(value));
            });

            select.observe('change', this.onChangeValue.bind(this));
            return select;
        },

        getCustomAttributeSelect: function()
        {
            var select = new Element('select', {
                'id'              : this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_ATTRIBUTE,
                'name'            : this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_ATTRIBUTE,
                'indexedxpath'    : this.indexedXPath,
                'specific_id'     : this.specific.specific_id,
                'attribute_index' : this.attributeIndex,
                'mode'            : this.MODE_CUSTOM_ATTRIBUTE,
                'class'           : 'attributes M2ePro-required-when-visible select admin__control-select',
                'style'           : 'display: none; width: 85%;',
                'apply_to_all_attribute_sets' : '0'
            });

            select.appendChild(new Element('option', {'style': 'display: none', 'value': ''}));
            this.attributeHandler.availableAttributes.each(function(el) {
                select.appendChild(new Element('option', {'value': el.code})).insert(el.label);
            });
            select.value = '';

            select.observe('change', this.onChangeValue.bind(this));

            var handlerObj = new AttributeCreator(select.id);
            handlerObj.setSelectObj(select);
            handlerObj.injectAddOption();

            return select;
        },

        onChangeValue: function(event)
        {
            var selectedObj = {};

            selectedObj.attributes = [];
            selectedObj.attributes[this.attributeIndex] = {};
            selectedObj.attributes[this.attributeIndex][this.attribute.title] = {};

            var mode = event.target.getAttribute('mode');

            selectedObj.attributes[this.attributeIndex][this.attribute.title]['mode'] = mode;
            selectedObj.attributes[this.attributeIndex][this.attribute.title][mode]   = event.target.value;

            this.specificHandler.markSpecificAsSelected(this.indexedXPath, selectedObj);
        },

        // ---------------------------------------

        renderButtons: function()
        {
            this.getRowContainer().appendChild(new Element('td'));
        },

        //########################################

        checkSelection: function()
        {
            var self = this;

            // specific selection info
            var selectionInfo = this.specificHandler.getSelectionInfo(this.indexedXPath);

            // try to find in formdata
            if (!selectionInfo.hasOwnProperty('attributes') || selectionInfo['attributes'].length <= 0) {
                selectionInfo = this.specificHandler.isInFormData(this.indexedXPath);
            }

            if (!selectionInfo.hasOwnProperty('attributes') || selectionInfo['attributes'].length <= 0) {
                return '';
            }

            // find attribute selection. brr.
            var attributeSelectionInfo = null;
            selectionInfo['attributes'].each(function(el) {

                if (el.hasOwnProperty(self.attribute.title)) {
                    attributeSelectionInfo = el[self.attribute.title];
                    return false;
                }
            });

            if (attributeSelectionInfo == null) {
                return '';
            }

            var id = this.indexedXPath + '_attribute_' + this.attribute.title + '_mode';
            $(id).value = attributeSelectionInfo.mode;
            this.simulateAction($(id), 'change');

            if (attributeSelectionInfo.mode == this.MODE_CUSTOM_VALUE) {
                id = this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_VALUE;
                $(id).value = attributeSelectionInfo['custom_value'];
                this.simulateAction($(id), 'change');
            }

            if (attributeSelectionInfo.mode == this.MODE_CUSTOM_ATTRIBUTE) {
                id = this.indexedXPath + '_attribute_' + this.attribute.title + '_' + this.MODE_CUSTOM_ATTRIBUTE;
                $(id).value = attributeSelectionInfo['custom_attribute'];
                this.simulateAction($(id), 'change');
            }
        },

        //########################################

        getCustomValueTypeNote: function()
        {
            if (this.attribute.data_definition.definition) {
                return null;
            }

            if (this.attribute.type == 'int') return this.getIntTypeNote(this.attribute);
            if (this.attribute.type == 'float') return this.getFloatTypeNote(this.attribute);
            if (this.attribute.type == 'string') return this.getStringTypeNote(this.attribute);
            if (this.attribute.type == 'date_time') return this.getDatTimeTypeNote(this.attribute);

            return this.getAnyTypeNote(this.attribute);
        },

        getCustomAttributeTypeNote: function()
        {
            if (this.attribute.values.length <= 0) {
                return null;
            }

            var span = new Element('span');

            span.appendChild(new Element('span')).insert('<b>' + M2ePro.translator.translate('Allowed Values') + ': </b>');

            var ul = span.appendChild(new Element('ul'));

            this.attribute.values.each(function(value) {
                ul.appendChild(new Element('li')).insert(value);
            });

            return span.outerHTML;
        },

        //########################################

        isAttributeRequired: function()
        {
            return this.attribute.required;
        },

        isAttributeTypeText: function()
        {
            return !this.isAttributeTypeSelect();
        },

        isAttributeTypeSelect: function()
        {
            return this.attribute.values && this.attribute.values.length > 0;
        },

        //########################################

        observeToolTips: function(indexedXpath)
        {
            // $$('tr[class="' + indexedXpath + '_attributes"] .tool-tip-image').each(function(element) {
            //     element.observe('mouseover', MagentoFieldTipObj.showToolTip);
            //     element.observe('mouseout', MagentoFieldTipObj.onToolTipIconMouseLeave);
            // });
            //
            // $$('tr[class="' + indexedXpath + '_attributes"] .tool-tip-message').each(function(element) {
            //     element.observe('mouseout', MagentoFieldTipObj.onToolTipMouseLeave);
            //     element.observe('mouseover', MagentoFieldTipObj.onToolTipMouseEnter);
            // });
        },

        //########################################

        onParentClonedAction: function(event)
        {
            this.observeToolTips(event.new_indexed_xpath);
        },

        onParentRemovedAction: function(event)
        {
            var attributesRows = $$('tr[class="' + this.indexedXPath + '_attributes"]');
            attributesRows.invoke('remove');
        },

        //########################################

        getRowContainer: function()
        {
            var id = this.indexedXPath + '_attribute_' + this.attribute.title;

            if ($(id)) {
                return $(id);
            }

            var grid = $$('table[id="'+ this.getParentIndexedXpath() +'_grid"] table.border tbody').first();
            return grid.appendChild(new Element('tr', {
                id    : id,
                class : this.indexedXPath + '_attributes'
            }));
        }

        // ---------------------------------------
    });
});