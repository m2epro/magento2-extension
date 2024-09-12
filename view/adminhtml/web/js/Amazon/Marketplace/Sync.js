define([
    'jquery',
    'mage/translate',
    'M2ePro/Plugin/ProgressBar',
], function($, $t) {
    'use strict';

    return function(options, button) {
        const urlForGetMarketplaces = options.url_for_get_marketplaces;
        const urlForUpdateMarketplacesDetails = options.url_for_update_marketplaces_details;
        const urlForGetProductTypes = options.url_for_get_product_types;
        const urlForUpdateProductType = options.url_for_update_product_type;

        const processor = {
            isWaiterActive: false,

            progressBar: null,

            totalItems: 0,
            processedItems: 0,

            async start(progressBar) {
                this.waiterStart();

                this.progressBarPrepare(progressBar);

                try {

                    const response = await this.getMarketplaces();
                    const marketplaces = response.list.map(
                            marketplace => {
                                return {
                                    'id': marketplace.id,
                                    'title': marketplace.title,
                                };
                            },
                    );

                    this.progressBarChangeStatus($t('Update Marketplace details. Please wait...'), marketplaces.size());

                    // ----------------------------------------

                    const productTypes = [];
                    for (const marketplace of marketplaces) {
                        await this.updateMarketplaceDetails(marketplace.id);

                        const response = await this.getProductTypesForMarketplace(marketplace.id);
                        for (const productType of response.list) {
                            productTypes.push({
                                'id': productType.id, 'title': productType.title,
                            });
                        }

                        this.progressBarTik();
                    }

                    this.progressBarChangeStatus($t('Update Product Types. Please wait...'), productTypes.size());

                    for (const productType of productTypes) {
                        await this.updateProductType(productType.id);
                        this.progressBarTik();
                    }

                    // ----------------------------------------
                } catch (e) {
                    this.complete();
                    throw e;
                }

                this.complete();

                window.location.reload();
            },

            async getMarketplaces() {
                return $.ajax({
                    url: urlForGetMarketplaces,
                    type: 'GET',
                });
            },

            async updateMarketplaceDetails(marketplaceId) {
                return await $.ajax({
                    url: urlForUpdateMarketplacesDetails,
                    type: 'POST',
                    contentType: 'application/x-www-form-urlencoded',
                    data: {form_key: FORM_KEY, marketplace_id: marketplaceId},
                });
            },

            async getProductTypesForMarketplace(marketplaceId) {
                return $.ajax({
                    url: urlForGetProductTypes + `marketplace_id/${marketplaceId}`,
                    type: 'GET',
                });
            },

            async updateProductType(productTypeId) {
                return await $.ajax({
                    url: urlForUpdateProductType,
                    type: 'POST',
                    contentType: 'application/x-www-form-urlencoded',
                    data: {form_key: FORM_KEY, id: productTypeId},
                });
            },

            // ----------------------------------------

            waiterStart: function() {
                if (this.isWaiterActive) {
                    return;
                }

                $('body').trigger('processStart');
                this.isWaiterActive = true;
            },

            waiterStop: function() {
                if (!this.isWaiterActive) {
                    return;
                }

                $('body').trigger('processStop');
                this.isWaiterActive = false;
            },

            complete: function() {
                this.ProgressBar.hide();
                this.waiterStop();
            },

            // ----------------------------------------

            progressBarPrepare: function(progressBar) {
                this.ProgressBar = progressBar;

                this.ProgressBar.reset();
                this.ProgressBar.setTitle($t('Update Amazon Data'));
                this.ProgressBar.show();

                this.progressBarUpdate();
            },

            progressBarChangeStatus: function(title, totalItems) {
                this.ProgressBar.setStatus(title);
                this.totalItems = totalItems;
                this.processedItems = 0;

                this.progressBarUpdate();
            },

            progressBarTik: function() {
                this.processedItems++;
                this.progressBarUpdate();
            },

            progressBarUpdate: function() {
                this.ProgressBar.setPercents(this.getProcessPercent(), 0);
            },

            getProcessPercent: function() {
                return (this.processedItems / this.totalItems) * 100;
            },
        };

        $(button).on('click', function(e) {
            e.preventDefault();

            processor.start(new window.ProgressBar(options.progress_bar_el_id));
        });
    };
});
