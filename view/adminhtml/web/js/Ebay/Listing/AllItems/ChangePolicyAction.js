define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common',
], function ($, $t) {
    'use strict';

    /**
     * @typedef {Object} MarketplaceGroupData
     * @property {number} marketplace_id
     * @property {string} marketplace_title
     * @property {number[]} listing_product_ids
     */

    /**
     * @typedef {Object} GroupByMarketplaceResponse
     * @property {boolean} success
     * @property {MarketplaceGroupData[]} [data]
     * @property {string} [message]
     */

    return Class.create(Common, {

        /** @type {Object|undefined} */
        gridHandler: undefined,
        /** @type {JQuery|undefined} */
        modal: undefined,

        /**
         * @param {Object} [gridHandler]
         */
        initialize: function(gridHandler) {
            this.gridHandler = gridHandler;
        },

        /**
         * @param {number[]} listingProductIds
         */
        handle: function (listingProductIds) {
            this.groupByMarketplace(listingProductIds, (marketplaceGroupData) => {
                if (!marketplaceGroupData || !marketplaceGroupData.length) {
                    return;
                }

                this.processQueue(marketplaceGroupData, 0);
            });
        },

        /**
         * @param {MarketplaceGroupData[]} queue
         * @param {number} index
         */
        processQueue: function (queue, index) {
            if (index >= queue.length) {
                if (this.gridHandler) {
                    this.gridHandler.unselectAllAndReload();
                }

                return;
            }

            const currentData = queue[index];

            this.getPopupHtml(currentData, (content) => {
                if (!content) {
                    this.processQueue(queue, index + 1);
                    return;
                }

                this.openPopup(currentData, content, () => {
                    this.processQueue(queue, index + 1);
                });
            });
        },

        /**
         * @param {number[]} listingProductIds
         * @param {function(MarketplaceGroupData[]): void} onSuccess
         */
        groupByMarketplace: function (listingProductIds, onSuccess) {
            new Ajax.Request(M2ePro.url.get('changePolicy/groupByMarketplace'), {
                method: 'POST',
                asynchronous: true,
                parameters: {
                    product_ids: listingProductIds.join(','),
                },
                onSuccess: function (transport) {
                    const response = JSON.parse(transport.responseText);
                    if (!response.success) {
                        console.error(response.message);
                        return;
                    }

                    onSuccess(response.data);
                }
            });
        },

        /**
         * @param {MarketplaceGroupData} marketplaceGroupData
         * @param {function(string): void} onSuccess
         */
        getPopupHtml: function (marketplaceGroupData, onSuccess) {
            new Ajax.Request(M2ePro.url.get('changePolicy/getPopupHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids: marketplaceGroupData.listing_product_ids.join(','),
                },
                onSuccess: function(transport) {
                    const response = transport.responseText;
                    if (+response === 0) {
                        return;
                    }

                    onSuccess(response);
                }
            });
        },

        /**
         * @param {MarketplaceGroupData} marketplaceGroupData
         * @param {string} content
         * @param {function(): void} onNext
         */
        openPopup: function (marketplaceGroupData, content, onNext) {
            const modalId = 'change_policy_modal';
            let modalElement = $(`#${modalId}`);
            if (modalElement.length) {
                modalElement.remove();
            }

            modalElement = new Element('div', {
                id: modalId
            });

            this.modal = $(modalElement).modal({
                title: $t('Change Policies for eBay [%marketplace_title]')
                        .replace('%marketplace_title', marketplaceGroupData.marketplace_title),
                type: 'slide',
                buttons: [
                    {
                        text: $t('Cancel'),
                        click: ()=> {
                            this.closeModal();
                            onNext();
                        }
                    },
                    {
                        text: $t('Save'),
                        class: 'action-primary',
                        click: () => {
                            const form = this.modal.find('form');
                            if (!form.valid()) {
                                return;
                            }

                            let requestParams = Object.fromEntries(new FormData(form[0]));
                            requestParams.products_ids = marketplaceGroupData.listing_product_ids.join(',');

                            this.showModalLoader();
                            this.saveTemplates(requestParams, () => {
                                this.hideModalLoader();
                                this.closeModal();
                                onNext();
                            });
                        }
                    },
                ]
            });

            modalElement.innerHTML = content;
            modalElement.innerHTML.evalScripts();

            this.modal.modal('openModal');
        },

        closeModal: function () {
            if (!this.modal) {
                return;
            }

            this.modal.modal('closeModal');
            this.modal.remove();
            this.modal = undefined;
        },

        /**
         * @param {Object.<string, string>} requestParams
         * @param {function(): void} onSuccess
         */
        saveTemplates: function (requestParams, onSuccess) {
            new Ajax.Request(M2ePro.url.get('changePolicy/savePolicies'), {
                method: 'post',
                asynchronous: true,
                parameters: requestParams,
                onSuccess: onSuccess,
            });
        },

        showModalLoader: function () {
            if (this.modal) {
                this.modal.loader({});
                this.modal.loader('show');
            }
        },

        hideModalLoader: function () {
            if (this.modal && this.modal.data('mageLoader')) {
                this.modal.loader('hide');
            }
        }
    });
});
