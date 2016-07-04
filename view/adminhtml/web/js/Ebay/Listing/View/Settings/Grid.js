define([
    'M2ePro/Ebay/Listing/View/Grid',
    'M2ePro/Listing/Moving',
    'Magento_Ui/js/modal/confirm'
], function () {

    window.EbayListingViewSettingsGrid = Class.create(EbayListingViewGrid, {

        // ---------------------------------------

        prepareActions: function($super)
        {
            $super();

            this.movingHandler = new ListingMoving(this);

            this.actions = Object.extend(this.actions, {

                editPrimaryCategorySettingsAction: function(id) {
                    this.editCategorySettings(id);
                }.bind(this),
                editStorePrimaryCategorySettingsAction: function(id) {
                    this.editCategorySettings(id);
                }.bind(this),
                editAllSettingsAction: function(id) {
                    this.editSettings(id);
                }.bind(this),
                editGeneralSettingsAction: function(id) {
                    this.editSettings(id, 'general');
                }.bind(this),
                editSellingSettingsAction: function(id) {
                    this.editSettings(id, 'selling');
                }.bind(this),
                editSynchSettingsAction: function(id) {
                    this.editSettings(id, 'synchronization');
                }.bind(this),

                editMotorsAction: function(id) {
                    this.openMotorsPopup(id);
                }.bind(this),

                movingAction: this.movingHandler.run.bind(this.movingHandler),

                transferringAction: function(id) {
                    this.transferring(id);
                }.bind(this)

            });
        },

        // ---------------------------------------

        editSettings: function(id, tab)
        {
            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();

            new Ajax.Request(M2ePro.url.get('adminhtml_ebay_template/editListingProduct'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    ids: this.selectedProductsIds.join(','),
                    tab: tab || ''
                },
                onSuccess: function(transport) {

                    this.unselectAll();

                    var title = this.getPopUpTitle(tab, this.getSelectedProductsTitles());

                    this.openPopUp(title, transport.responseText);

                    ebayListingTemplateEditTabsJsTabs.moveTabContentInDest();
                }.bind(this)
            });
        },

        openMotorsPopup: function(id)
        {
            EbayMotorsHandlerObj.savedNotes = {};

            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();
            EbayMotorsHandlerObj.openAddPopUp(this.selectedProductsIds);
        },

        // ---------------------------------------

        saveSettings: function(savedTemplates)
        {
            var requestParams = {};

            // push information about saved templates into the request params
            // ---------------------------------------
            $H(savedTemplates).each(function(i) {
                requestParams[i.key] = i.value;
            });
            // ---------------------------------------

            // ---------------------------------------
            requestParams['ids'] = this.selectedProductsIds.join(',');
            // ---------------------------------------

            new Ajax.Request(M2ePro.url.get('adminhtml_ebay_template/saveListingProduct'), {
                method: 'post',
                asynchronous: true,
                parameters: requestParams,
                onSuccess: function(transport) {
                    Windows.getFocusedWindow().close();
                    this.getGridObj().doFilter();
                }.bind(this)
            });
        },

        // ---------------------------------------

        getSelectedProductsTitles: function()
        {
            if (this.selectedProductsIds.length > 3) {
                return '';
            }

            var title = '';

            // use the names of only first three products for pop up title
            for (var i = 0; i < 3; i++) {
                if (typeof this.selectedProductsIds[i] == 'undefined') {
                    break;
                }

                if (title != '') {
                    title += ', ';
                }

                title += this.getProductNameByRowId(this.selectedProductsIds[i]);
            }

            return title;
        },

        // ---------------------------------------

        getPopUpTitle: function(tab, productTitles)
        {
            var title;

            switch (tab) {
                case 'general':
                    title = M2ePro.translator.translate('Edit Payment and Shipping Settings');
                    break;
                case 'selling':
                    title = M2ePro.translator.translate('Edit Selling Settings');
                    break;
                case 'synchronization':
                    title = M2ePro.translator.translate('Edit Synchronization Settings');
                    break;
                default:
                    title = M2ePro.translator.translate('Edit Settings');
            }

            if (productTitles) {
                title += ' ' + M2ePro.translator.translate('for') + '"' + productTitles + '"';
            }

            title += '.';

            return title;
        },

        // ---------------------------------------

        transferring: function(id)
        {
            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();
            if (this.selectedProductsIds.length) {
                this.unselectAll();

                var productName = this.selectedProductsIds.length == 1 ?
                    this.getProductNameByRowId(this.selectedProductsIds[0]) : null;

                EbayListingTransferringHandlerObj.loadActionHtml(this.selectedProductsIds, null, productName);
            }
        },

        // ---------------------------------------

        confirm: function()
        {
            return true;
        },

        // ---------------------------------------

        removeTemplate: function (currentElement, url)
        {
            var element = jQuery('<div class="remove_template_confirm_popup">');

            element.confirm({
                title: M2ePro.translator.translate('Are you sure?'),
                actions: {
                    confirm: function () {
                        new Ajax.Request(url, {
                            method: 'get',
                            asynchronous: true,
                            onSuccess: function(transport) {

                                var result = JSON.parse(transport.responseText);
                                if (!result.success) {
                                    return;
                                }

                                currentElement.up().remove();

                                $$('.product_templates').forEach(function(el) {
                                    if (el.childElementCount) {
                                        return;
                                    }
                                    
                                    el.hide();
                                });
                            }
                        });
                    }
                }
            });
        }

        // ---------------------------------------
    });
});