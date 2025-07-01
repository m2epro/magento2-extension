define([
    'jquery',
    'mage/translate',
    'M2ePro/Plugin/Messages',
    'mage/loader',
], function ($, $t, MessagesObj) {
    window.Campaign = Class.create({
        accountId: null,
        marketplaceId: null,
        productGridJsObject: null,
        marketplaceTitle: null,
        gridMessageObj: null,
        formMessageObj: null,
        productGridMessageObj: null,


        campaignModal: null,
        campaignGridJsObject: null,
        selectedProductsIds: null,

        initialize: function (accountId, marketplaceId, productGridId, marketplaceTitle) {
            this.accountId = accountId;
            this.marketplaceId = marketplaceId;
            this.productGridJsObject = window[productGridId + 'JsObject'];
            this.marketplaceTitle = marketplaceTitle

            this.productGridMessageObj = Object.create(MessagesObj);
            this.productGridMessageObj.setContainer('#' + productGridId);

            this.gridMessageObj = Object.create(MessagesObj);
            this.gridMessageObj.setContainer('#campaign_grid_messages');

            this.formMessageObj = Object.create(MessagesObj);
            this.formMessageObj.setContainer('#campaign_form_messages');
        },

        /**
         * @param {jQuery} campaignModal
         */
        initializeAfterOpenCampaignModal: function (campaignModal) {
            this.campaignModal = campaignModal;
            this.campaignGridJsObject = this.findGridJsObject(campaignModal);
        },

        // -------------------------

        openCampaignModal: function (selectedProductsIds) {

            this.selectedProductsIds = selectedProductsIds;

            $.ajax(M2ePro.url.get('promotedListing/getCampaignGrid'), {
                method: 'GET',
                data: {
                    account_id: this.accountId,
                    marketplace_id: this.marketplaceId,
                },
                success: (response) => {
                    const modalId = 'promoted_listing_campaign_modal'

                    const modalTitle = $t('Manage Promotion Campaign on %marketplace Marketplace')
                            .replace('%marketplace', this.marketplaceTitle)
                    let campaignModal = this
                        .getModalElementById(modalId)
                        .html(response)
                        .modal({
                            title: modalTitle,
                            type: 'slide',
                            buttons: [{
                                class: 'action-primary',
                                text: $t('Create Promotion Campaign'),
                                click: () => this.openCreateCampaignPopup()
                            }, {
                                class: 'action-primary',
                                text: $t('Refresh Promotion Campaigns'),
                                click: () => this.refreshCampaigns()
                            }],
                            opened: () => {
                                this.initializeAfterOpenCampaignModal(campaignModal)
                            }
                        });

                    campaignModal.modal('openModal');
                },
                error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
            });
        },

        refreshCampaigns: function () {
            const successCallback = () => {
                $.ajax(M2ePro.url.get('promotedListing/refreshCampaigns'), {
                    method: 'GET',
                    data: {
                        account_id: this.accountId,
                        marketplace_id: this.marketplaceId
                    },
                    success: (response) => {
                        response = JSON.parse(response);
                        if (response.result) {
                            this.campaignGridJsObject.doFilter();
                            return;
                        }

                        if (response.hasOwnProperty('fail_message')) {
                            this.gridMessageObj.clear();
                            this.gridMessageObj.addError(response['fail_message']);
                        }
                        setTimeout(() => this.gridMessageObj.clear(), 5000);
                    },
                    beforeSend: () => this.showLoader(),
                    complete: () => this.hideLoader(),
                    error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
                });
            };

            this.confirmPopup(successCallback);
        },

        openCreateCampaignPopup: function () {
            $.ajax(M2ePro.url.get('promotedListing/getCreateCampaignForm'), {
                method: 'GET',
                data: {
                    account_id: this.accountId,
                    marketplace_id: this.marketplaceId,
                },
                success: (response) => {
                    const popupId = 'create_new_campaign_popup'
                    const popupElement = this.getModalElementById(popupId).html(response);

                    let campaignFormPopup = popupElement.modal({
                        title: $t('Create Promotion Campaign'),
                        type: 'popup',
                        modalClass: 'width-50',
                        buttons: [{
                            class: 'action-primary action-accept',
                            text: $t('Create'),
                            click: () => this.createCampaign(campaignFormPopup)
                        }, {
                            class: 'action-secondary',
                            text: $t('Cancel'),
                            click: () => {
                                campaignFormPopup.modal('closeModal');
                            }
                        }],
                        opened: () => {
                            campaignFormPopup.trigger('contentUpdated');
                        }
                    });

                    campaignFormPopup.modal('openModal');
                },
                error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
            });
        },

        createCampaign: function (campaignPopup) {
            this.formMessageObj.clear();

            const form = campaignPopup.find('form');
            if (!form.valid()) {
                return;
            }

            new $.ajax(M2ePro.url.get('promotedListing/createCampaign'), {
                method: 'POST',
                asynchronous: false,
                data: form.serializeArray(),
                success: (response) => this.addOrCreateCampaignComplete(response, campaignPopup),
                beforeSend: () => this.showLoader(),
                complete: () => this.hideLoader(),
                error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
            });
        },

        /**
         * @param {int} campaignId
         */
        openUpdateCampaignPopup: function (campaignId) {
            $.ajax(M2ePro.url.get('promotedListing/getUpdateCampaignForm'), {
                method: 'GET',
                data: {
                    'campaign_id': campaignId
                },
                success: (response) => {
                    const popupId = 'create_new_campaign_popup'
                    const popupElement = this.getModalElementById(popupId).html(response);

                    let campaignFormPopup = popupElement.modal({
                        title: $t('Update Promotion Campaign'),
                        type: 'popup',
                        modalClass: 'width-50',
                        buttons: [{
                            class: 'action-primary action-accept',
                            text: $t('Update'),
                            click: () => this.updateCampaign(campaignFormPopup)
                        }, {
                            class: 'action-secondary',
                            text: $t('Cancel'),
                            click: () => {
                                campaignFormPopup.modal('closeModal');
                            }
                        }],
                        opened: () => {
                            campaignFormPopup.trigger('contentUpdated');
                        }
                    });

                    campaignFormPopup.modal('openModal');
                },
                error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
            });
        },

        updateCampaign: function (campaignPopup) {
            this.formMessageObj.clear();

            const form = campaignPopup.find('form');
            if (!form.valid()) {
                return;
            }

            new $.ajax(M2ePro.url.get('promotedListing/updateCampaign'), {
                method: 'POST',
                asynchronous: false,
                data: form.serializeArray(),
                success: (response) => this.addOrCreateCampaignComplete(response, campaignPopup),
                beforeSend: () => this.showLoader(),
                complete: () => this.hideLoader(),
                error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
            });
        },

        addOrCreateCampaignComplete: function (response, campaignPopup) {
            response = JSON.parse(response);

            if (response.result) {
                if (response.hasOwnProperty('message')) {
                    campaignPopup.modal('closeModal');
                    this.gridMessageObj.addSuccess(response.message)
                    this.campaignGridJsObject.doFilter();
                    setTimeout(() => {
                        this.gridMessageObj.clear();
                    }, 3000);
                }

                return;
            }

            if (response['fail_messages']) {
                response['fail_messages'].forEach((message) => {
                    this.formMessageObj.addError(message)
                });
            }
        },

        addItemsToCampaign: function (campaignId) {
            const successCallback = () => {
                $.ajax(M2ePro.url.get('promotedListing/addItemsToCampaign'), {
                    method: 'POST',
                    data: {
                        listing_product_ids: this.selectedProductsIds,
                        campaign_id: campaignId,
                        form_key: window.FORM_KEY
                    },
                    success: (response) => this.itemsComplete(response),
                    beforeSend: () => this.showLoader(),
                    complete: () => this.hideLoader(),
                    error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
                })
            }

            this.confirmPopup(successCallback);
        },

        deleteItemsFromCampaign: function (campaignId) {
            const successCallback = () => {
                $.ajax(M2ePro.url.get('promotedListing/deleteItemsFromCampaign'), {
                    method: 'POST',
                    data: {
                        listing_product_ids: this.selectedProductsIds,
                        campaign_id: campaignId,
                        form_key: window.FORM_KEY
                    },
                    success: (response) => this.itemsComplete(response),
                    beforeSend: () => this.showLoader(),
                    complete: () => this.hideLoader(),
                    error: (jqXHR) => this.ajaxErrorHandler(jqXHR)
                })
            }

            this.confirmPopup(successCallback);
        },

        itemsComplete: function (response) {
            response = JSON.parse(response);
            if (response.result) {
                this.campaignModal.modal('closeModal');
                this.productGridJsObject.doFilter();
                if (response.hasOwnProperty('message')) {
                    setTimeout(() => this.productGridMessageObj.addSuccess(response.message), 1500);
                }

                return;
            }

            if (response.hasOwnProperty('fail_message')) {
                this.gridMessageObj.addError(response.fail_message);
            }
        },

        /**
         * @param {int} campaignId
         */
        openDeleteCampaignPopup: function (campaignId) {
            const successCallback = () => {
                $.ajax(M2ePro.url.get('promotedListing/deleteCampaign'), {
                    method: 'POST',
                    data: {
                        campaign_id: campaignId,
                        form_key: window.FORM_KEY
                    },
                    success: (response) => {
                        response = JSON.parse(response);

                        if (response.result) {
                            this.campaignGridJsObject.doFilter();

                            return;
                        }

                        if (response['fail_messages']) {
                            response['fail_messages'].forEach((message) => {
                                this.gridMessageObj.addError(message)
                            });
                        }
                        setTimeout(() => this.gridMessageObj.clear(), 5000);
                    },
                    beforeSend: () => this.showLoader(),
                    complete: () => this.hideLoader(),
                });
            };

            this.confirmPopup(successCallback)
        },

        //----------------------------

        confirmPopup: function (successCallback) {
            const confirmModalId = 'promoted_listing_campaign_confirm_popup'

            this.getModalElementById(confirmModalId).confirm({
                title: $t('Are you sure?'),
                actions: {
                    confirm: successCallback
                },
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $t('Confirm'),
                    class: 'action-primary',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        /**
         * @param {jQuery} element
         * @return {null | varienGrid}
         */
        findGridJsObject: function (element) {
            let gridId = element.find('[data-grid-id]').attr('data-grid-id');
            if (!gridId) {
                return null;
            }

            gridId += 'JsObject'
            if (!window.hasOwnProperty(gridId)) {
                return null;
            }

            return window[gridId];
        },

        /**
         * @param {string} elementId
         * @returns {jQuery}
         */
        getModalElementById: function (elementId) {
            let modalElement = $('#' + elementId);

            if (modalElement.length) {
                modalElement.remove();
            }

            modalElement = new Element('div', {
                id: elementId
            });

            return $(modalElement)
        },

        showLoader: function () {
            $('body').loader('show')
        },

        hideLoader: function () {
            $('body').loader('hide')
        },

        ajaxErrorHandler: function(jqXHR) {
            this.formMessageObj.addError(jqXHR.statusText)
            console.error(jqXHR.responseText)
        }
    });
})
