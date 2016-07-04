define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'underscore',
    'M2ePro/Attribute'
], function (jQuery, modal, _) {

    window.MagentoAttributeButton = Class.create();
    MagentoAttributeButton.prototype = {
        _id: '',
        _destinationId: '',
        _magentoAttributes: {},
        _selectCustomAttributes: {},
        _template: "<fieldset class='fieldset admin__fieldset  m2epro-fieldset'>" +
                   "<legend class='admin__legend legend'><span>&nbsp</span></legend><br>" +
                   "<div class='admin__field field'>" +
                   "<label class='label admin__field-label' for='<%= id %>'><span><%= title %></span></label> " +
                   "<div class='admin__field-control control'> <%= select%> </div></div></fieldset>",

        initialize: function() {
            if (typeof AttributeObj === 'undefined') {
                window.AttributeObj = new Attribute();
            }
        },

        setDestinationId: function(id)
        {
            this._destinationId = id;
            return this;
        },

        getDestinationId: function()
        {
            return this._destinationId;
        },

        setMagentoAttributes: function(magentoAttributes)
        {
            this._magentoAttributes = magentoAttributes;
            return this;
        },

        getMagentoAttributes: function()
        {
            return this._magentoAttributes;
        },

        setSelectCustomAttributes: function(selectCustomAttributes)
        {
            this._selectCustomAttributes = selectCustomAttributes;
            return this;
        },

        getSelectCustomAttributes: function()
        {
            return this._selectCustomAttributes;
        },

        init: function(element, callback)
        {
            var self = this;

            this._id = element.id;
            var popupElement = $('magento-attribute-button-popup'+self._id);

            if (!popupElement) {
                popupElement = new Element('div', {
                    id: 'magento-attribute-button-popup'+self._id
                })
            }

            this._renderMagentoAttributes(popupElement);

            var popup = jQuery(popupElement).modal({
                title: M2ePro.translator.translate(
                    "Insert Magento Attribute in %s%",
                    self._getFieldTitle(self.getDestinationId())
                ),
                type: 'popup',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    click: function () {
                        this.closeModal();
                    }
                },{
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'primary',
                    id: 'save_popup_button',
                    click: function () {
                        AttributeObj.appendToText(
                            'magento-attribute-select-'+self._id,
                            self.getDestinationId()
                        );
                        callback && callback();
                        this.closeModal();
                    }
                }]
            });

            popup.modal('openModal');
        },

        _getFieldTitle: function(elementId)
        {
            var label = $(elementId).up('div.admin__field.field').down('label span');
            return label ? label.innerHTML : '';
        },

        _renderMagentoAttributes: function(containerElement)
        {
            if (containerElement.down('select')) {
                return;
            }

            var select = new Element('select',
                _.extend({
                    id: 'magento-attribute-select-'+this._id,
                    class: 'select admin__control-select'
                }, this.getSelectCustomAttributes())
            );

            _.each(this.getMagentoAttributes(), function(obj) {
                var option = new Element('option', {
                    value: obj.value
                });
                option.innerHTML = obj.label;

                select.appendChild(option);
            });

            containerElement.innerHTML = _.template(this._template)( {
                id: 'magento-attribute-select-'+this._id,
                title: M2ePro.translator.translate('Magento Attribute'),
                select: select.outerHTML
            });
        }
    };
});