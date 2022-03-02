define([
    'jquery',
    'M2ePro/Grid',
    'Magento_Ui/js/modal/modal'
], function(jquery, grid, modal) {
    window.ControlPanelInspection = Class.create(Grid, {
        prepareActions: function()
        {
            this.actions = {
                checkAllAction: function() { this.checkAllAction(); }.bind(this),
                checkAction: function() { this.checkAction(); }.bind(this)
            }
        },

        showMetaData: function(element) {
            var content = '<div style="padding: 10px 10px; max-height: 450px; overflow: auto;">' +
                element.next().innerHTML +
                '</div>' +
                '<div style="text-align: right; padding-right: 10px; margin-top: 10px; margin-bottom: 5px;">' +
                '</div>';

            modal({
                title: 'Details',
                type: 'popup',
                modalClass: 'width-1000',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    class: 'action-secondary',
                    click: function() {
                        this.closeModal();
                    }
                }]
            }, content).openModal();

        },

        removeRow: function(element) {
            var form = element.up('form'),
                url = form.getAttribute('action'),
                data = Form.serialize(form);

            form.querySelectorAll("input:checked").forEach(function(element) {
                element.up('tr').remove();
            });

            new Ajax.Request(url, {
                method: 'post',
                asynchronous: true,
                parameters: data
            });
        },

        checkAction: function(nick = null, details = null) {
            if (!nick && !details) {
                var tr = event.target.closest("tr");

                nick = tr.querySelector(".id").textContent;
                details = tr.querySelector(".details");
            }

            new Ajax.Request(M2ePro.url.get('checkInspection'), {
                method: 'post',
                parameters: {
                    title: nick.trim()
                },
                asynchronous: true,
                onSuccess: function(transport) {
                    if (!transport.responseText.isJSON()) {
                        details.innerHTML = "<span style='color: red; font-weight: bold;'>Internal Error occured" +
                            " check system log</span>";
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    if (response) {
                        if (response['result'] === true) {
                            details.innerHTML = "<span style='color: green; font-weight: bold;'>" + response['message'] + "</span>";
                        } else {
                            details.innerHTML = "<span style='color: red; font-weight: bold;'>" + response['message'] + "</span>";
                            details.innerHTML += '&nbsp;&nbsp;\n' +
                                '<a href="javascript://"' +
                                ' onclick="ControlPanelInspectionObj.showMetaData(this);">details</a>\n' +
                                '<div class="no-display"><div>' + response['metadata'] + '</div></div>';
                        }
                    }
                }
            });
        },

        checkAllAction: function () {
            var selectedIds = this.getSelectedProductsArray(),
                rows = document.querySelector("#controlPanelInspectionsGrid_table").querySelectorAll(".id"),
                details;

            for (var row of rows) {
                for (var nick of selectedIds) {
                    if (row.textContent.trim() === nick) {
                        details = row.closest("tr").querySelector(".details");
                        this.checkAction(nick, details);
                        break;
                    }
                }
            }
        }
    });
});
