define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Action',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function (MessageObj) {

    window.ListingViewAction = Class.create(Action, {

        // ---------------------------------------

        initialize: function($super, gridHandler)
        {
            $super(gridHandler);

            this.messageObj = Object.create(MessageObj);
        },

        // ---------------------------------------

        sendPartsResponses: [],
        errorsSummaryContainerId: 'listing_container_errors_summary',

        // ---------------------------------------

        setProgressBar: function (progressBarId) {
            this.progressBarObj = new ProgressBar(progressBarId);
        },

        setGridWrapper: function (wrapperId) {
            this.gridWrapperObj = new AreaWrapper(wrapperId);
        },

        setErrorsSummaryContainer: function (containerId) {
            this.errorsSummaryContainerId = containerId;
        },

        setActionMessagesContainer: function (containerId) {
            this.messageObj.setContainer('#' + containerId);
        },

        // ---------------------------------------

        startActions: function (title, url, selectedProductsParts, requestParams) {
            var self = this;
            self.messageObj.clear();

            $(self.errorsSummaryContainerId).hide();

            self.progressBarObj.reset();
            self.progressBarObj.show(title);
            self.gridWrapperObj.lock();

            self.sendPartsOfProducts(selectedProductsParts, selectedProductsParts.length, url, requestParams);

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        },

        sendPartsOfProducts: function (parts, totalPartsCount, url, requestParams) {
            var self = this;

            if (parts.length == totalPartsCount) {
                self.sendPartsResponses = new Array();
            }

            if (parts.length == 0) {

                self.progressBarObj.setPercents(100, 0);
                self.progressBarObj.setStatus(M2ePro.translator.translate('task_completed_message'));

                var combineResult = 'success';
                for (var i = 0; i < self.sendPartsResponses.length; i++) {
                    if (self.sendPartsResponses[i].result != 'success' && self.sendPartsResponses[i].result != 'warning') {
                        combineResult = 'error';
                        break;
                    }
                    if (self.sendPartsResponses[i].result == 'warning') {
                        combineResult = 'warning';
                    }
                }

                if (combineResult == 'error') {

                    var message = M2ePro.translator.translate('task_completed_error_message');
                    message = message.replace('%task_title%', self.progressBarObj.getTitle());
                    message = message.replace('%url%', M2ePro.url.get('logViewUrl'));

                    self.messageObj.addError(message);

                    var actionIds = '';
                    for (var i = 0; i < self.sendPartsResponses.length; i++) {
                        if (actionIds != '') {
                            actionIds += ',';
                        }
                        actionIds += self.sendPartsResponses[i].action_id;
                    }

                    new Ajax.Request(M2ePro.url.get('getErrorsSummary') + 'action_ids/' + actionIds + '/', {
                        method: 'get',
                        onSuccess: function (transportSummary) {
                            $(self.errorsSummaryContainerId).innerHTML = transportSummary.responseText;
                            $(self.errorsSummaryContainerId).show();
                        }
                    });

                } else if (combineResult == 'warning') {
                    var message = M2ePro.translator.translate('task_completed_warning_message');
                    message = message.replace('%task_title%', self.progressBarObj.getTitle());
                    message = message.replace('%url%', M2ePro.url.get('logViewUrl'));

                    self.messageObj.addWarning(message);
                } else {
                    var message = M2ePro.translator.translate('task_completed_success_message');
                    message = message.replace('%task_title%', self.progressBarObj.getTitle());

                    self.messageObj.addSuccess(message);
                }

                self.progressBarObj.hide();
                self.progressBarObj.reset();
                self.gridWrapperObj.unlock();
                $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

                self.sendPartsResponses = new Array();

                self.gridHandler.unselectAllAndReload();

                return;
            }

            var part = parts.splice(0, 1);
            part = part[0];
            var partString = implode(',', part);

            var partExecuteString = '';

            if (part.length <= 2 && self.gridHandler.gridId != 'amazonVariationProductManageGrid') {

                for (var i = 0; i < part.length; i++) {

                    if (i != 0) {
                        partExecuteString += ', ';
                    }

                    var temp = self.gridHandler.getProductNameByRowId(part[i]);

                    if (temp != '') {
                        if (temp.length > 75) {
                            temp = temp.substr(0, 75) + '...';
                        }
                        partExecuteString += '"' + temp + '"';
                    } else {
                        partExecuteString = part.length;
                        break;
                    }
                }

            } else {
                partExecuteString = part.length;
            }

            partExecuteString += '';

            self.progressBarObj.setStatus(
                str_replace(
                    '%product_title%',
                    partExecuteString,
                    M2ePro.translator.translate('sending_data_message')
                )
            );

            if (typeof requestParams == 'undefined') {
                requestParams = {}
            }

            requestParams['selected_products'] = partString;

            new Ajax.Request(url + 'id/' + self.gridHandler.listingId, {
                method: 'post',
                parameters: requestParams,
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {

                        if (transport.responseText != '') {
                            self.alert(transport.responseText);
                        }

                        self.progressBarObj.hide();
                        self.progressBarObj.reset();
                        self.gridWrapperObj.unlock();
                        $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

                        self.sendPartsResponses = new Array();

                        self.gridHandler.unselectAllAndReload();

                        return;
                    }

                    var response = transport.responseText.evalJSON(true);

                    if (response.error) {
                        self.progressBarObj.hide();
                        self.progressBarObj.reset();
                        self.gridWrapperObj.unlock();
                        $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

                        self.sendPartsResponses = new Array();

                        self.alert(response.message);

                        return;
                    }

                    self.sendPartsResponses[self.sendPartsResponses.length] = response;

                    var percents = (100 / totalPartsCount) * (totalPartsCount - parts.length);

                    if (percents <= 0) {
                        self.progressBarObj.setPercents(0, 0);
                    } else if (percents >= 100) {
                        self.progressBarObj.setPercents(100, 0);
                    } else {
                        self.progressBarObj.setPercents(percents, 1);
                    }

                    setTimeout(function () {
                        self.sendPartsOfProducts(parts, totalPartsCount, url);
                    }, 500);
                }
            });

            return;
        },

        // ---------------------------------------

        listAction: function () {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('listing_selected_items_message'),
                M2ePro.url.get('runListProducts'),
                selectedProductsParts
            );
        },

        relistAction: function () {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('relisting_selected_items_message'),
                M2ePro.url.get('runRelistProducts'),
                selectedProductsParts
            );
        },

        reviseAction: function () {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('revising_selected_items_message'),
                M2ePro.url.get('runReviseProducts'),
                selectedProductsParts
            );
        },

        stopAction: function () {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('stopping_selected_items_message'),
                M2ePro.url.get('runStopProducts'),
                selectedProductsParts
            );
        },

        stopAndRemoveAction: function () {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts();
            if (selectedProductsParts.length == 0) {
                return;
            }

            this.startActions(
                M2ePro.translator.translate('stopping_and_removing_selected_items_message'),
                M2ePro.url.get('runStopAndRemoveProducts'),
                selectedProductsParts
            );
        },

        previewItemsAction: function () {
            var orderedSelectedProductsArray = this.gridHandler.getOrderedSelectedProductsArray();
            if (orderedSelectedProductsArray.length == 0) {
                return;
            }

            this.openWindow(
                M2ePro.url.get('previewItems') + 'productIds/' + implode(',', orderedSelectedProductsArray)
                + '/currentProductId/' + orderedSelectedProductsArray[0]
            );
        }

        // ---------------------------------------
    });
});
