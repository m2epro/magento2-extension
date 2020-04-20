define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Listing/View/Action'
], function (jQuery, Modal, MessageObj) {

    window.EbayListingPickupStoreGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        popup: null,
        variationPopup: null,
        logPopup: null,

        // ---------------------------------------

        pickupStoreStepProducts: function(listingId, callback)
        {
            var self = this;
            this.getGridMassActionObj().unselectAll();

            new Ajax.Request(M2ePro.url.get('*/productsStep'), {
                method: 'post',
                parameters: {
                    id: listingId
                },
                onSuccess: function(transport) {

                    self.openPopUp(
                        M2ePro.translator.translate('Assign Products to Stores'),
                        transport.responseText
                    );

                    var closeBtn = $('assign_close_button');
                    closeBtn.removeClassName('back');
                    self.updateButtonText(closeBtn, 'Cancel');
                    closeBtn.stopObserving().observe('click', function() {
                        self.popup.modal('closeModal');
                    });

                    var continueBtn = $('assign_done_button');
                    continueBtn.addClassName('forward');
                    self.updateButtonText(continueBtn, 'Continue');
                    continueBtn.stopObserving().observe('click', function() {
                        var checkedIds = EbayListingPickupStoreStepProductsGridObj.getCheckedValues();
                        if (checkedIds == '') {
                            self.alert('Please select Items.');
                            return;
                        }

                        self.pickupStoreStepStores(listingId);
                    });

                    callback && callback();
                }
            });
        },

        pickupStoreStepStores: function(listingId)
        {
            var self = this;
            new Ajax.Request(M2ePro.url.get('*/storesStep'), {
                method: 'post',
                parameters: {
                    id: listingId
                },
                onSuccess: function(transport) {

                    self.openPopUp(
                        M2ePro.translator.translate('Assign Products to Stores'),
                        transport.responseText
                    );

                    var backBtn = $('assign_close_button');
                    backBtn.addClassName('back');
                    self.updateButtonText(backBtn, 'Back');
                    backBtn.stopObserving().observe('click', function(e) {
                        e.preventDefault();
                        var checked = EbayListingPickupStoreStepProductsGridObj.getCheckedValues();
                        self.pickupStoreStepProducts(listingId, function() {
                            var gridMassAcction = EbayListingPickupStoreStepProductsGridObj.getGridMassActionObj();

                            gridMassAcction.setCheckedValues(checked);
                            gridMassAcction.checkCheckboxes();
                        });
                    });

                    var completeBtn = $('assign_done_button');
                    completeBtn.removeClassName('forward');
                    self.updateButtonText(completeBtn, 'Complete');
                    completeBtn.stopObserving().observe('click', function(e) {
                        e.preventDefault();
                        var productsIds = EbayListingPickupStoreStepProductsGridObj.getCheckedValues(),
                            storesIds = EbayListingPickupStoreStepStoresGridObj.getCheckedValues();

                        if (productsIds == '' || storesIds == '') {
                            self.alert('Please select Stores.');
                            return;
                        }

                        self.completeStep(productsIds, storesIds);
                    });
                }
            });
        },

        completeStep: function (productsIds, storesIds)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('*/assign'), {
                method: 'post',
                parameters: {
                    products_ids: productsIds,
                    stores_ids: storesIds
                },
                onSuccess: function(transport) {

                    if (!transport.responseText.isJSON()) {
                        self.alert(transport.responseText);
                        return;
                    }

                    self.getGridMassActionObj().unselectAll();
                    self.getGridObj().doFilter();

                    var response = transport.responseText.evalJSON();

                    MessageObj.clear();
                    response.messages.each(function(msg) {
                        MessageObj['add' + msg.type[0].toUpperCase() + msg.type.slice(1)+'Message'](msg.text);
                    });

                    self.popup.modal('closeModal');
                }
            });
        },

        // ---------------------------------------

        openPopUp: function(title, content)
        {
            var self = this;

            var modalDialogMessage = $('pickup_store_popup');
            if (!modalDialogMessage) {
                modalDialogMessage = new Element('div', {
                    id: 'pickup_store_popup'
                });

                this.popup = jQuery(modalDialogMessage).modal({
                    title: title,
                    type: 'slide',
                    buttons: [{
                        text: M2ePro.translator.translate('Cancel'),
                        attr: {id: 'assign_close_button'},
                        class: 'action-default action-dismiss',
                        click: function () {}
                    },{
                        text: M2ePro.translator.translate('Continue'),
                        attr: {id: 'assign_done_button'},
                        class: 'action-primary action-accept forward',
                        click: function () {}
                    }],
                    closed: function() {
                        self.selectedProductsIds = [];
                        self.selectedCategoriesData = {};
                    }
                });
            }

            this.popup.modal('openModal');

            modalDialogMessage.innerHTML = '';
            modalDialogMessage.insert(content);
            modalDialogMessage.innerHTML.evalScripts();
        },

        // ---------------------------------------

        openVariationPopUp: function(productId, title, pickupStoreId, filter)
        {
            var self = this;

            MessageObj.clear();
            M2ePro.customData.variationPopup = {
                product_id: productId,
                title: title,
                pickup_store_id: pickupStoreId,
                filter: filter
            };

            new Ajax.Request(M2ePro.url.get('variationProduct'), {
                method: 'post',
                parameters: {
                    product_id: productId,
                    pickup_store_id: pickupStoreId,
                    filter: filter
                },
                onSuccess: function (transport) {

                    var modalDialogMessage = $('pickup_store_variation_product_popup');
                    if (!modalDialogMessage) {
                        modalDialogMessage = new Element('div', {
                            id: 'pickup_store_variation_product_popup'
                        });

                        this.variationPopup = jQuery(modalDialogMessage).modal({
                            title: title,
                            type: 'slide',
                            buttons: []
                        });
                    }

                    this.variationPopup.modal('openModal');

                    modalDialogMessage.innerHTML = '';
                    modalDialogMessage.insert(transport.responseText);
                    modalDialogMessage.innerHTML.evalScripts();
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
                checkbox = checkbox[0];

                if (checkbox.value == rowId) {
                    row.insert({
                        after: '<tr id="grid_help_content_'+rowId+'"><td class="help_line" colspan="'+($(row).childElements().length)+'">'+html+'</td></tr>'
                    });
                } else {
                    var lastCell = cels[cels.length-1],
                        hiddenElement = $(lastCell).down('#product_row_order_'+rowId);

                    if (hiddenElement && hiddenElement.value == rowId) {
                        row.insert({
                            after: '<tr id="grid_help_content_'+rowId+'"><td class="help_line" colspan="'+($(row).childElements().length)+'">'+html+'</td></tr>'
                        });
                    }
                }
            }
            var self = this;
            $('hide_item_help_' + rowId).observe('click', function() {
                self.hideItemHelp(rowId);
            });
        },

        createHelpTitleHtml: function(rowId)
        {
            var closeHtml = '<a href="javascript:void(0);" id="hide_item_help_' + rowId + '" title="'+M2ePro.translator.translate('Close')+
                            '"><span class="hl_close icon-close"></span></a>';
            return '<div class="hl_header"><span class="hl_title">&nbsp;</span>'+closeHtml+'</div>';
        },

        createHelpViewAllLogHtml: function(rowId)
        {
            var id = $('product_row_order_'+rowId).getAttribute('listing-product-pickup-store-state');
            return '<div class="hl_footer">' +
                '<a href="#" onclick="EbayListingPickupStoreGridObj.getLogGrid('+id+');">'+
                M2ePro.translator.translate('View Full Product Log')+
                '</a></div>';
        },

        // ---------------------------------------

        getLogGrid: function(rowId)
        {
            new Ajax.Request(M2ePro.url.get('*/logGrid'), {
                method: 'post',
                parameters: {
                    listing_product_pickup_store_state: rowId
                },
                onSuccess: function(transport) {

                    var modalDialogMessage = $('pickup_store_variation_log_popup');
                    if (!modalDialogMessage) {
                        modalDialogMessage = new Element('div', {
                            id: 'pickup_store_variation_log_popup'
                        });

                        this.logPopup = jQuery(modalDialogMessage).modal({
                            title: M2ePro.translator.translate('Log For SKU ' + rowId),
                            type: 'slide',
                            buttons: []
                        });
                    }

                    modalDialogMessage.up('.modal-inner-wrap')
                                      .down('.modal-title')
                                      .innerHTML = M2ePro.translator.translate('Log For SKU ' + rowId);
                    this.logPopup.modal('openModal');

                    modalDialogMessage.innerHTML = '';
                    modalDialogMessage.insert(transport.responseText);
                    modalDialogMessage.innerHTML.evalScripts();
                }
            });
        },

        // ---------------------------------------

        updateButtonText: function(element, text)
        {
            jQuery(element).find('span').text(M2ePro.translator.translate(text));
        },

        // ---------------------------------------

        prepareActions: function()
        {
            var self = this;

            var actionHandler = new ListingViewAction(this);
            actionHandler.setProgressBar('pickup_store_view_progress_bar');
            actionHandler.setGridWrapper('pickup_store_view_content_container');
            actionHandler.setErrorsSummaryContainer('pickup_store_container_errors_summary');

            this.actions = {
                unassignAction: function() {
                    var selectedProductsParts = self.getSelectedItemsParts();
                    if (selectedProductsParts.length == 0) {
                        return;
                    }

                    actionHandler.startActions(
                        M2ePro.translator.translate('Unassign Product(s) from Stores'),
                        M2ePro.url.get('*/unassign'),
                        selectedProductsParts
                    );
                }
            };
        },

        getMaxProductsInPart: function ()
        {
            return 100;
        }

        // ---------------------------------------
    });
});