define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Messages',
    'M2ePro/Action'
], function ($, $t, modal, MessageObj) {

    window.WalmartListingProductRepricer = Class.create(Action, {

        getAssignPopupHtmlUrl: M2ePro.url.get('template_repricer/viewPopup'),
        getAssignGridUrl: M2ePro.url.get('template_repricer/viewGrid'),
        newTemplateUrl: M2ePro.url.get('template_repricer/newTemplate'),
        assignUrl: M2ePro.url.get('template_repricer/assign'),
        unAssignUrl: M2ePro.url.get('template_repricer/unassign'),

        productIds: undefined,

        initialize: function ($super, gridHandler) {
            $super(gridHandler);
        },

        openPopUp: function (productsIds) {
            this.gridHandler.unselectAll();

            const requestParams = {
                products_ids: productsIds
            }

            this._ajaxRequest(this.getAssignPopupHtmlUrl, requestParams).then((response) => {
                if (!response.html) {
                    if (response.messages.length > 0) {
                        this._printMessages(response.messages);
                    }
                }

                this.productIds = response.products_ids;

                this._createPopup(response.html);
            });
        },

        unassign: function (id) {
            let requestData = {
                products_ids: id
            };

            this._ajaxRequest(this.unAssignUrl, requestData, 'POST').then((response) => {
                this.gridHandler.unselectAllAndReload();
                this._printMessages(response.messages);
            });
        },

        loadRepricerPolicyGrid: function () {
            const requestParams = {
                products_ids: this.productIds,
            }

            this._ajaxRequest(this.getAssignGridUrl, requestParams, 'GET', 'html').then((response) => {
                $('#template_repricer_grid').html(response).show();
            })
        },

        _ajaxRequest: function (url, requestData = {}, method = 'GET', dataType = 'json') {
            const setLoaderStatus = function (status) {
                $('body').loader(status)
            }

            let requestConfig = {
                url: url,
                type: method,
                dataType: dataType,
                data: requestData,
                beforeSend: () => setLoaderStatus('show'),
                complete: () => setLoaderStatus('hide'),
            };

            if (method.toLowerCase() === 'post') {
                requestConfig.data.form_key = FORM_KEY;
                requestConfig.contentType = 'application/x-www-form-urlencoded';
            }

            return $.ajax(requestConfig);
        },

        _createPopup: function (popupHtml) {
            const popupElId = '#template_repricer_pop_up_content';

            const popupEl = $(popupElId);
            if (popupEl.length) {
                popupEl.remove();
            }

            $('#html-body').append(popupHtml);

            this.templateRepricerPopup = $(popupElId);

            modal({
                title: $t('Assign Repricer Template Policy'),
                type: 'slide',
                buttons: [{
                    text: $t('Cancel'),
                    click: () => this.templateRepricerPopup.modal('closeModal'),
                }, {
                    text: $t('Add New Repricer Policy'),
                    class: 'action primary ',
                    click: () => this._createInNewTab(this.newTemplateUrl),
                }]
            }, this.templateRepricerPopup);

            this.templateRepricerPopup.modal('openModal');

            $(this.templateRepricerPopup).on('click', '.assign-repricer-template', (event) => {
                this._assign($(event.currentTarget).attr('data-template-id'));
            });

            $(this.templateRepricerPopup).on('click', '.new-repricer-template', () => {
                this._createInNewTab(this.newTemplateUrl);
            })

            this.loadRepricerPolicyGrid();
        },

        _assign: function (repricerTemplateId) {
            this.confirm({
                actions: {
                    confirm: () => {
                        const requestData = {
                            products_ids: this.productIds,
                            template_id: repricerTemplateId
                        }

                        this._ajaxRequest(this.assignUrl, requestData, 'POST').then((response) => {
                            this.gridHandler.unselectAllAndReload();
                            this._printMessages(response.messages);
                        });

                        this.templateRepricerPopup.modal('closeModal');
                    },
                    cancel: () => false
                }
            });
        },

        _createInNewTab: function (url) {
            const win = window.open(url);

            const intervalId = setInterval(() => {
                if (!win.closed) {
                    return;
                }

                clearInterval(intervalId);

                this.loadRepricerPolicyGrid();
            }, 1000);
        },

        _printMessages: function (messages) {
            MessageObj.clear();
            messages.reverse().each((msg) => {
                switch (msg.type.toLowerCase()) {
                    case 'warning':
                        MessageObj.addWarning(msg.text);
                        break;
                    case 'error':
                        MessageObj.addError(msg.text);
                        break;
                    case 'success':
                        MessageObj.addSuccess(msg.text);
                        break;
                    default:
                        MessageObj.addNotice(msg.text);
                }
            });
        }
    });
});
