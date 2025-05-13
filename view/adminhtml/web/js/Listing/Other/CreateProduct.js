define([
    'mage/translate',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper',
], function ($t, MessagesObj) {
    window.ListingOtherCreateProduct = Class.create(Action, {

        // ---------------------------------------

        setProgressBar: function (progressBarId) {
            this.progressBarObj = new ProgressBar(progressBarId);
        },

        setGridWrapper: function (wrapperId) {
            this.wrapperObj = new AreaWrapper(wrapperId);
        },

        // ---------------------------------------

        run: function () {
            this.mapProductsAuto(
                    this.gridHandler.getSelectedProductsString()
            );
        },

        // ---------------------------------------

        mapProductsAuto: function (product_ids) {
            var self = this;
            var selectedProductsString = product_ids;
            var selectedProductsArray = selectedProductsString.split(",");

            if (selectedProductsString == '' || selectedProductsArray.length == 0) {
                return;
            }

            var maxProductsInPart = 10;

            var result = [];
            for (var i = 0; i < selectedProductsArray.length; i++) {
                if (result.length == 0 || result[result.length - 1].length == maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length - 1][result[result.length - 1].length] = selectedProductsArray[i];
            }

            var selectedProductsParts = result;

            this.progressBarObj.reset();
            this.progressBarObj.show($t('Creating Product Progress'));
            this.wrapperObj.lock();

            const partsStatistic = {fail: 0, partial_success: 0, success: 0}

            self.sendPartsOfProducts(selectedProductsParts, selectedProductsParts.length, partsStatistic);
        },

        sendPartsOfProducts: function (parts, totalPartsCount, partsStatistic) {
            var self = this;

            if (parts.length == 0) {
                MessagesObj.clear();

                self.printResultMessage(partsStatistic);

                this.progressBarObj.hide();
                this.progressBarObj.reset();
                this.wrapperObj.unlock();

                self.gridHandler.unselectAllAndReload();

                return;
            }

            var part = parts.splice(0, 1);
            part = part[0];
            var partString = implode(',', part);

            this.progressBarObj.setStatus($t('Processing...'));

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
            new Ajax.Request(M2ePro.url.get('createProductAndMap'), {
                method: 'post',
                parameters: {
                    componentMode: M2ePro.customData.componentMode,
                    product_ids: partString
                },
                onSuccess: function (transport) {
                    var percents = (100 / totalPartsCount) * (totalPartsCount - parts.length);

                    if (percents <= 0) {
                        self.progressBarObj.setPercents(0, 0);
                    } else if (percents >= 100) {
                        self.progressBarObj.setPercents(100, 0);
                    } else {
                        self.progressBarObj.setPercents(percents, 1);
                    }

                    response = JSON.parse(transport.responseText);
                    if (response.fail_count == part.length) {
                        partsStatistic.fail += 1;
                    } else if (response.fail_count > 0) {
                        partsStatistic.partial_success += 1;
                    } else {
                        partsStatistic.success += 1;
                    }

                    if (response.fail_messages) {
                        response.fail_messages.forEach((message) => {
                            console.error(message)
                        })
                    }

                    setTimeout(function () {
                        self.sendPartsOfProducts(parts, totalPartsCount, partsStatistic);
                    }, 500);
                }
            });
        },

        printResultMessage: function (partsStatistic) {
            if (
                    partsStatistic.partial_success == 0
                    && partsStatistic.fail == 0
            ) {
                MessagesObj.addSuccess($t('Selected Products were successfully created in Magento.'));

                return;
            }

            if (
                    partsStatistic.partial_success == 0
                    && partsStatistic.success == 0
            ) {
                MessagesObj.addError($t('Selected Products could not be created in Magento.'));

                return;
            }

            MessagesObj.addWarning($t('Some of the selected Products could not be created in Magento.'));
        }

        // ---------------------------------------
    });
});
