define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/confirm',
    'M2ePro/Grid'
], function (jQuery, confirm, modal) {
    window.Promotion = Class.create(Grid, {

        initialize: function (accountId, marketplaceId, promoDashboardUrl) {
            this.accountId = accountId;
            this.marketplaceId = marketplaceId;
            this.promoDashboardUrl = promoDashboardUrl;
        },

        handleSelectChange: function(selectElement, id, method) {
            const selectedValue = selectElement.value;

            if (selectedValue === "0") {
                return;
            }

            this[method](id, selectedValue, selectElement);
        },

        openPromotionPopup: function (selectedProductsIds) {
            this.selectedProductsIds = selectedProductsIds;

            new Ajax.Request(M2ePro.url.get('ebay_promotion/openGridPromotion'), {
                method: 'POST',
                parameters: {
                    account_id: this.accountId,
                    marketplace_id: this.marketplaceId,
                },
                onSuccess: this.processPopupPromotionData.bind(this)
            });
        },

        processPopupPromotionData: function (transport) {
            const gridHtml = transport.responseText;
            const buttons = this.getPopupButtons();

            this.createOrUpdateModal('modal_promotion', 'Manage Discounts', gridHtml, buttons);
        },

        getPopupButtons: function () {
            return [{
                class: 'action-primary action-accept',
                text: M2ePro.translator.translate('Create New Discount'),
                click: function (event) {
                    window.open(this.promoDashboardUrl, '_blank');
                }.bind(this)
            }, {
                class: 'action-primary action-accept',
                text: M2ePro.translator.translate('Refresh Discounts'),
                click: function () {
                    this.refreshPromotions();
                }.bind(this)
            }];
        },

        createOrUpdateModal: function (modalId, title, gridHtml, buttons) {
            let modalElement = jQuery('#' + modalId);

            if (modalElement.length) {
                modalElement.remove();
            }

            modalElement = new Element('div', {
                id: modalId
            });

            modalElement.update(gridHtml);

            this.popUp = jQuery(modalElement).modal({
                title: M2ePro.translator.translate(title),
                type: 'slide',
                buttons: buttons
            });

            this.popUp.modal('openModal');
        },

        refreshPromotions: function () {
            new Ajax.Request(M2ePro.url.get('ebay_promotion/synchronizePromotions'), {
                method: 'post',
                parameters: {
                    account_id: this.accountId,
                    marketplace_id: this.marketplaceId,
                },
                onSuccess: this.onRefreshPromotionsSuccess.bind(this)
            });
        },

        onRefreshPromotionsSuccess: function () {
            this.closePopup();
            this.openPromotionPopup(this.selectedProductsIds);
        },

        closePopup: function () {
            jQuery('#modal_promotion').modal('closeModal');
        },

        updateItemPromotion: function (promotion_id, action, selectElement) {
            let selectedProductsIds = this.selectedProductsIds;

            this.confirm({
                content: 'Are you sure?',
                actions: {
                    confirm: function() {
                        setLocation(M2ePro.url.get('ebay_promotion/updateItemPromotion', {
                            listing_product_ids: selectedProductsIds,
                            promotion_id: promotion_id,
                            action: action,
                        }));
                    },
                    cancel: function() {
                        selectElement.value = "0";
                        return false;
                    }
                }
            });
        },

        openDiscountPopup: function (promotion_id) {
            new Ajax.Request(M2ePro.url.get('ebay_promotion/openGridDiscount'), {
                method: 'POST',
                parameters: {
                    promotion_id: promotion_id,
                },
                onSuccess: this.processPopupDiscountData.bind(this)
            });
        },

        processPopupDiscountData: function (transport) {
            const gridHtml = transport.responseText;

            this.createOrUpdateModal('modal_discount', 'Manage Discount', gridHtml, '');
        },
    });
});
