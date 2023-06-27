define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Common',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function (MessageObj) {

    window.ListingActionProcessor = Class.create(Common, {

        // ---------------------------------------

        initialize: function(gridHandler)
        {
            this.gridHandler = gridHandler;
            this.messageObj = Object.create(MessageObj);
        },

        // ---------------------------------------

        sendPartsResponses: [],
        errorsSummaryContainerId: 'listing_container_errors_summary',
        sizeOfParts: 100,

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

        setSizeOfParts: function (size) {
            this.sizeOfParts = size;
        },

        // ---------------------------------------

        processActions: function (title, url, requestParams) {
            var selectedProductsParts = this.gridHandler.getSelectedItemsParts(this.sizeOfParts);
            if (selectedProductsParts.length === 0) {
                return;
            }

            this.messageObj.clear();

            $(this.errorsSummaryContainerId).hide();

            this.progressBarObj.reset();
            this.progressBarObj.show(title);
            this.gridWrapperObj.lock();

            this.sendPartsOfProducts(selectedProductsParts, selectedProductsParts.length, url, requestParams);

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        },

        sendPartsOfProducts: function (parts, totalPartsCount, url, requestParams) {
            if (parts.length === totalPartsCount) {
                this.sendPartsResponses = [];
            }

            if (parts.length === 0) {
                this.postProcess();

                return;
            }

            var part = parts.splice(0, 1);
            part = part[0];
            var partString = implode(',', part);

            this.showSendingDataMessage(part);

            if (typeof requestParams == 'undefined') {
                requestParams = {}
            }

            requestParams['selected_products'] = partString;

            var self = this;

            this.sendRequest(url, requestParams, function () {
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
            });
        },

        showSendingDataMessage: function (part) {
            var partExecuteString = '';

            if (part.length <= 2) {

                for (var i = 0; i < part.length; i++) {

                    if (i !== 0) {
                        partExecuteString += ', ';
                    }

                    var temp = this.gridHandler.getProductNameByRowId(part[i]);

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

            this.progressBarObj.setStatus(
                    str_replace(
                            '%product_title%',
                            partExecuteString,
                            M2ePro.translator.translate('sending_data_message')
                    )
            );
        },

        postProcess: function () {
            this.progressBarObj.setPercents(100, 0);
            this.progressBarObj.setStatus(M2ePro.translator.translate('task_completed_message'));

            this.handleResultsOfResponses();

            this.clear();

            this.gridHandler.unselectAllAndReload();
        },

        clear: function () {
            this.progressBarObj.hide();
            this.progressBarObj.reset();
            this.gridWrapperObj.unlock();
            $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

            this.sendPartsResponses = [];
        },

        sendRequest: function (url, requestParams, successCallback) {
            var self = this;

            new Ajax.Request(url, {
                method: 'post',
                parameters: requestParams,
                onSuccess: function (transport) {

                    if (!transport.responseText.isJSON()) {

                        if (transport.responseText !== '') {
                            self.alert(transport.responseText);
                        }

                        self.clear();
                        self.gridHandler.unselectAllAndReload();

                        return;
                    }

                    var response = transport.responseText.evalJSON(true);

                    if (response.error) {
                        self.clear();
                        self.alert(response.message);

                        return;
                    }

                    self.sendPartsResponses[self.sendPartsResponses.length] = response;

                    successCallback();
                }
            });
        },

        handleResultsOfResponses: function () {
            var residualResult = 'success';

            for (var i = 0; i < this.sendPartsResponses.length; i++) {

                var responseResult = this.sendPartsResponses[i].result;

                if (responseResult !== 'success' && responseResult !== 'warning') {
                    residualResult = 'error';
                    break;
                }

                if (responseResult === 'warning') {
                    residualResult = 'warning';
                }
            }

            if (residualResult === 'error') {
                var message = M2ePro.translator.translate('task_completed_error_message');
                message = message.replace('%task_title%', this.progressBarObj.getTitle());

                this.messageObj.addError(message);
                this.showErrors();

                return;
            }

            if (residualResult === 'warning') {
                var message = M2ePro.translator.translate('task_completed_warning_message');
                message = message.replace('%task_title%', this.progressBarObj.getTitle());

                this.messageObj.addWarning(message);

                return;
            }

            if (residualResult === 'success') {
                var message = M2ePro.translator.translate('task_completed_success_message');
                message = message.replace('%task_title%', this.progressBarObj.getTitle());

                this.messageObj.addSuccess(message);
            }
        },

        showErrors: function () {
            var actionIds = '';
            for (var i = 0; i < this.sendPartsResponses.length; i++) {
                if (actionIds != '') {
                    actionIds += ',';
                }
                actionIds += this.sendPartsResponses[i].action_id;
            }

            var self = this;
            new Ajax.Request(M2ePro.url.get('getErrorsSummary') + 'action_ids/' + actionIds + '/', {
                method: 'get',
                onSuccess: function (transportSummary) {
                    $(self.errorsSummaryContainerId).innerHTML = transportSummary.responseText;
                    $(self.errorsSummaryContainerId).show();
                }
            });
        },

        // ---------------------------------------

    })
});
