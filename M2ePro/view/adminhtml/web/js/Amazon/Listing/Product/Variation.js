define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Common'
], function (modal, MessageObj) {

    window.AmazonListingProductVariation = Class.create();
    AmazonListingProductVariation.prototype = Object.extend(new Common(), {

        // ---------------------------------------

        initialize: function (gridHandler) {
            this.gridHandler = gridHandler;
        },

        setListingProductId: function (listingProductId) {
            this.listingProductId = listingProductId;
            return this;
        },

        setNeededVariationData: function (variationAttributes, variationsTree) {
            this.variationAttributes = variationAttributes;
            this.variationsTree = variationsTree;

            return this;
        },

        //########################################

        showSwitchToIndividualModePopUp: function (title) {
            var self = this;

            if (self.switchToIndividualModePopUp) {
                self.switchToIndividualModePopUp.modal('openModal');
                return;
            }

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation/getSwitchToIndividualModePopUp'), {
                method: 'post',
                onSuccess: function (transport) {

                    $('html-body').insert({bottom: transport.responseText});

                    self.switchToIndividualModePopUp = jQuery('#switch_to_individual_popup');

                    modal({
                        title: M2ePro.translator.translate('switch_to_individual_mode_popup_title'),
                        type: 'popup',
                        modalClass: 'width-50',
                        buttons: [{
                            text: M2ePro.translator.translate('No'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.switchToIndividualModePopUp.modal('closeModal');
                            }
                        }, {
                            text: M2ePro.translator.translate('Yes'),
                            class: 'action-primary action-accept',
                            click: function () {
                                if ($('switch_to_individual_remember_checkbox').checked) {
                                    new Ajax.Request(M2ePro.url.get('saveListingAdditionalData'), {
                                        method: 'post',
                                        parameters: {
                                            param_name: 'hide_switch_to_individual_confirm',
                                            param_value: 1
                                        },
                                        onSuccess: function (transport) {
                                            self.gridHandler.unselectAllAndReload();
                                        }
                                    });
                                }

                                self.switchToIndividualModePopUp.modal('closeModal');
                                self.showManagePopup(title);
                            }
                        }]
                    }, self.switchToIndividualModePopUp);

                    self.switchToIndividualModePopUp.modal('openModal');
                }
            });
        },

        showSwitchToParentModePopUp: function () {
            var self = this;

            if (self.switchToParentModePopUp) {
                self.switchToParentModePopUp.modal('openModal');
                return;
            }

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation/getSwitchToParentModePopUp'), {
                method: 'post',
                onSuccess: function (transport) {

                    $('html-body').insert({bottom: transport.responseText});

                    self.switchToParentModePopUp = jQuery('#switch_to_parent_popup');

                    modal({
                        title: M2ePro.translator.translate('switch_to_parent_mode_popup_title'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('No'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.switchToParentModePopUp.modal('closeModal');
                            }
                        }, {
                            text: M2ePro.translator.translate('Yes'),
                            class: 'action-primary action-accept',
                            click: function () {
                                if ($('switch_to_parent_remember_checkbox').checked) {
                                    new Ajax.Request(M2ePro.url.get('saveListingAdditionalData'), {
                                        method: 'post',
                                        parameters: {
                                            param_name: 'hide_switch_to_parent_confirm',
                                            param_value: 1
                                        }
                                    });
                                }

                                self.switchToParentModePopUp.modal('closeModal');
                                self.resetListingProductVariation();
                            }
                        }]
                    }, self.switchToParentModePopUp);

                    self.switchToParentModePopUp.modal('openModal');
                }
            });
        },

        //########################################

        showEditPopup: function (popupTitle) {
            var self = this;

            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_individual/getEditPopup'), {
                method: 'get',
                parameters: {
                    listing_product_id: this.listingProductId
                },
                onSuccess: (function (transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        if (response.type == 'error') {
                            MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);
                            return;
                        }

                        var popupContent = $('variation_individual_edit_popup');

                        if (popupContent) {
                            popupContent.remove();
                        }

                        $('html-body').insert({bottom: response.html});

                        var variationForm = jQuery('#variation_edit_form').form();

                        self.editPopup = jQuery('#variation_individual_edit_popup');

                        modal({
                            title: popupTitle,
                            type: 'slide',
                            buttons: [{
                                text: M2ePro.translator.translate('Cancel'),
                                click: function () {
                                    self.editPopup.modal('closeModal');
                                }
                            },{
                                text: M2ePro.translator.translate('Confirm'),
                                class: 'action primary',
                                click: function () {
                                    if (!variationForm.valid()) {
                                        return;
                                    }

                                    var variationData = {};
                                    Form.getElements('variation_edit_form').each(function (selectElement) {
                                        var attribute = selectElement.readAttribute('name');
                                        selectElement.value && (variationData[attribute] = selectElement.value);
                                    });

                                    self.editAction(variationData);

                                    self.editPopup.modal('closeModal');
                                }
                            }]
                        }, self.editPopup);

                        self.editPopup.modal('openModal');

                    } catch (e) {
                        this.editPopup.modal('closeModal');
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }).bind(this)
            });
        },

        // ---------------------------------------

        editPopupInit: function (currentVariation) {
            var container = $('variation_edit_container');

            var filters = {};

            this.variationAttributes.each((function (attribute, i) {

                var tr = container.appendChild(new Element('tr'));
                tr.appendChild(new Element('td', {class: 'label', style: 'width: 25%; vertical-align: inherit;'}))
                    .insert(attribute + ': <span class="required">*</span>');

                var select = tr
                    .appendChild(new Element('td', {class: 'value'}))
                    .appendChild(new Element('select', {
                        name: 'variation_data[' + attribute + ']',
                        class: 'required-entry admin__control-select',
                        index: i
                    }));

                select
                    .appendChild(new Element('option', {value: currentVariation[attribute]}))
                    .insert(currentVariation[attribute]);

                this.eachAttributeHandler(
                    select,
                    i,
                    function () {
                        return container.select('select[index]').filter(function (select) {
                            return select.readAttribute('index') > i;
                        });
                    },
                    filters
                );

            }).bind(this));

            container.select('select[index]').each(function (select) {
                select.simulate('change');
            });
        },

        // ---------------------------------------

        resetListingProductVariation: function () {
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product/variationReset'), {
                method: 'get',
                parameters: {
                    listing_product_id: this.listingProductId
                },
                onSuccess: (function (transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);

                        this.gridHandler.unselectAllAndReload();
                    } catch (e) {
                        this.editPopup.modal('closeModal');
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }).bind(this)
            });
        },

        //########################################

        showManagePopup: function (popupTitle) {
            var self = this;

            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_individual/getManagePopup'), {
                method: 'get',
                parameters: {
                    listing_product_id: this.listingProductId
                },
                onSuccess: (function (transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        if (response.type == 'error') {
                            MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);
                            return;
                        }

                        var popupContent = $('variation_individual_manage_popup');

                        if (popupContent) {
                            popupContent.remove();
                        }

                        $('html-body').insert({bottom: response.html});

                        var variationForm = jQuery('#variation_manage_form');
                        self.initFormValidation(variationForm);

                        self.managePopup = jQuery('#variation_individual_manage_popup');

                        modal({
                            title: popupTitle,
                            type: 'slide',
                            buttons: [{
                                text: M2ePro.translator.translate('Cancel'),
                                click: function () {
                                    self.managePopup.modal('closeModal');
                                }
                            },{
                                text: M2ePro.translator.translate('Confirm'),
                                class: 'action primary',
                                click: function () {
                                    if (!variationForm.valid()) {
                                        return;
                                    }

                                    var variationData = {};
                                    Form.getElements('variation_manage_form').each(function (selectElement) {
                                        var attribute = selectElement.readAttribute('name');
                                        selectElement.value && (variationData[attribute] = selectElement.value);
                                    });

                                    self.manageAction(variationData);

                                    self.managePopup.modal('closeModal');
                                }
                            }]
                        }, self.managePopup);

                        self.managePopup.modal('openModal');

                        $('add_more_variation_button').observe('click', (function () {
                            if (!variationForm.valid()) {
                                return;
                            }
                            self.manageAddRow();
                        }));

                    } catch (e) {
                        self.managePopup.modal('closeModal');
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                })
            });
        },

        // ---------------------------------------

        managePopupInit: function()
        {
            $('add_more_variation_button').simulate('click');
        },

        // ---------------------------------------

        manageAddRow: function () {
            var container = $('variation_manage_tbody');

            var lastTr = container.select('tr').last();
            var index = lastTr ? parseInt(lastTr.readAttribute('index')) + 1 : 1;

            var tr = container.appendChild(new Element('tr', {index: index}));

            var filters = {};

            this.variationAttributes.each((function (attribute, i) {

                var select = tr
                    .appendChild(new Element('td', {style: 'vertical-align: top; padding: 5px 4px'}))
                    .appendChild(new Element('select', {
                        name: 'variation_data[' + index + '][' + attribute + ']',
                        class: 'required-entry admin__control-select',
                        style: 'width: 100%',
                        index: i,
                        disabled: true
                    }));

                this.eachAttributeHandler(
                    select,
                    i,
                    function () {
                        return tr.select('select[index]').filter(function (select) {
                            return select.readAttribute('index') > i;
                        });
                    },
                    filters
                );

            }).bind(this));

            tr.appendChild(new Element('td', {style: 'vertical-align: top; padding: 10px 4px'}))
                .appendChild(new Element('a', {class: 'remove-variation', href: 'javascript:void(0)'})).insert('<span></span>')
                .observe('click', function () {
                    if (container.select('tr').length > 1) {
                        tr.remove();
                    }

                    if (container.select('tr').length == 1) {
                        container.select('a.remove-variation').each(function (btn) {
                            btn.hide();
                        });
                    }
                });

            container.select('a.remove-variation').each(function (btn) {
                btn.hide();
            });

            if (container.select('tr').length > 1) {
                container.select('a.remove-variation').each(function (btn) {
                    btn.show();
                });
            }
        },

        //########################################

        eachAttributeHandler: function (select, i, getNextSelects, filters) {
            var attribute = this.variationAttributes[i];

            if (!i) {
                select.disabled = false;
                this.renderAttributeValues(select, attribute);
            }

            select.observe('change', (function () {
                filters[attribute] = select.value;

                var nextSelects = getNextSelects.call(this);

                if (nextSelects.length < 1) {
                    return;
                }

                nextSelects.each(function (select) {
                    select.disabled = true;
                });

                var nextSelect = nextSelects[0];

                nextSelect.disabled = false;

                this.renderAttributeValues(
                    nextSelect, this.variationAttributes[i + 1], filters
                );
            }).bind(this));
        },

        // ---------------------------------------

        renderAttributeValues: function (container, attribute, filters) {
            filters = filters || {};

            var values = this.getAttributeValues(attribute, this.variationsTree, filters);

            var oldValue = container.value;
            container.update();

            container.appendChild(new Element('option', {style: 'display: none'}));

            if (typeof values != 'undefined') {

                values.each(function (value) {
                    container.appendChild(new Element('option', {value: value})).insert(value);

                    if (value == oldValue) {
                        container.value = oldValue;
                        container.simulate('change');
                    }
                });
            }
        },

        // ---------------------------------------

        getAttributeValues: function (attribute, attributesTree, filters) {
            for (var treeAttribute in attributesTree) {

                if (attribute == treeAttribute) {

                    var values = [];
                    for (var value in attributesTree[treeAttribute]) {
                        value && values.push(value);
                    }

                    return values;
                }

                for (var filterAttribute in filters) {

                    if (filterAttribute == treeAttribute) {
                        return this.getAttributeValues(
                            attribute,
                            attributesTree[treeAttribute][filters[filterAttribute]],
                            filters
                        )
                    }
                }
            }
        },

        //########################################

        editAction: function (variationData) {
            MessageObj.clear();

            var parameters = Object.extend(
                {
                    listing_product_id: this.listingProductId
                },
                variationData
            );

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_individual/edit'), {
                method: 'post',
                parameters: parameters,
                onSuccess: (function (transport) {

                    try {
                        this.editPopup.modal('closeModal');

                        var response = transport.responseText.evalJSON();

                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);

                        if (response.type == 'error') {
                            this.scrollPageToTop();
                        } else {
                            this.gridHandler.unselectAllAndReload();
                        }

                    } catch (e) {
                        console.log(e.stack);
                        this.scrollPageToTop();
                        this.editPopup.modal('closeModal');
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }).bind(this)
            });
        },

        // ---------------------------------------

        manageAction: function (variationData) {
            MessageObj.clear();

            var parameters = Object.extend(
                {
                    listing_product_id: this.listingProductId
                },
                variationData
            );

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_individual/manage'), {
                method: 'post',
                parameters: parameters,
                onSuccess: (function (transport) {

                    try {
                        this.managePopup.modal('closeModal');

                        var response = transport.responseText.evalJSON();

                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1) + 'Message'](response.message);

                        if (response.type == 'error') {
                            this.scrollPageToTop();
                        } else {
                            this.gridHandler.unselectAllAndReload();
                        }

                    } catch (e) {
                        this.scrollPageToTop();
                        this.managePopup.modal('closeModal');
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }).bind(this)
            });
        },

        // ---------------------------------------

        manageGenerateAction: function (unique) {
            var self = this,
                attributesIndexes = {};

            $('variation_manage').select('th.data-grid-th').each(function (el, i) {
                attributesIndexes[el.readAttribute('attribute').toLowerCase()] = i;
            });

            new Ajax.Request(M2ePro.url.get('amazon_listing_product_variation_individual/generate'), {
                method: 'post',
                parameters: {
                    listing_product_id: self.listingProductId,
                    unique: +(unique)
                },
                onSuccess: function (transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        if (response.type == 'error') {
                            MessageObj.addErrorMessage(response.message);
                            self.managePopup.modal('closeModal');
                            return self.scrollPageToTop();
                        }

                        if (response.text.length < 1 && Boolean(unique)) {
                            self.alert(M2ePro.translator.translate('no_variations_left'));
                            return;
                        }

                        $('variation_manage_tbody').select('tr').invoke('remove');

                        response.text.each(function (attributes) {

                            self.manageAddRow();

                            var tr = $('variation_manage_tbody').select('tr').last();

                            var temp = [];

                            attributes.each(function (attribute) {
                                var index = attributesIndexes[attribute.attribute.toLowerCase()];
                                var select = tr.down('select[index=' + index + ']');
                                temp[index] = {select: select, value: attribute.option};
                            });

                            temp.each(function (obj) {
                                obj.select.value = obj.value;
                                obj.select.simulate('change');
                            });
                        });
                    } catch (e) {
                        self.scrollPageToTop();
                        self.managePopup.modal('closeModal');
                        MessageObj.addErrorMessage('Internal Error.');
                    }
                }
            });
        }

        // ---------------------------------------
    });

});