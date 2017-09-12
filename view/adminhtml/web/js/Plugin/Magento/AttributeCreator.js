define([
    'M2ePro/Plugin/Messages',
    'jquery',
    'Magento_Ui/js/modal/modal',
    'prototype'
], function (MessagesObj, jQuery, modal) {

    window.AttributeCreator = Class.create();
    AttributeCreator.prototype = {

        id: null,

        popupObj: null,
        selectObj: null,

        delayTimer: null,
        selectIndexBeforeCreation: 0,

        // it is for close callback [in order to rest selected option for selectObj]
        attributeWasCreated: false,

        formId: 'edit_form',
        modalId: 'modal_create_magento_attribute',
        addOptionValue: 'new-one-attribute',

        onSuccessCallback: null,
        onFailedCallback: null,

        // ---------------------------------------

        initialize: function (id)
        {
            id = 'AttributeCreator_' + id + '_Obj';

            this.id = id;
            window[id] = this;

            MessagesObj.setContainer('.modal-slide:has(#'+this.modalId+')');
        },

        // ---------------------------------------

        setSelectObj: function (selectObj)
        {
            this.selectObj = selectObj;
        },

        setSelectIndexBeforeCreation: function (index)
        {
            this.selectIndexBeforeCreation = index;
        },

        // ---------------------------------------

        setOnSuccessCallback: function (funct)
        {
            this.onSuccessCallback = funct;
        },

        setOnFailedCallback: function (funct)
        {
            this.onFailedCallback = funct;
        },

        // ---------------------------------------

        showPopup: function (params)
        {
            var self = this;
            params = params || {};

            if (self.selectObj && self.selectObj.getAttribute('allowed_attribute_types')) {
                params['allowed_attribute_types'] = self.selectObj.getAttribute('allowed_attribute_types');
            }

            if (self.selectObj && self.selectObj.getAttribute('apply_to_all_attribute_sets') == 'false') {
                params['apply_to_all_attribute_sets'] = '0';
            }

            params['handler_id'] = self.id;

            new Ajax.Request(M2ePro.url.get('general/getCreateAttributeHtmlPopup'), {
                method: 'post',
                asynchronous: true,
                parameters: params,
                onSuccess: function (transport) {

                    var modalDialogMessage = $(self.modalId);

                    if (!modalDialogMessage) {
                        modalDialogMessage = new Element('div', {
                            id: self.modalId
                        });
                    }

                    self.popupObj = jQuery(modalDialogMessage).modal({
                        title: M2ePro.translator.translate('Creation of New Magento Attribute'),
                        type: 'slide',
                        buttons: [
                            {
                                text: M2ePro.translator.translate('Cancel'),
                                attr: {id: 'magento_attribute_creation_cancel_button'},
                                class: 'action-dismiss',
                                click: function () {}
                            },{
                                text: M2ePro.translator.translate('Confirm'),
                                attr: {id: 'magento_attribute_creation_confirm_button'},
                                class: 'action primary',
                                click: function () {}
                            }
                        ],
                        closed: function() {
                            MessagesObj.clear();
                        }
                    });

                    var closeCallback = function (e) {
                        self.onClosePopupCallback();
                    };
                    self.popupObj.data().modal.modal.find('.action-close')
                        .off('click', closeCallback)
                        .on('click', closeCallback);

                    $('magento_attribute_creation_cancel_button')
                        .stopObserving()
                        .observe('click', function (e) {
                            self.onCancelPopupCallback();
                            self.popupObj.modal('closeModal');
                        });

                    $('magento_attribute_creation_confirm_button')
                        .stopObserving()
                        .observe('click', function (e) {
                            self.onOkPopupCallback();
                        });

                    self.popupObj.modal('openModal');
                    self.attributeWasCreated = false;

                    modalDialogMessage.innerHTML = transport.responseText;
                    modalDialogMessage.innerHTML.evalScripts();

                    modalDialogMessage.down('#store_label')
                        .stopObserving()
                        .observe('keyup', self.onChangeLabel.bind(self));
                    modalDialogMessage.down('#code')
                        .stopObserving()
                        .observe('change', self.onChangeCode.bind(self));
                }
            });
        },

        create: function (attributeParams)
        {
            var self = this;

            MessagesObj.clear();

            new Ajax.Request(M2ePro.url.get('general/createAttribute'), {
                method: 'post',
                asynchronous: true,
                parameters: attributeParams,
                onSuccess: function (transport) {

                    var result = transport.responseText.evalJSON();
                    if (!result || !result['result']) {

                        typeof self.onFailedCallback == 'function'
                            ? self.onFailedCallback.call(self, attributeParams, result)
                            : self.defaultOnFailedCallback(attributeParams, result);

                        return;
                    }

                    typeof self.onSuccessCallback == 'function'
                        ? self.onSuccessCallback.call(self, attributeParams, result)
                        : self.defaultOnSuccessCallback(attributeParams, result);

                    self.popupObj.modal('closeModal');
                    self.onClosePopupCallback();
                }
            });
        },

        // ---------------------------------------

        defaultOnSuccessCallback: function (attributeParams, result)
        {
            MessagesObj.addSuccessMessage(M2ePro.translator.translate('Attribute has been created.'));
            this.chooseNewlyCreatedAttribute(attributeParams, result);
        },

        defaultOnFailedCallback: function (attributeParams, result)
        {
            MessagesObj.addErrorMessage(result['error']);
            this.onCancelPopupCallback();
        },

        // ---------------------------------------

        onOkPopupCallback: function ()
        {
            if (!jQuery('#'+this.modalId+' #'+this.formId).validation().valid()) {
                return false;
            }

            this.create($$('#'+this.modalId+' #'+this.formId)[0].serialize(true));
            this.attributeWasCreated = true;

            return true;
        },

        onCancelPopupCallback: function ()
        {
            if (!this.selectObj) {
                return true;
            }

            this.selectObj.selectedIndex = this.selectIndexBeforeCreation;
            this.selectObj.simulate('change');

            return true;
        },

        onClosePopupCallback: function ()
        {
            if (this.attributeWasCreated || !this.selectObj) {
                return true;
            }

            this.onCancelPopupCallback();
            return true;
        },

        chooseNewlyCreatedAttribute: function (attributeParams, result)
        {
            var self = this;

            var newOption = new Element('option');

            if (this.haveOptgroup()) {
                newOption.setAttribute('attribute_code', attributeParams['code']);
            }

            newOption.update(attributeParams['store_label']);
            newOption.setAttribute('value', self.getNewlyCreatedAttributeValue(attributeParams));
            newOption.setAttribute('selected', 'selected');

            $$('select[id="' + self.selectObj.id + '"] option').each(function (el) {
                el.removeAttribute('selected');
            });

            var existedOptionsCollection = self.haveOptgroup() ? $$('select[id="' + self.selectObj.id + '"] optgroup.M2ePro-custom-attribute-optgroup option')
                : $$('select[id="' + self.selectObj.id + '"] option');

            var titles = [];
            existedOptionsCollection.each(function (el) {
                titles.push(trim(el.innerHTML));
            });

            titles.push(attributeParams['store_label']);
            titles.sort();

            var neededIndex = titles.indexOf(attributeParams['store_label']),
                beforeOptionTitle = titles[neededIndex - 1];

            existedOptionsCollection.each(function (el) {

                if (typeof beforeOptionTitle == 'undefined') {
                    $(el).insert({before: newOption});
                    throw $break;
                }

                if (trim(el.innerHTML) == beforeOptionTitle) {
                    $(el).insert({after: newOption});
                    throw $break;
                }
            });

            self.selectObj.simulate('change');
        },

        getNewlyCreatedAttributeValue: function (attributeParams)
        {
            if (!this.haveOptgroup()) {
                return attributeParams['code'];
            }

            var optGroupObj = $$('select[id="' + this.selectObj.id + '"] optgroup.M2ePro-custom-attribute-optgroup').first();

            if (optGroupObj.hasAttribute('new_option_value')) {
                return optGroupObj.getAttribute('new_option_value');
            }

            return $$('select[id="' + this.selectObj.id + '"] optgroup.M2ePro-custom-attribute-optgroup option').first().value;
        },

        // ---------------------------------------

        injectAddOption: function ()
        {
            var self = this;

            if (self.selectObj.getAttribute('option_injected')) {
                return;
            }

            // -- if select is empty -> inject each one empty option
            if ($$('select[id="' + self.selectObj.id + '"] option').length == 0) {

                self.selectObj.insertBefore(
                    new Element('option', {style: 'display: none;'}),
                    self.selectObj.firstChild
                );
            }
            // --

            var option = new Element('option', {
                style: 'color: brown;',
                value: this.addOptionValue
            }).update(M2ePro.translator.translate('Create a New One...'));

            if (self.haveOptgroup()) {
                $$('select[id="' + this.selectObj.id + '"] optgroup.M2ePro-custom-attribute-optgroup').first().appendChild(option);
            } else {
                self.selectObj.appendChild(option);
            }

            $(self.selectObj).observe('focus', function (event) {
                this.value != self.addOptionValue && self.setSelectIndexBeforeCreation(this.selectedIndex);
            });

            $(self.selectObj).observe('change', function (event) {

                this.value == self.addOptionValue
                    ? self.showPopup()
                    : self.setSelectIndexBeforeCreation(this.selectedIndex);
            });

            self.selectObj.setAttribute('option_injected' , '1');
        },

        validateAttributeCode: function (value, el)
        {
            if (!value.match(/^[a-z][a-z_0-9]{1,254}$/)) {
                return false;
            }

            return true;
        },

        validateAttributeCodeToBeUnique: function (value, el)
        {
            var result = false;

            new Ajax.Request(M2ePro.url.get('general/isAttributeCodeUnique'), {
                method: 'post',
                asynchronous: false,
                parameters: {
                    code: value
                },
                onSuccess: function (transport) {

                    var response = transport.responseText.evalJSON();
                    result = response.status;
                }
            });

            return result;
        },

        // ---------------------------------------

        onChangeCode: function (event)
        {
            if (!$('code').hasClassName('changed-by-user')) {
                $('code').addClassName('changed-by-user');
            }
        },

        onChangeLabel: function (event)
        {
            var self = this;

            if ($('code').hasClassName('changed-by-user')) {
                return;
            }

            self.delayTimer && clearTimeout(self.delayTimer);
            self.delayTimer = setTimeout(function () {
                self.updateCode(event.target.value);
            }, 600);
        },

        updateCode: function (label)
        {
            new Ajax.Request(M2ePro.url.get('general/generateAttributeCodeByLabel'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    store_label: label
                },
                onSuccess: function (transport) {
                    if ($('code').hasClassName('changed-by-user')) {
                        return;
                    }

                    $('code').value = transport.responseText;
                }
            });
        },

        // ---------------------------------------

        haveOptgroup: function ()
        {
            var obj = $$('select[id="' + this.selectObj.id + '"] optgroup.M2ePro-custom-attribute-optgroup').first();
            return typeof obj != 'undefined';
        },

        alreadyHaveAddedOption: function ()
        {
            var obj = $$('select[id="' + this.selectObj.id + '"] option[value="' + this.addOptionValue + '"]').first();
            return typeof obj != 'undefined';
        }

        // ---------------------------------------
    };

});