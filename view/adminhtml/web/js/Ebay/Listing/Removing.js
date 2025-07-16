define([
    'mage/translate',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function ($t, MessagesObj) {
    window.EbayListingOtherRemoving = Class.create(Action, {

        setProgressBar: function (progressBarId) {
            this.progressBarObj = new ProgressBar(progressBarId);
        },

        setGridWrapper: function (wrapperId) {
            this.wrapperObj = new AreaWrapper(wrapperId);
        },

        run: function () {
            this.removeProducts(this.gridHandler.getSelectedProductsString());
        },

        removeProducts: function (product_ids) {
            const self = this;

            if (!product_ids) {
                return;
            }

            const selectedProductsArray = product_ids.split(',');
            const maxProductsInPart = 1000;
            const result = [];

            for (let i = 0; i < selectedProductsArray.length; i++) {
                if (result.length === 0 || result[result.length - 1].length === maxProductsInPart) {
                    result.push([]);
                }
                result[result.length - 1].push(selectedProductsArray[i]);
            }

            const selectedProductsParts = result;

            this.progressBarObj.reset();
            this.progressBarObj.show($t('Removing from eBay'));
            this.wrapperObj.lock();

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

            self.sendPartsOfProducts(selectedProductsParts, selectedProductsParts.length);
        },

        sendPartsOfProducts: function (parts, totalPartsCount) {
            const self = this;

            if (parts.length === 0) {
                MessagesObj.clear();
                MessagesObj.addSuccess($t('Product(s) were successfully removed from eBay.'));

                this.progressBarObj.setStatus($t('Completed.'));
                this.progressBarObj.hide();
                this.progressBarObj.reset();
                this.wrapperObj.unlock();

                self.gridHandler.unselectAllAndReload();
                return;
            }

            const part = parts.splice(0, 1)[0];
            const partString = implode(',', part);

            this.progressBarObj.setStatus($t('Processing...'));

            new Ajax.Request(M2ePro.url.get('removingProducts'), {
                method: 'post',
                parameters: {
                    product_ids: partString
                },
                onSuccess: function (transport) {
                    const response = transport.responseText;
                    if (response === 'removing_error') {
                        MessagesObj.clear();
                        MessagesObj.addError($t('Failed to remove products from eBay. Operation was stopped.'));

                        self.progressBarObj.setStatus($t('Error.'));
                        self.progressBarObj.hide();
                        self.progressBarObj.reset();
                        self.wrapperObj.unlock();

                        return;
                    }

                    const percents = (100 / totalPartsCount) * (totalPartsCount - parts.length);
                    self.progressBarObj.setPercents(Math.min(percents, 100), 1);

                    setTimeout(function () {
                        self.sendPartsOfProducts(parts, totalPartsCount);
                    }, 500);
                }
            });
        }
    });
});
