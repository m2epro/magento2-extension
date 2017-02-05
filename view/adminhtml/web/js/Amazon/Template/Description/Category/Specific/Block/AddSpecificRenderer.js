define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Amazon/Template/Description/Category/Specific/Renderer'
], function (jQuery, modal) {

    window.AmazonTemplateDescriptionCategorySpecificBlockGridAddSpecificRenderer = Class.create(AmazonTemplateDescriptionCategorySpecificRenderer, {

        // ---------------------------------------

        childAllSpecifics  : [],
        childRowsSpecifics : [],

        selectedSpecifics : [],

        // ---------------------------------------

        process: function()
        {
            if (!this.load()) {
                return '';
            }

            this.prepareDomStructure();
            this.tuneStyles();
        },

        // ---------------------------------------

        prepareDomStructure: function()
        {
            this.getContainer().appendChild(this.getTemplate());

            $(this.indexedXPath + '_add_row').observe('child-specific-rendered' , this.onChildSpecificRendered.bind(this));
            $(this.indexedXPath).observe('child-specific-rendered', this.onChildSpecificRendered.bind(this));

            var addSpecificBlockButtonObj = $(this.indexedXPath + '_add_button');
            addSpecificBlockButtonObj.observe('click', this.addSpecificAction.bind(this));
        },

        // ---------------------------------------

        onChildSpecificRendered: function()
        {
            this.tuneStyles();

            if (this.indexedXPath == this.getRootIndexedXpath()) {
                return;
            }

            var myEvent = new CustomEvent('child-specific-rendered');

            $(this.getParentIndexedXpath() + '_add_row') && $(this.getParentIndexedXpath() + '_add_row').dispatchEvent(myEvent);
            $(this.getParentIndexedXpath()) && $(this.getParentIndexedXpath()).dispatchEvent(myEvent);
        },

        // ---------------------------------------

        tuneStyles: function()
        {
            var addSpecificsRowObj        = $(this.indexedXPath + '_add_row'),
                addSpecificBlockButtonObj = $(this.indexedXPath + '_add_button');

            // ---------------------------------------
            addSpecificsRowObj && addSpecificsRowObj.show();

            if ((this.getRootIndexedXpath() != this.indexedXPath && this.isAnyOfChildSpecificsRendered()) ||
                this.isAllOfSpecificsRendered())
            {
                addSpecificsRowObj && addSpecificsRowObj.hide();
            }
            // ---------------------------------------

            // ---------------------------------------
            addSpecificBlockButtonObj && addSpecificBlockButtonObj.show();

            if (this.getRootIndexedXpath() == this.indexedXPath || this.isAllOfSpecificsRendered() || !this.isAnyOfChildSpecificsRendered()) {
                addSpecificBlockButtonObj && addSpecificBlockButtonObj.hide();
            }
            // ---------------------------------------
        },

        isAnyOfChildSpecificsRendered: function()
        {
            var countOfRenderedSpecifics = 0;
            var realRenderedXpathes = this.specificHandler.getRealXpathesOfRenderedSpecifics();

            this.childAllSpecifics.each(function(sp) {
                if (realRenderedXpathes.indexOf(sp.xpath) >= 0) countOfRenderedSpecifics++;
            });

            return countOfRenderedSpecifics > 0;
        },

        isAllOfSpecificsRendered: function()
        {
            var countOfRenderedSpecifics = 0;

            var realRenderedXpathes = this.specificHandler.getRealXpathesOfRenderedSpecifics(),
                allChildSpecifics   = this.specificHandler.dictionaryHelper.getAllChildSpecifics(this.specific);

            allChildSpecifics.each(function(sp) {
                if (realRenderedXpathes.indexOf(sp.xpath) >= 0) countOfRenderedSpecifics++;
            });

            return countOfRenderedSpecifics == allChildSpecifics.length;
        },

        //########################################

        isAlreadyRendered: function()
        {
            return $(this.indexedXPath + '_add_row') == null;
        },

        getTemplate: function()
        {
            var template = $('specifics_add_row_template').down('table').cloneNode(true);

            template.down('button.add_custom_specific_button').observe('click', this.addSpecificAction.bind(this));
            template.setAttribute('id', this.indexedXPath + '_add_row');

            return template;
        },

        getContainer: function()
        {
            return $(this.indexedXPath);
        },

        // POPUP
        //########################################

        addSpecificAction: function(event)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_template_description/getAddSpecificsHtml'), {
                method: 'post',
                parameters: {
                    marketplace_id        : $('marketplace_id').value,
                    product_data_nick     : $('product_data_nick').value,
                    current_indexed_xpath : self.indexedXPath,
                    rendered_specifics    : Object.toJSON(self.specificHandler.renderedSpecifics)
                },
                onSuccess: function(transport) {

                    self.selectedSpecifics = [];
                    self.openPopUp(transport.responseText);
                }
            });
        },

        openPopUp: function(html)
        {
            var self = this,
                doneCallback = self.specificsDoneButton.bind(self);

            if (!M2ePro.popUp) {
                var modalDialogMessage = new Element('div', {
                    id: 'modal_dialog_message_specifics'
                });

                modalDialogMessage.insert(html);
                modalDialogMessage.innerHTML.evalScripts();

                M2ePro.popUp = jQuery(modalDialogMessage).modal({
                    title: modalDialogMessage.down('#specific_popup_title').value,
                    type: 'slide',
                    buttons: [{
                        text: M2ePro.translator.translate('Cancel'),
                        //class: 'action-primary',
                        click: function () {
                            this.closeModal();
                        }
                    },{
                        text: M2ePro.translator.translate('Confirm'),
                        class: 'action-primary',
                        id: 'save_popup_button',
                        click: function () {
                            doneCallback();
                            this.closeModal();
                        }
                    }]
                });
            } else {
                modalDialogMessage = $('modal_dialog_message_specifics');
                modalDialogMessage.innerHTML = '';

                modalDialogMessage.insert(html);
                modalDialogMessage.innerHTML.evalScripts();
            }

            M2ePro.popUp.modal('openModal');

            self.observePopupButtonsActions();
            self.observePopupGridRowsActions();
            self.tunePopupStyles();
        },

        observePopupButtonsActions: function()
        {
            $$('#modal_dialog_message_specifics button.specifics_filter_button').first().observe('click', this.specificsFilterButton.bind(this));
            $$('#modal_dialog_message_specifics button.specifics_reset_filter_button').first().observe('click', this.specificsResetFilterButton.bind(this));
            $$('#modal_dialog_message_specifics a.specifics_reset_selected_button').first().observe('click', this.specificsResetSelectedButton.bind(this));

            $$('#modal_dialog_message_specifics #query').first().observe('keypress', this.specificsKeyPressQuery.bind(this));
        },

        observePopupGridRowsActions: function()
        {
            var self = this;

            $$('#specifics_grid_container a.specific_search_result_row').each(function(el) {
                el.stopObserving('click');
                el.observe('click', self.specificsSelectRow.bind(self));
            });
        },

        tunePopupStyles: function()
        {
            $$('#amazonTemplateDescriptionCategorySpecificAddGrid div.grid th').each(function(el) {
                el.style.padding = '1px 1px';
            });

            $$('#amazonTemplateDescriptionCategorySpecificAddGrid div.grid td').each(function(el) {
                el.style['padding'] = '1px 1px';
                el.style['vertical-align'] = 'middle';
            });
        },

        //########################################

        specificsDoneButton: function(event)
        {
            var self = this;

            self.selectedSpecifics.each(function(indexedXpath) {
                self.specificHandler.renderSpecific(indexedXpath);
            });
        },

        specificsFilterButton: function(event)
        {
            this.reloadSearchingGrid(jQuery('#query', M2ePro.popUp).val(), +jQuery('#only_desired').prop('checked'));
        },

        specificsKeyPressQuery: function(event)
        {
            if (event.keyCode == 13) {
                this.specificsFilterButton(event);
            }
        },

        specificsResetFilterButton: function(event)
        {
            jQuery('#query', M2ePro.popUp).val('').focus();
            jQuery('#only_desired').prop('checked', false);

            this.reloadSearchingGrid('', '');
        },

        specificsResetSelectedButton: function(event)
        {
            var selectedSpecificsBox = $('selected_specifics_box');

            selectedSpecificsBox.update('');
            $('selected_specifics_container').hide();

            $('specifics_grid_container').style.height = '370px';

            this.selectedSpecifics = [];
            this.specificsFilterButton(event);
        },

        specificsSelectRow: function(event)
        {
            var selectedSpecificsBox = $('selected_specifics_box'),
                newIndexedXpath     = this.getNewSpecificXpath(event.target.getAttribute('xpath'));

            selectedSpecificsBox.appendChild(new Element('span', {
                    class   : 'selected-specific-box-item',
                    xml_tag : event.target.getAttribute('xml_tag'),
                    xpath   : newIndexedXpath
                }))
                .update('<div>'+event.target.getAttribute('xml_tag')+'</div>')
                .appendChild(new Element('a', {
                    href  : 'javascript:void(0);',
                    class : 'remove-link-button',
                    align : 'center',
                    title : M2ePro.translator.translate('Remove this specific')
                }))
                .observe('click', this.specificsUnSelectRow.bind(this));

            $('selected_specifics_container').show();
            $('specifics_grid_container').style.height = (370 - $('selected_specifics_container').offsetHeight - 6) + 'px';

            this.selectedSpecifics.push(newIndexedXpath);
            this.specificsFilterButton(event);
        },

        specificsUnSelectRow: function(event)
        {
            var newPreparedXpath = event.target.up('span').getAttribute('xpath');

            event.target.up('span').remove();

            var index = this.selectedSpecifics.indexOf(newPreparedXpath);
            index >= 0 && this.selectedSpecifics.splice(index, 1);

            if (this.selectedSpecifics.length <= 0) {
                return this.specificsResetSelectedButton(event);
            }

            this.specificsFilterButton(event);
        },

        // ---------------------------------------

        reloadSearchingGrid: function(query, onlyDesired)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('amazon_template_description/getAddSpecificsGridHtml'), {
                method: 'post',
                parameters: {
                    marketplace_id        : $('marketplace_id').value,
                    product_data_nick     : $('product_data_nick').value,
                    current_indexed_xpath : self.indexedXPath,
                    rendered_specifics    : Object.toJSON(self.specificHandler.renderedSpecifics),
                    selected_specifics    : Object.toJSON(self.selectedSpecifics),
                    only_desired          : onlyDesired,
                    query                 : query
                },
                onSuccess: function(transport) {
                    $('specifics_grid_container').down('div.grid-wrapper').update(transport.responseText);
                    self.tunePopupStyles();
                    self.observePopupGridRowsActions();
                }
            });
        },

        //########################################

        getNewSpecificXpath: function(dictionaryXpath)
        {
            var currentRealXpath = this.indexedXPath.replace(/-\d+/g, '');
            var newIndexedXpath  = '';

            var temp = dictionaryXpath.replace(currentRealXpath + '/', '');

            temp.split('/').each(function(pathPart) {
                newIndexedXpath += '/' + pathPart + '-1';
            });

            return this.indexedXPath + newIndexedXpath;
        }

        // ---------------------------------------
    });
});