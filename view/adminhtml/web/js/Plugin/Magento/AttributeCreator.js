define([], function () {

    window.AttributeCreator = Class.create();
    AttributeCreator.prototype = {

        id: null,

        popupObj: null,
        selectObj: null,

        delayTimer: null,
        selectIndexBeforeCreation: 0,

        // it is for close callback [in order to rest selected option for selectObj]
        attributeWasCreated: false,

        formId: 'general_create_new_attribute_form',
        addOptionValue: 'new-one-attribute',

        onSuccessCallback: null,
        onFailedCallback: null,

        // ---------------------------------------

        initialize: function (id) {

            id = 'AttributeCreator_' + id + '_Obj';

            this.id = id;
            window[id] = this;
        },

        // ---------------------------------------

        setSelectObj: function (selectObj) {
            this.selectObj = selectObj;
        },

        setSelectIndexBeforeCreation: function (index) {
            this.selectIndexBeforeCreation = index;
        },

        // ---------------------------------------

        setOnSuccessCallback: function (funct) {
            this.onSuccessCallback = funct;
        },

        setOnFailedCallback: function (funct) {
            this.onFailedCallback = funct;
        },

        // ---------------------------------------

        showPopup: function (params) {
            var self = this;
            params = params || {};

            if (self.selectObj && self.selectObj.getAttribute('allowed_attribute_types')) {
                params['allowed_attribute_types'] = self.selectObj.getAttribute('allowed_attribute_types');
            }

            if (self.selectObj && self.selectObj.getAttribute('apply_to_all_attribute_sets') == '0') {
                params['apply_to_all_attribute_sets'] = '0';
            }

            params['handler_id'] = self.id;

            new Ajax.Request(M2ePro.url.get('adminhtml_general/getCreateAttributeHtmlPopup'), {
                method: 'post',
                asynchronous: true,
                parameters: params,
                onSuccess: function (transport) {

                    self.popupObj = Dialog.info(null, {
                        draggable: true,
                        resizable: true,
                        closable: true,
                        className: "magento",
                        windowClassName: "popup-window",
                        title: M2ePro.translator.translate('Creation of New Magento Attribute'),
                        top: 50,
                        maxHeight: 520,
                        width: 560,
                        zIndex: 100,
                        hideEffect: Element.hide,
                        showEffect: Element.show,
                        onOk: function () {
                            return self.onOkPopupCallback();
                        },
                        onCancel: function () {
                            return self.onCancelPopupCallback();
                        },
                        onClose: function () {
                            return self.onClosePopupCallback();
                        }
                    });

                    self.attributeWasCreated = false;
                    self.popupObj.options.destroyOnClose = true;
                    self.autoHeightFix();

                    $('modal_dialog_message').insert(transport.responseText);
                    $('modal_dialog_message').evalScripts();
                }
            });
        },

        create: function (attributeParams) {
            var self = this;

            MagentoMessageObj.clearAll();

            new Ajax.Request(M2ePro.url.get('adminhtml_general/createAttribute'), {
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
                }
            });
        },

        // ---------------------------------------

        defaultOnSuccessCallback: function (attributeParams, result) {
            MagentoMessageObj.addSuccess(M2ePro.translator.translate('Attribute has been created.'));
            this.chooseNewlyCreatedAttribute(attributeParams, result);
        },

        defaultOnFailedCallback: function (attributeParams, result) {
            MagentoMessageObj.addError(result['error']);
            this.onCancelPopupCallback();
        },

        // ---------------------------------------

        onOkPopupCallback: function () {
            if (!new varienForm(this.formId).validate()) {
                return false;
            }

            this.create($(this.formId).serialize(true));
            this.attributeWasCreated = true;

            return true;
        },

        onCancelPopupCallback: function () {
            if (!this.selectObj) {
                return true;
            }

            this.selectObj.selectedIndex = this.selectIndexBeforeCreation;
            this.selectObj.simulate('change');

            return true;
        },

        onClosePopupCallback: function () {
            if (this.attributeWasCreated || !this.selectObj) {
                return true;
            }

            this.onCancelPopupCallback();
            return true;
        },

        chooseNewlyCreatedAttribute: function (attributeParams, result) {
            var self = this;

            var newOption = new Element('option');

            if (this.haveOptgroup()) {
                newOption.addClassName('simple_mode_disallowed');
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

        getNewlyCreatedAttributeValue: function (attributeParams) {
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

        injectAddOption: function () {
            var self = this;

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
        },

        validateAttributeCode: function (value, el) {
            if (!value.match(/^[a-z][a-z_0-9]{1,254}$/)) {
                return false;
            }

            return true;
        },

        validateAttributeCodeToBeUnique: function (value, el) {
            var result = false;

            new Ajax.Request(M2ePro.url.get('adminhtml_general/isAttributeCodeUnique'), {
                method: 'post',
                asynchronous: false,
                parameters: {
                    code: value
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        return;
                    }

                    result = transport.responseText.evalJSON();
                }
            });

            return result;
        },

        // ---------------------------------------

        onChangeCode: function (event) {
            if (!$('code').hasClassName('changed-by-user')) {
                $('code').addClassName('changed-by-user');
            }
        },

        onChangeLabel: function (event) {
            var self = this;

            if ($('code').hasClassName('changed-by-user')) {
                return;
            }

            self.delayTimer && clearTimeout(self.delayTimer);
            self.delayTimer = setTimeout(function () {
                self.updateCode(event.target.value);
            }, 600);
        },

        updateCode: function (label) {
            new Ajax.Request(M2ePro.url.get('adminhtml_general/generateAttributeCodeByLabel'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    store_label: label
                },
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {
                        return;
                    }

                    if ($('code').hasClassName('changed-by-user')) {
                        return;
                    }

                    $('code').value = transport.responseText.evalJSON();
                }
            });
        },

        // ---------------------------------------

        autoHeightFix: function () {
            setTimeout(function () {
                Windows.getFocusedWindow().content.style.height = '';
                Windows.getFocusedWindow().content.style.maxHeight = '650px';
            }, 50);
        },

        haveOptgroup: function () {
            var obj = $$('select[id="' + this.selectObj.id + '"] optgroup.M2ePro-custom-attribute-optgroup').first();
            return typeof obj != 'undefined';
        },

        alreadyHaveAddedOption: function () {
            var obj = $$('select[id="' + this.selectObj.id + '"] option[value="' + this.addOptionValue + '"]').first();
            return typeof obj != 'undefined';
        }

        // ---------------------------------------
    };

});