define([
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper',
    'M2ePro/Grid',
    'M2ePro/Action',
], function () {
    window.ProductTypeValidatorGrid = Class.create(Grid, {

        initialize: function($super, gridId, progressBarId, objectName)
        {
            this.objectName = objectName;
            this.progressBar =  new ProgressBar(progressBarId);
            this.progressBar.setTitle(M2ePro.translator.translate('progress_bar_title'));
            this.progressBar.setStatus(M2ePro.translator.translate('progress_bar_status'));

            $super(gridId)
        },

        afterInitPage: function ($super) {
            $super();
            this.areaWrapper = new AreaWrapper(this.gridId);
        },

        prepareActions: function () {
            this.actions = {
                validateProductTypeAction: (function () {
                    this.validate(this.getOrderedSelectedProductsArray());
                    this.unselectAllAndReload();
                }).bind(this),
            };
        },

        validateAll: function () {
            var self = this;
            setTimeout(function () {
                var ids = self.getGridMassActionObj().getGridIds().split(',')
                self.validate(ids);
            }, 100)
        },

        validate: function (listingProductIds) {
            var self = this;

            var sendPart = function (listingProductIds, totalCount, partSize) {
                self.progressBar.show();

                if (listingProductIds.length === 0) {
                    self.getGridObj().reload();
                    self.progressBar.hide();
                    self.areaWrapper.unlock();
                    return;
                }

                var part = listingProductIds.splice(0, partSize);

                new Ajax.Request(M2ePro.url.get('product_type_validation_url'), {
                    method: 'post',
                    parameters: {
                        listing_product_ids: part.join(',')
                    },
                    onSuccess: function (transport) {
                        var percents = self.progressBar.getPercents() + ((100 * part.length) / totalCount);
                        percents = Math.ceil(percents);

                        if (percents <= 0) {
                            self.progressBar.setPercents(0, 0);
                        } else if (percents >= 100) {
                            self.progressBar.setPercents(100, 0);
                        } else {
                            self.progressBar.setPercents(percents, 0);
                        }

                        setTimeout(function () {
                            sendPart(listingProductIds, totalCount, partSize);
                            self.hideGridDefaultLoader();
                        }, 1);
                    }
                });
            }

            self.progressBar.reset();
            self.areaWrapper.lock();

            var totalItemsCount = listingProductIds.length;
            sendPart(listingProductIds, totalItemsCount, 100);
            self.hideGridDefaultLoader();
        },

        hideGridDefaultLoader: function (){
            $$('.loading-mask').invoke('hide');
        }
    });
});
