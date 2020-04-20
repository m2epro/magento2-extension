define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function (MessagesObj) {
    window.ListingOtherAutoMapping = Class.create(Action, {

        // ---------------------------------------

        options: {},

        setOptions: function(options)
        {
            this.options = Object.extend(this.options,options);
            return this;
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

            ListingProgressBarObj.reset();
            ListingProgressBarObj.show(self.options.translator.translate('automap_progress_title'));
            GridWrapperObj.lock();
            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

            self.sendPartsOfProducts(selectedProductsParts,selectedProductsParts.length,0);
        },

        sendPartsOfProducts: function(parts,totalPartsCount,isFailed)
        {
            var self = this;

            if (parts.length == 0) {
                MessagesObj.clear();

                if (isFailed == 1) {
                    MessagesObj.addErrorMessage(self.options.translator.translate('failed_mapped'));
                } else {
                    MessagesObj.addSuccessMessage(self.options.translator.translate('successfully_mapped'));
                }

                ListingProgressBarObj.setStatus(self.options.translator.translate('task_completed_message'));
                ListingProgressBarObj.hide();
                ListingProgressBarObj.reset();
                GridWrapperObj.unlock();
                $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});

                self.gridHandler.unselectAllAndReload();

                return;
            }

            var part = parts.splice(0,1);
            part = part[0];
            var partString = implode(',',part);

            var partExecuteString = part.length;
            partExecuteString += '';

            ListingProgressBarObj.setStatus(str_replace('%product_title%', partExecuteString, self.options.translator.translate('processing_data_message')));

            new Ajax.Request(self.options.url.get('mapAutoToProduct'), {
                method: 'post',
                parameters: {
                    componentMode: self.options.customData.componentMode,
                    product_ids: partString
                },
                onSuccess: function(transport) {

                    var percents = (100/totalPartsCount)*(totalPartsCount-parts.length);

                    if (percents <= 0) {
                        ListingProgressBarObj.setPercents(0,0);
                    } else if (percents >= 100) {
                        ListingProgressBarObj.setPercents(100,0);
                    } else {
                        ListingProgressBarObj.setPercents(percents,1);
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