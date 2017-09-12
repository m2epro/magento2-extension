define([
    'M2ePro/Plugin/Confirm',
    'M2ePro/Plugin/Alert',
    'prototype',
    'jquery/validate',
    'mage/backend/form',
    'mage/backend/validation'
], function (confirm, alert) {

    window.Common = Class.create();
    Common.prototype = {

        // ---------------------------------------

        initialize: function() {},

        // ---------------------------------------

        initCommonValidators: function()
        {
            var self = this;
            jQuery.validator.addMethod('M2ePro-required-when-visible', function(value, el) {

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                if(typeof value === 'string' ) {
                    value = value.trim();
                }

                return value != null && value.length > 0;
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-required-when-visible-and-enabled', function(value, el) {

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                if (!$(el).disabled) {
                    return true;
                }

                return value != null && value.length > 0;
            }, M2ePro.translator.translate('This is a required field.'));

            jQuery.validator.addMethod('M2ePro-validation-float', function(value, element) {

                if (!element.visible()) {
                    return true;
                }

                if (element.parentNode && !element.parentNode.visible()) {
                    return true;
                }

                if (element.up('tr') && !element.up('tr').visible()) {
                    return true;
                }

                if (element.up('.entry-edit') && !element.up('.entry-edit').visible()) {
                    return true;
                }

                if (value == '') {
                    return true;
                }

                return value.match(/^\d+[.]?\d*?$/g);
            }, M2ePro.translator.translate('Invalid input data. Decimal value required. Example 12.05'));

            jQuery.validator.addMethod('M2ePro-store-switcher-validation', function(value, element) {
                if (!element.visible()) {
                    return true;
                }

                if (!element.up('.admin__field').visible()) {
                    return true;
                }

                if (value == -1 || value == null) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('You should select Store View'));

            jQuery.validator.addMethod('M2ePro-validate-email', function(value, el) {
                this.error = Validation.get('validate-email').error;
                return Validation.get('validate-email').test(value,el);
            }, M2ePro.translator.translate('Email is not valid.'));
        },

        initFormValidation: function (selector)
        {
            selector = selector || 'form';

            jQuery(selector).each(function (index, form) {
                jQuery(form).form().validation();
            });
        },

        isElementHiddenFromPage: function(el)
        {
            var hidden = !$(el).visible();

            while (!hidden) {
                el = $(el).up();
                hidden = !el.visible();
                if ($(el).up() == document || el.hasClassName('entry-edit')) {
                    break;
                }
            }

            return hidden;
        },

        // ---------------------------------------

        scrollPageToTop: function()
        {
            if (location.href[location.href.length-1] != '#') {
                setLocation(location.href+'#');
            } else {
                setLocation(location.href);
            }
        },

        backClick: function(url)
        {
            setLocation(url.replace(/#$/, ''));
        },

        // ---------------------------------------

        saveClick: function(url, skipValidation)
        {
            if (typeof skipValidation == 'undefined' && !this.isValidForm()) {
                return;
            }

            if (typeof url == 'undefined' || url == '') {
                url = M2ePro.url.get('formSubmit', {'back': base64_encode('list')});
            }
            this.submitForm(url);
        },

        saveAndEditClick: function(url, tabsId, skipValidation)
        {
            if (typeof skipValidation == 'undefined' && !this.isValidForm()) {
                return;
            }

            if (typeof url == 'undefined' || url == '') {

                var tabsUrl = '';
                if (typeof tabsId != 'undefined') {
                    tabsUrl = '|tab=' + jQuery('#' + tabsId).data().tabs.active.find('a').attr('name');
                }

                url = M2ePro.url.get('formSubmit', {'back': base64_encode('edit' + tabsUrl)});
            }
            this.submitForm(url);
        },

        // ---------------------------------------

        duplicateClick: function($headId, chapter_when_duplicate_text)
        {
            $$('.loading-mask').invoke('show');

            M2ePro.url.add({'formSubmit': M2ePro.url.get('formSubmitNew')});

            $('title').value = '';

            $$('.page-title').each(function(o) { o.innerHTML = chapter_when_duplicate_text; });
            $$('.M2ePro_duplicate_button').each(function(o) { o.hide(); });
            $$('.M2ePro_delete_button').each(function(o) { o.hide(); });

            window.setTimeout(function() {
                $$('.loading-mask').invoke('hide')
            }, 1200);
        },

        deleteClick: function()
        {
            this.confirm({
                actions: {
                    confirm: function () {
                        setLocation(M2ePro.url.get('deleteAction'));
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        isValidForm: function()
        {
            return jQuery('#edit_form').valid();
        },

        submitForm: function(url, newWindow)
        {
            if (typeof newWindow == 'undefined') {
                newWindow = false;
            }

            var form = $('edit_form');

            var oldAction = form.action;

            form.action = url;
            form.target = newWindow ? '_blank' : '_self';

            form.submit();

            form.action = oldAction;
        },

        postForm: function(url, params)
        {
            var form = new Element('form', {'method': 'post', 'action': url});

            $H(params).each(function(i) {
                form.insert(new Element('input', {'name': i.key, 'value': i.value, 'type': 'hidden'}));
            });

            form.insert(new Element('input', {'name': 'form_key', 'value': FORM_KEY, 'type': 'hidden'}));

            $(document.body).insert(form);

            // chrome ugly hack
            setTimeout(form.submit.bind(form), 250);
        },

        // ---------------------------------------

        openWindow: function(url)
        {
            var w = window.open(url);
            w.focus();
            return w;
        },

        // ---------------------------------------

        updateHiddenValue : function(elementMode, elementHidden)
        {
            elementHidden.value = elementMode.options[elementMode.selectedIndex].getAttribute('attribute_code');
        },

        hideEmptyOption: function(select)
        {
            $(select).select('.empty') && $(select).select('.empty').length && $(select).select('.empty')[0].hide();
        },

        setRequiried: function(el)
        {
            $(el).addClassName('required-entry');
        },

        setNotRequiried: function(el)
        {
            $(el) && $(el).removeClassName('required-entry');
        },

        // ---------------------------------------

        setConstants: function(data)
        {
            //data = eval(data);
            //for (var i=0;i<data.length;i++) {
            //    eval('this.'+data[i][0]+'=\''+data[i][1]+'\'');
            //}
        },

        setValidationCheckRepetitionValue: function(idInput, textError, model, dataField, idField, idValue, component, filterField, filterValue)
        {
            component = component || null;
            filterField = filterField || null;
            filterValue = filterValue || null;

            jQuery.validator.addMethod(
                idInput, function(value) {
                    var checkResult = false;

                    new Ajax.Request(M2ePro.url.get('general/validationCheckRepetitionValue'), {
                        method: 'post',
                        asynchronous: false,
                        parameters: {
                            model: model,
                            data_field: dataField,
                            data_value: value,
                            id_field: idField,
                            id_value: idValue,
                            component: component,
                            filter_field: filterField,
                            filter_value: filterValue
                        },
                        onSuccess: function(transport) {
                            checkResult = transport.responseText.evalJSON()['result'];
                        }
                    });

                    return checkResult;
                }, textError
            );
        },

        // ---------------------------------------

        autoHeightFix: function()
        {
            setTimeout(function() {
                Windows.getFocusedWindow().content.style.height = '';
                Windows.getFocusedWindow().content.style.maxHeight = '650px';
            }, 50);
        },

        // ---------------------------------------

        updateFloatingHeader: function ()
        {
            var data = jQuery('.page-actions').data('floatingHeader');

            if (typeof data === 'undefined') {
                return;
            }

            data._offsetTop = data._placeholder.offset().top;
        },

        // ---------------------------------------

        confirm: function (config)
        {
            confirm(config);
        },

        // ---------------------------------------

        alert: function (text, callback)
        {
            alert({
                actions: {
                    cancel: callback || function() {}
                },
                content: text
            });
        },

        // ---------------------------------------

        bindEventAtFirstPosition: function(domElement, eventName, callable)
        {
            // bind as you normally would
            jQuery(domElement).bind(eventName, callable);

            var handlers = jQuery(domElement).data('events')[eventName.split('.')[0]];
            var handler = handlers.pop();

            handlers.splice(0, 0, handler);
        }

        // ---------------------------------------
    }
});