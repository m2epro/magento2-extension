define([
    'M2ePro/Plugin/Messages',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Grid'
], function(MessageObj, modal) {

    window.EbayListingProductCategorySettingsGrid = Class.create(Grid, {

        // ---------------------------------------

        prepareActions: function() {

            this.actions = {
                editCategoriesAction: function(id) {
                    id && this.selectByRowId(id);
                    this.editCategories('both');
                }.bind(this),

                resetCategoriesAction: function(id) {
                    this.resetCategories(id);
                }.bind(this)
            };
        },

        editCategories: function(mode) {
            this.selectedProductsIds = this.getSelectedProductsString();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/getChooserBlockHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids: this.selectedProductsIds,
                    category_mode: mode
                },
                onSuccess: function(transport) {
                    this.openPopUp('Category Settings', transport.responseText);
                }.bind(this)
            });
        },

        resetCategories: function(id) {
            if (id && !confirm('Are you sure?')) {
                return;
            }

            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoReset'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids: this.selectedProductsIds.join(',')
                },
                onSuccess: function(transport) {
                    this.getGridObj().doFilter();
                    this.unselectAll();
                }.bind(this)
            });
        },

        //----------------------------------------

        openPopUp: function(title, content) {
            var self = this;
            var popupId = 'modal_view_action_dialog';

            var modalDialogMessage = $(popupId);

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('form', {
                    id: popupId
                });
            }

            modalDialogMessage.innerHTML = '';

            this.popUp = jQuery(modalDialogMessage).modal(Object.extend({
                title: title,
                type: 'slide',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    attr: {id: 'cancel_button'},
                    class: 'action-dismiss',
                    click: function(event) {
                        self.unselectAllAndReload();
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Save'),
                    attr: {id: 'done_button'},
                    class: 'action-primary action-accept',
                    click: function(event) {
                        self.confirmCategoriesData();
                    }
                }]
            }));

            this.popUp.modal('openModal');

            try {
                modalDialogMessage.innerHTML = content;
                modalDialogMessage.innerHTML.evalScripts();
            } catch (ignored) {
            }
        },

        confirmCategoriesData: function() {
            this.initFormValidation('#modal_view_action_dialog');

            if (!jQuery('#modal_view_action_dialog').valid()) {
                return;
            }

            var selectedCategories = EbayTemplateCategoryChooserObj.selectedCategories;
            var typeMain = M2ePro.php.constant('\\Ess\\M2ePro\\Helper\\Component\\Ebay\\Category::TYPE_EBAY_MAIN');
            if (typeof selectedCategories[typeMain] !== 'undefined') {
                selectedCategories[typeMain]['specific'] = EbayTemplateCategoryChooserObj.selectedSpecifics;
            }

            this.saveCategoriesData(selectedCategories);
        },

        //----------------------------------------

        saveCategoriesData: function(templateData) {
            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoSaveToSession'), {
                method: 'post',
                parameters: {
                    products_ids: this.getSelectedProductsString(),
                    template_data: Object.toJSON(templateData)
                },
                onSuccess: function(transport) {

                    jQuery('#modal_view_action_dialog').modal('closeModal');
                    this.unselectAllAndReload();
                }.bind(this)
            });
        },

        // ---------------------------------------

        completeCategoriesDataStep: function(validateCategory, validateSpecifics) {
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('ebay_listing_product_category_settings/stepTwoModeValidate'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    validate_category: validateCategory,
                    validate_specifics: validateSpecifics
                },
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response['validation']) {
                        return setLocation(M2ePro.url.get('ebay_listing_product_category_settings'));
                    }

                    if (response['message']) {
                        return MessageObj.addError(response['message']);
                    }

                    $('next_step_warning_popup_content').select('span.total_count').each(function(el) {
                        $(el).update(response['total_count']);
                    });

                    $('next_step_warning_popup_content').select('span.failed_count').each(function(el) {
                        $(el).update(response['failed_count']);
                    });

                    var popup = jQuery('#next_step_warning_popup_content');

                    modal({
                        title: M2ePro.translator.translate('Set eBay Category'),
                        type: 'popup',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function() {
                                this.closeModal();
                            }
                        }, {
                            text: M2ePro.translator.translate('Continue'),
                            class: 'action-primary action-accept forward',
                            id: 'save_popup_button',
                            click: function() {
                                this.closeModal();
                                setLocation(M2ePro.url.get('ebay_listing_product_category_settings'));
                            }
                        }]
                    }, popup);

                    popup.modal('openModal');

                }.bind(this)
            });
        },

        // ---------------------------------------

        validateCategories: function(isAlLeasOneCategorySelected, showErrorMessage) {
            MessageObj.setContainer('#anchor-content');
            MessageObj.clear();
            var button = $('ebay_listing_category_continue_btn');
            if (parseInt(isAlLeasOneCategorySelected)) {
                button.addClassName('disabled');
                button.disable();
                if (parseInt(showErrorMessage)) {
                    MessageObj.addError(M2ePro.translator.translate('select_relevant_category'));
                }
            } else {
                button.removeClassName('disabled');
                button.enable();
                MessageObj.clear();
            }
        },

        // ---------------------------------------

        getComponent: function() {
            return 'ebay';
        }

        // ---------------------------------------
    });

});
