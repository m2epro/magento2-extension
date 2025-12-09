define([
    'jquery',
    'mage/translate',
    'mage/loader',
    'M2ePro/Common'
], function ($, $t) {
    window.WalmartTemplateSellingFormatRepricerStrategy = Class.create(Common, {
        refreshButtonSelector: undefined,
        selectPrefix: undefined,
        getStrategiesUrl: undefined,

        initialize: function (config) {
            this.refreshButtonSelector = config.refreshButtonSelector;
            this.selectPrefix = config.selectPrefix;
            this.getStrategiesUrl = config.getStrategiesUrl;

            this.initObservers()
        },

        initObservers: function () {
            $(this.refreshButtonSelector).on('click', this.refreshButtonClick.bind(this))
        },

        refreshButtonClick: function (event) {
            const element = $(event.currentTarget);
            const accountId = element.attr('data-account-id');
            const strategySelect = $(`#${this.selectPrefix}${accountId}`);

            this.getStrategies(accountId)
                    .then((strategies) => this.updateSelectOptions(strategies, strategySelect))
        },

        getStrategies: function (accountId) {
            return $.ajax({
                url: this.getStrategiesUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    accountId: accountId
                },
                beforeSend: () => this.loader('show'),
                complete: () => this.loader('hide'),
            });
        },

        updateSelectOptions: function (categories, strategySelect) {
            const selectedValue = strategySelect.val();
            strategySelect.empty();
            strategySelect.append($('<option>', {value: '', text: $t('None')}))
            $.each(categories, function(index, item) {
                strategySelect.append($('<option>', {value: item.id, text: item.title}));
            });
            strategySelect.val(selectedValue);
        },

        loader: function (status) {
            $('body').loader(status)
        }
    })
})
