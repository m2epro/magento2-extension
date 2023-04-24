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
        window.AmazonTemplateProductType = Class.create(Common, {
            showConfirmMsg: true,
            skipSaveConfirmationPostFix: '_skip_save_confirmation',

            originalFormData: null,
            isPageLeavingSafe: false,

            initialize: function()
            {
                if (this.getProductType()) {
                    this.updateProductTypeScheme();
                } else {
                    this.originalFormData = jQuery('#edit_form').serialize();
                }
            },

            initObservers: function()
            {
                $('general_marketplace_id').observe(
                    'change',
                    AmazonTemplateProductTypeObj.onChangeMarketplaceId.bind(this)
                );
                $('product_type_edit_activator').observe(
                    'click',
                    AmazonTemplateProductTypeObj.openSearchPopup.bind(this)
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
                        .text(AmazonTemplateProductTypeSearchObj.getProductTypeTitle(productType))
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
                AmazonTemplateProductTypeTabsObj.resetTabs(
                    AmazonTemplateProductTypeContentObj.getGroupList()
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

                new Ajax.Request(M2ePro.url.get('amazon_template_productType/searchProductTypePopup'), {
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
                                    class: 'primary modal-action-button',
                                    click: function () {
                                        self.confirmSearchProductTypePopup(self.productTypeSearchPopup);
                                    }
                                }
                            ]
                        });

                        self.productTypeSearchPopup.modal('openModal');
                        self.setSearchActivatorVisibility(true);
                    }
                });
            },

            cancelSearchProductTypePopup: function (container)
            {
                container.modal('closeModal');
            },

            confirmSearchProductTypePopup: function (container)
            {
                this.setProductType(AmazonTemplateProductTypeSearchObj.currentProductType);
                container.modal('closeModal');
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

                new Ajax.Request(M2ePro.url.get('amazon_template_productType/getProductTypeInfo'), {
                    method: 'post',
                    asynchronous: true,
                    parameters: {
                        marketplace_id: marketplaceId,
                        product_type: productType
                    },
                    onSuccess: function(transport) {
                        const response = transport.responseText.evalJSON();
                        if (!response.result) {
                            messageObj.clear();
                            messageObj.addError(response.message);
                            return;
                        }

                        AmazonTemplateProductTypeContentObj.load(
                            response.data['scheme'],
                            response.data['settings'],
                            response.data['groups'],
                            response.data['timezone_shift']
                        );

                        self.originalFormData = jQuery('#edit_form').serialize();
                    }
                });
            },

            confirm: function (confirmText, okCallback)
            {
                var self = this;
                var skipConfirmation = localStorage.get('amazon_product_type_' + self.skipSaveConfirmationPostFix);

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
                                localStorage.set('amazon_product_type_' + self.skipSaveConfirmationPostFix, 1);
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
                if (!this.isValidForm()) {
                    return;
                }

                if (confirmText && this.showConfirmMsg) {
                    this.confirm(
                        confirmText,
                        function() {
                            AmazonTemplateProductTypeObj.isPageLeavingSafe = true;
                            $super(url);
                        }
                    );
                    return;
                }

                this.isPageLeavingSafe = true;
                $super(url, true);
            },

            saveAndEditClick: function ($super, url, confirmText)
            {
                if (!this.isValidForm()) {
                    return;
                }

                if (confirmText && this.showConfirmMsg) {
                    this.confirm(
                        confirmText,
                        function() {
                            AmazonTemplateProductTypeObj.isPageLeavingSafe = true;
                            $super(url);
                        }
                    );
                    return;
                }

                this.isPageLeavingSafe = true;
                $super(url)
            },

            saveAndCloseClick: function ($super, confirmText)
            {
                if (!this.isValidForm()) {
                    return;
                }

                if (confirmText && this.showConfirmMsg) {
                    this.confirm(
                        confirmText,
                        function() {
                            AmazonTemplateProductTypeObj.isPageLeavingSafe = true;
                            AmazonTemplateProductTypeObj.saveFormUsingAjax();
                        }
                    );
                    return;
                }

                this.isPageLeavingSafe = true;
                this.saveFormUsingAjax();
            },

            saveFormUsingAjax: function() {
                new Ajax.Request(M2ePro.url.get('formSubmit'), {
                    method: 'post',
                    parameters: Form.serialize($('edit_form')),
                    onSuccess: function(transport) {
                        const response = transport.responseText.evalJSON();

                        if (response.status) {
                            window.close();
                        } else {
                            messageObj.clear();
                            messageObj.addError(response.message);
                        }
                    }
                });
            },

            deleteClick: function()
            {
                CommonObj.confirm({
                    actions: {
                        confirm: function () {
                            AmazonTemplateProductTypeObj.isPageLeavingSafe = true;
                            setLocation(M2ePro.url.get('deleteAction'));
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            }
        });
    }
);
