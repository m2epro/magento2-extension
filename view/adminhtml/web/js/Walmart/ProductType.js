define(
    [
        'jquery',
        'Magento_Ui/js/modal/confirm',
        'M2ePro/Plugin/Storage',
        'M2ePro/Plugin/Messages',
        'M2ePro/Common',
        'M2ePro/General/PhpFunctions',
    ],
    function (jQuery, confirm, localStorage, messageObj) {
        window.WalmartProductType = Class.create(Common, {
            skipSaveConfirmationPostFix: '_skip_save_confirmation',

            originalFormData: null,
            isPageLeavingSafe: false,
            viewModeSwitcher: jQuery('#view_mode_switch_button'),
            viewModeInput: jQuery('input#view_mode'),

            initialize: function()
            {
                if (this.getProductType()) {
                    this.updateProductTypeScheme();
                } else {
                    this.originalFormData = jQuery('#edit_form').serialize();
                }

                let self = this;
                this.viewModeSwitcher.on('change', function () {
                    self.viewModeInput.val(self.getViewModeSwitcherValue())
                });

                jQuery.validator.addMethod(
                    'M2ePro-general-product-type-title',
                    function (productTypeTitle) {
                        let marketplaceId = document.getElementById('general_marketplace_id').value;
                        let productTypeId = document.getElementById('general_id').value;
                        let isValid = false;

                        new Ajax.Request(M2ePro.url.get('walmart_productType/isUniqueTitle'), {
                            method: 'post',
                            asynchronous: false,
                            parameters: {
                                title: productTypeTitle,
                                marketplace_id: marketplaceId,
                                product_type_id: productTypeId,
                            },
                            onSuccess: function(transport) {
                                isValid = transport.responseText.evalJSON()['result'];
                            }
                        });

                        return isValid;
                    },
                    M2ePro.translator.translate(
                            'The specified Product Title is already used for other Product Type. Product Type Title must be unique.'
                    )
                );

                jQuery(document).on('change', '#general_product_type_title', this.clearValidationMessage.bind(this));
            },

            getViewModeSwitcherValue: function ()
            {
                return this.viewModeSwitcher.prop('checked') ? 1 : 0
            },

            initObservers: function()
            {
                $('general_marketplace_id').observe(
                    'change',
                    WalmartProductTypeObj.onChangeMarketplaceId.bind(this)
                );
                $('product_type_edit_activator').observe(
                    'click',
                    WalmartProductTypeObj.openSearchPopup.bind(this)
                );

                addEventListener(
                    "beforeunload",
                    function (event) {
                        const currentFormData = jQuery('#edit_form').serialize();
                        if (!this.isPageLeavingSafe && currentFormData !== this.originalFormData) {
                            event.preventDefault();
                            return event.returnValue = "";
                        }
                    }.bind(this),
                    {capture: true}
                );
            },

            getMarketplaceId: function()
            {
                const marketplaceId = $('general_marketplace_id').value;

                return marketplaceId !== undefined ? marketplaceId : 0;
            },

            getProductType: function ()
            {
                const productType = $('general_product_type').value;

                return productType !== undefined ? productType : '';
            },

            setProductType: function (productType)
            {
                const productTypeField = $('general_product_type');
                if (productType === productTypeField.value) {
                    return;
                }

                productTypeField.value = productType;
                const searchPopupNotSelected = jQuery('#general_product_type_not_selected');
                const selectedProductTypeTitle = jQuery('#general_selected_product_type_title');

                if (productType) {
                    searchPopupNotSelected.hide();
                    selectedProductTypeTitle
                            .text(WalmartProductTypeSearchObj.getProductTypeTitle(productType))
                            .show();
                } else {
                    searchPopupNotSelected.show();
                    selectedProductTypeTitle.hide();
                }

                this.updateProductTypeScheme();
            },

            onChangeMarketplaceId: function ()
            {
                this.setProductType('');
                this.updateProductTypeScheme();
                this.openSearchPopup();
            },

            resetProductTypeScheme: function ()
            {
                WalmartProductTypeTabsObj.resetTabs(
                    WalmartProductTypeContentObj.getGroupList()
                );

                $$('.product_type_generated_field').map(
                    function (item) {
                        item.remove();
                    }
                );
            },

            openSearchPopup: function ()
            {
                var self = this;

                new Ajax.Request(M2ePro.url.get('walmart_productType/searchProductTypePopup'), {
                    method: 'post',
                    asynchronous: true,
                    parameters: {
                        marketplace_id: self.getMarketplaceId()
                    },
                    onSuccess: function(transport) {
                        const popupContainerId = 'search_product_type_popup';

                        const previousContainer = $(popupContainerId);
                        if (previousContainer) {
                            previousContainer.remove();
                        }

                        const popupContainer = new Element(
                            'div',
                            {id: popupContainerId}
                        );

                        popupContainer.innerHTML = transport.responseText;

                        self.productTypeSearchPopup = jQuery(popupContainer).modal({
                            title: M2ePro.translator.translate('Search Product Type'),
                            type: 'popup',
                            buttons: [
                                {
                                    text: M2ePro.translator.translate('Cancel'),
                                    class: 'modal-action-button',
                                    click: function () {
                                        self.cancelSearchProductTypePopup(self.productTypeSearchPopup);
                                    }
                                },
                                {
                                    text: M2ePro.translator.translate('Confirm'),
                                    class: 'primary modal-action-button product-type-confirm',
                                    click: function () {
                                        self.confirmSearchProductTypePopup(self.productTypeSearchPopup);
                                    }
                                }
                            ]
                        });

                        self.productTypeSearchPopup.modal('openModal');
                        self.setSearchActivatorVisibility(true);
                        WalmartProductTypeFinderObj.renderRootCategories('product_type_browse_results')
                    }
                });
            },

            cancelSearchProductTypePopup: function (container)
            {
                container.modal('closeModal');
            },

            confirmSearchProductTypePopup: function (container)
            {
                var currentTabId = this.getActiveTabId();
                if (currentTabId === 'productTypeChooserTabs_search') {
                    this.setProductType(WalmartProductTypeSearchObj.currentProductType);
                } else if (currentTabId === 'productTypeChooserTabs_browse') {
                    this.setProductType(WalmartProductTypeFinderObj.currentProductType);
                }

                container.modal('closeModal');
            },

            getActiveTabId: function ()
            {
                var activeTab = jQuery('.tabs-horiz li.ui-tabs-active a.tab-item-link');

                if (activeTab.length > 0) {
                    return activeTab.attr('id');
                }

                return 'productTypeChooserTabs_search';
            },

            setSearchActivatorVisibility: function (visible)
            {
                $('product_type_edit_activator').style.display = visible ? 'inline' : 'none';

            },

            updateProductTypeScheme: function ()
            {
                var self = this;
                this.resetProductTypeScheme();

                const marketplaceId = this.getMarketplaceId(),
                    productType = this.getProductType();
                if (!marketplaceId || !productType) {
                    return;
                }

                self.viewModeSwitcher.on('change', function () {
                    self.resetProductTypeScheme();
                    self.loadProductTypeForm(marketplaceId, productType);
                });

                self.loadProductTypeForm(marketplaceId, productType);
            },

            loadProductTypeForm: function (marketplaceId, productType)
            {
                var self = this;
                var isToggledOn = self.getViewModeSwitcherValue();

                var generalProductTypeTitle = $('general_product_type_title');
                if (generalProductTypeTitle.value === '') {
                    generalProductTypeTitle.value = jQuery('#general_selected_product_type_title').text();
                }

                new Ajax.Request(M2ePro.url.get('walmart_productType/getProductTypeInfo'), {
                    method: 'post',
                    asynchronous: true,
                    parameters: {
                        marketplace_id: marketplaceId,
                        product_type: productType,
                        only_required_attributes: isToggledOn,
                        is_new_product_type: jQuery('input#is_new_product_type').val()
                    },
                    onSuccess: function(transport) {
                        const response = transport.responseText.evalJSON();
                        if (!response.result) {
                            messageObj.clear();
                            messageObj.addError(response.message);
                            return;
                        }

                        WalmartProductTypeContentObj.load(
                            response.data['scheme'],
                            response.data['settings'],
                            response.data['groups'],
                            response.data['timezone_shift'],
                            response.data['specifics_default_settings'],
                        );

                        self.originalFormData = jQuery('#edit_form').serialize();
                    }
                });
            },

            confirm: function (confirmText, okCallback)
            {
                var self = this;
                var skipConfirmation = localStorage.get('walmart_product_type_' + self.skipSaveConfirmationPostFix);

                if (!confirmText || skipConfirmation) {
                    okCallback();
                    return;
                }

                confirm({
                    title: M2ePro.translator.translate('Save Product Type Settings'),
                    content: confirmText + '<div class="admin__field admin__field-option" style="position: absolute; bottom: 43px; left: 28px;">' +
                            '<input class="admin__control-checkbox" type="checkbox" id="do_not_show_again" name="do_not_show_again">&nbsp;' + '<label for="do_not_show_again" class="admin__field-label"><span>'+ M2ePro.translator.translate('Do not show any more') + '</span></label>' + '</div>',
                    buttons: [{
                        text: M2ePro.translator.translate('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }, {
                        text: M2ePro.translator.translate('Confirm'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }],
                    actions: {
                        confirm: function () {
                            if ($('do_not_show_again').checked) {
                                localStorage.set('walmart_product_type_' + self.skipSaveConfirmationPostFix, 1);
                            }

                            okCallback();
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            },

            saveClick: function ($super, url, confirmText)
            {
                var self = this;
                if (!this.isValidForm()) {
                    return;
                }

                if (confirmText) {
                    this.confirm(confirmText, function () {
                        self.isPageLeavingSafe = true;
                        self.saveFormUsingAjax((response) => setLocation(response.backUrl))
                    });
                } else {
                    self.isPageLeavingSafe = true;
                    self.saveFormUsingAjax((response) => setLocation(response.backUrl))
                }
            },

            saveAndEditClick: function ($super, url, confirmText)
            {
                var self = this;
                if (!this.isValidForm()) {
                    return;
                }

                if (confirmText) {
                    this.confirm(confirmText, function () {
                        self.isPageLeavingSafe = true;
                        self.saveFormUsingAjax(response => setLocation(response.editUrl));
                    });

                    return;
                }

                self.isPageLeavingSafe = true;
                self.saveFormUsingAjax(response => setLocation(response.editUrl));
            },

            saveAndCloseClick: function ($super, confirmText)
            {
                var self = this;
                if (!this.isValidForm()) {
                    return;
                }

                if (confirmText) {
                    this.confirm(confirmText, function () {
                        self.isPageLeavingSafe = true;
                        self.saveFormUsingAjax(() => window.close());
                    });

                    return;
                }

                self.isPageLeavingSafe = true;
                self.saveFormUsingAjax(() => window.close());
            },

            saveFormUsingAjax: function (successCallback) {
                new Ajax.Request(M2ePro.url.get('formSubmit'), {
                    method: 'post',
                    parameters: Form.serialize($('edit_form')),
                    onSuccess: function(transport) {
                        const response = transport.responseText.evalJSON();
                        if (!response.status) {
                            messageObj.clear();
                            messageObj.addError(response.message);

                            return;
                        }
                        if(successCallback) {
                            successCallback({
                                backUrl: response.back_url,
                                editUrl: response.edit_url
                            });
                        }
                    }
                });
            },

            deleteClick: function()
            {
                CommonObj.confirm({
                    actions: {
                        confirm: function () {
                            WalmartProductTypeObj.isPageLeavingSafe = true;
                            setLocation(M2ePro.url.get('deleteAction'));
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            },

            clearValidationMessage: function () {
                jQuery('label[for="general_product_type_title"].mage-error').remove();
            }
        });
    }
);
