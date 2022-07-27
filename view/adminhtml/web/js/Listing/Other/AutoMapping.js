define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Action',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function (MessagesObj) {
    window.ListingOtherAutoMapping = Class.create(Action, {

        // ---------------------------------------

        setProgressBar: function (progressBarId) {
            this.progressBarObj = new ProgressBar(progressBarId);
        },

        setGridWrapper: function (wrapperId) {
            this.wrapperObj = new AreaWrapper(wrapperId);
        },

        // ---------------------------------------

        run: function()
        {
            this.mapProductsAuto(
                this.gridHandler.getSelectedProductsString()
            );
        },

        // ---------------------------------------

        mapProductsAuto: function(product_ids)
        {
            var self = this;
            var selectedProductsString = product_ids;
            var selectedProductsArray = selectedProductsString.split(",");

            if (selectedProductsString == '' || selectedProductsArray.length == 0) {
                return;
            }

            var maxProductsInPart = 10;

            var result = [];
            for (var i=0;i<selectedProductsArray.length;i++) {
                if (result.length == 0 || result[result.length-1].length == maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length-1][result[result.length-1].length] = selectedProductsArray[i];
            }

            var selectedProductsParts = result;

            this.progressBarObj.reset();
            this.progressBarObj.show(M2ePro.translator.translate('automap_progress_title'));
            this.wrapperObj.lock();
            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

            self.sendPartsOfProducts(selectedProductsParts,selectedProductsParts.length,0);
        },

        sendPartsOfProducts: function(parts,totalPartsCount,isFailed)
        {
            var self = this;

            if (parts.length == 0) {
                MessagesObj.clear();

                if (isFailed == 1) {
                    MessagesObj.addError(M2ePro.translator.translate('failed_mapped'));
                } else {
                    MessagesObj.addSuccess(M2ePro.translator.translate('Product was Linked.'));
                }

                this.progressBarObj.setStatus(M2ePro.translator.translate('task_completed_message'));
                this.progressBarObj.hide();
                this.progressBarObj.reset();
                this.wrapperObj.unlock();
                $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

                self.gridHandler.unselectAllAndReload();

                return;
            }

            var part = parts.splice(0,1);
            part = part[0];
            var partString = implode(',',part);

            var partExecuteString = part.length;
            partExecuteString += '';

            this.progressBarObj.setStatus(str_replace('%product_title%', partExecuteString, M2ePro.translator.translate('processing_data_message')));

            new Ajax.Request(M2ePro.url.get('mapAutoToProduct'), {
                method: 'post',
                parameters: {
                    componentMode: M2ePro.customData.componentMode,
                    product_ids: partString
                },
                onSuccess: function(transport) {

                    var percents = (100/totalPartsCount)*(totalPartsCount-parts.length);

                    if (percents <= 0) {
                        self.progressBarObj.setPercents(0,0);
                    } else if (percents >= 100) {
                        self.progressBarObj.setPercents(100,0);
                    } else {
                        self.progressBarObj.setPercents(percents,1);
                    }

                    if (transport.responseText == 1) {
                        isFailed = 1;
                    }

                    setTimeout(function() {
                        self.sendPartsOfProducts(parts,totalPartsCount,isFailed);
                    },500);
                }
            });
        }

        // ---------------------------------------
    });
});
