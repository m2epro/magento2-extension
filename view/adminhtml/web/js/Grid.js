define([
    'M2ePro/Common'
], function () {

    window.Grid = Class.create(Common, {

        // ---------------------------------------

        initialize: function(gridId)
        {
            this.gridId = gridId;
            this.prepareActions();
        },

        // ---------------------------------------

        afterInitPage: function()
        {
            var submitButton = $$('#'+this.gridId+'_massaction-form button');

            submitButton.each((function(s) {
                s.writeAttribute("onclick",'');
                s.observe('click', (function() {
                    this.massActionSubmitClick();
                }).bind(this));
            }).bind(this));
        },

        // ---------------------------------------

        getGridObj: function()
        {
            return window[this.gridId + 'JsObject'];
        },

        getGridMassActionObj: function()
        {
            return window[this.gridId + '_massactionJsObject'];
        },

        // ---------------------------------------

        getCellContent: function(rowId,cellIndex)
        {
            var rows = this.getGridObj().rows;

            for(var i=0;i<rows.length;i++) {
                var row = rows[i];
                var cells = $(row).childElements();

                var checkbox = $(cells[0]).select('input');
                checkbox = checkbox[0];

                if (checkbox.value == rowId) {
                    return trim(cells[cellIndex].innerHTML);
                }
            }

            return '';
        },

        // ---------------------------------------

        getProductNameByRowId: function(rowId)
        {
            var cellContent = this.getCellContent(rowId,this.productTitleCellIndex);
            var expr = new RegExp(/<span[^>]*>(.*?)<\/span>/i);
            var matches = expr.exec(cellContent);

            return (matches && !Object.isUndefined(matches[1])) ? matches[1] : '';
        },

        // ---------------------------------------

        selectAll: function()
        {
            this.getGridMassActionObj().selectAll();
        },

        unselectAll: function()
        {
            this.getGridMassActionObj().unselectAll();
        },

        unselectAllAndReload: function()
        {
            this.unselectAll();
            this.getGridObj().reload();
        },

        // ---------------------------------------

        selectByRowId: function(rowId)
        {
            this.unselectAll();

            var rows = this.getGridObj().rows;
            for(var i=0;i<rows.length;i++) {
                var row = rows[i];
                var cells = $(row).childElements();

                var checkbox = $(cells[0]).select('input');
                checkbox = checkbox[0];

                if (checkbox.value == rowId) {
                    checkbox.checked = true;
                    this.getGridMassActionObj().checkedString = rowId.toString();
                    break;
                }
            }
        },

        // ---------------------------------------

        getSelectedProductsString: function()
        {
            return this.getGridMassActionObj().checkedString
        },

        getSelectedProductsArray: function()
        {
            return this.getSelectedProductsString().split(',');
        },

        getOrderedSelectedProductsArray: function()
        {
            var selectedProductsArray = this.getSelectedProductsArray();
            var checkboxesValuesArray = this.getGridMassActionObj().getCheckboxesValuesAsString().split(',');

            var orderedSelectedProductArray = [];

            checkboxesValuesArray.forEach(function (value) {
                if (selectedProductsArray.indexOf(value) >= 0) {
                    orderedSelectedProductArray.push(value);
                }
            });

            return orderedSelectedProductArray;
        },

        // ---------------------------------------

        massActionSubmitClick: function()
        {
            var self = this;

            if (self.getSelectedProductsString() == '' || self.getSelectedProductsArray().length == 0) {
                self.alert(M2ePro.translator.translate('Please select Items.'));
                return;
            }

            var selectAction = true;
            $$('select#'+self.gridId+'_massaction-select option').each(function(o) {
                if (o.selected && o.value == '') {
                    self.alert(M2ePro.translator.translate('Please select Action.'));
                    selectAction = false;
                    return;
                }
            });

            if (!selectAction) {
                return;
            }

            self.scrollPageToTop();

            self.confirm({
                actions: {
                    confirm: function () {
                        $$('select#'+self.gridId+'_massaction-select option').each(function(o) {

                            if (!o.selected) {
                                return;
                            }

                            if (!o.value || !self.actions[o.value + 'Action']) {
                                self.alert(M2ePro.translator.translate('Please select Action.'));
                                return;
                            }

                            self.actions[o.value + 'Action']();

                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        viewItemHelp: function(rowId, data, hideViewLog)
        {
            $('grid_help_icon_open_'+rowId).hide();
            $('grid_help_icon_close_'+rowId).show();

            if ($('grid_help_content_'+rowId) != null) {
                $('grid_help_content_'+rowId).show();
                return;
            }

            var html = this.createHelpTitleHtml(rowId);

            var synchNote = $('synch_template_list_rules_note_'+rowId);
            if (synchNote) {
                html += this.createSynchNoteHtml(synchNote.innerHTML)
            }

            data = eval(base64_decode(data));
            for (var i=0;i<data.length;i++) {
                html += this.createHelpActionHtml(data[i]);
            }

            if (!hideViewLog) {
                html += this.createHelpViewAllLogHtml(rowId);
            }

            var rows = this.getGridObj().rows;
            for(var i=0;i<rows.length;i++) {
                var row = rows[i];
                var cels = $(row).childElements();

                var checkbox = $(cels[0]).childElements();
                checkbox = checkbox[0].firstElementChild;

                if (checkbox.value == rowId) {
                    row.insert({
                      after: '<tr id="grid_help_content_'+rowId+'" class="grid_help_content"><td class="help_line" colspan="'+($(row).childElements().length)+'">'+html+'</td></tr>'
                    });
                }
            }
            var self = this;
            $('hide_item_help_' + rowId).observe('click', function() {
                self.hideItemHelp(rowId);
            });
        },

        hideItemHelp: function(rowId)
        {
            if ($('grid_help_content_'+rowId) != null) {
                $('grid_help_content_'+rowId).hide().remove();
            }

            $('grid_help_icon_open_'+rowId).show();
            $('grid_help_icon_close_'+rowId).hide();
        },

        // ---------------------------------------

        createHelpTitleHtml: function(rowId)
        {
            var productTitle = this.getProductNameByRowId(rowId);
            var closeHtml = '<a href="javascript:void(0);" id="hide_item_help_' + rowId + '" title="'+M2ePro.translator.translate('Close')+'"><span class="hl_close icon-close"></span></a>';
            return '<div class="hl_header"><span class="hl_title">'+productTitle+'</span>'+closeHtml+'</div>';
        },

        createSynchNoteHtml: function(synchNote)
        {
                return '<div class="messages m2epro-messages a-left">' +
                    '<div class="message message-warning error">' + synchNote+
                    '</div></div></div>';
        },

        createHelpActionHtml: function(action)
        {
            var self = this;
            var classContainer = 'hl_container';

            if (action.type == M2ePro.php.constant('Ess_M2ePro_Model_Log_AbstractModel::TYPE_SUCCESS')) {
                classContainer += ' hl_container_success';
            } else if (action.type == M2ePro.php.constant('Ess_M2ePro_Model_Log_AbstractModel::TYPE_WARNING')) {
                classContainer += ' hl_container_warning';
            } else if (action.type == M2ePro.php.constant('Ess_M2ePro_Model_Log_AbstractModel::TYPE_ERROR')) {
                classContainer += ' hl_container_error';
            } else {
                classContainer += ' hl_container_notice';
            }

            var html = '<div class="'+classContainer+'">';
                html += '<div class="hl_date">'+action.date+'</div>' +
                        '<div class="hl_action">';

            if (action.initiator != '') {
                html += '<strong style="color: gray;">'+action.initiator+'</strong>&nbsp;&nbsp;';
            }

            html += '<strong>'+action.action+'</strong>';

            if(action.action_in_progress) {
                html += '<span style="color: gray"> (' + M2ePro.translator.translate('In Progress') + ')</span>';
            }

            html += '</div>' +
                '<div style="clear: both"></div>' +
                '<div style="padding-top: 3px;">';

            for (var i=0;i<action.items.length;i++) {

                var type;

                if (action.items[i].type == M2ePro.php.constant('Ess_M2ePro_Model_Log_AbstractModel::TYPE_SUCCESS')) {
                    if (action.items[i].count) {
                        type = '<span style="color: green;"> ' + action.items[i].count + ' ' +
                            M2ePro.translator.translate('Product(s)') +
                        '</span>';
                    } else {
                        type = '<span style="color: green;">' +
                            M2ePro.translator.translate('Success') +
                        '</span>';
                    }
                } else if (action.items[i].type == M2ePro.php.constant('Ess_M2ePro_Model_Log_AbstractModel::TYPE_WARNING')) {
                    if (action.items[i].count) {
                        type = '<span style="color: orange;"> ' + action.items[i].count + ' ' +
                            M2ePro.translator.translate('Product(s)') +
                        '</span>';
                    } else {
                        type = '<span style="color: orange;">' +
                            M2ePro.translator.translate('Warning') +
                        '</span>';
                    }
                } else if (action.items[i].type == M2ePro.php.constant('Ess_M2ePro_Model_Log_AbstractModel::TYPE_ERROR')) {
                    if (action.items[i].count) {
                        type = '<span style="color: red;"> ' + action.items[i].count + ' ' +
                            M2ePro.translator.translate('Product(s)') +
                        '</span>';
                    } else {
                        type = '<span style="color: red;">' +
                            M2ePro.translator.translate('Error') +
                        '</span>';
                    }
                } else {
                    if (action.items[i].count) {
                        type = '<span style="color: #444;"> ' + action.items[i].count + ' ' +
                            M2ePro.translator.translate('Product(s)') +
                            '</span>';
                    } else {
                        type = '<span style="color: #444;">' +
                            M2ePro.translator.translate('Notice') +
                            '</span>';
                    }
                }

                var description = action.items[i].description;
                if (description.indexOf('code:64') !== -1) {
                    description = description.replace(/^(.*?)(\(code\:64,\s*amount_due\:(.*?)\s*,\s*currency\:(.*?)\s*)(\))(.*?)$/gi,
                        '$1$2, <a href="#" onclick="EbayListingTransferringPaymentHandlerObj.payNowAction(\'$3\', \'$4\', \'\')">Add Funds</a>$5$6'

                    );
                }

                html += '<div style="margin-top: 7px;"><div class="hl_messages_type">'+type+'</div><div class="hl_messages_text">'+description+'</div></div>';
            }

            html +=     '</div>' +
                    '</div>';

            return html;
        },

        // ---------------------------------------

        createHelpViewAllLogHtml: function(rowId)
        {
            return '<div class="hl_footer"><a target="_blank" href="'+this.getLogViewUrl(rowId)+'">'+
                   M2ePro.translator.translate('View Full Product Log')+
                   '</a></div>';
        },

        // ---------------------------------------

        getLogViewUrl: function(rowId)
        {
            alert('abstract getLogViewUrl');
        },

        // ---------------------------------------

        prepareActions: function()
        {
            alert('abstract prepareActions');
        },

        // ---------------------------------------

        getComponent: function()
        {
            alert('abstract getComponent');
        }

        // ---------------------------------------
    });

});
