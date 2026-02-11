define([
    'jquery',
    'mage/translate',
    'M2ePro/Walmart/Template/Edit',
], function ($, $t) {
    window.WalmartTemplateRepricer = Class.create(WalmartTemplateEdit, {

        accountSelect: $('#account'),

        strategyNameSelect: $('#strategy_name_select'),
        strategyNameHiddenInput: $('#strategy_name'),

        minPriceModeSelect: $('#min_price_mode'),
        minPriceAttributeHiddenInput: $('#min_price_attribute'),

        maxPriceModeSelect: $('#max_price_mode'),
        maxPriceAttributeHiddenInput: $('#max_price_attribute'),

        refreshStrategiesButton: $('.refresh-strategies'),

        getStrategiesUrl: undefined,

        initialize: function (config) {
            this.getStrategiesUrl = config['getStrategiesUrl'];

            this.initObservers();
            this.addValidation();
        },

        initObservers: function () {
            this.refreshStrategies(false);

            this.accountSelect.change(() => {
                this.refreshStrategies(false);
            });

            this.refreshStrategiesButton.click(() => {
                this.refreshStrategies(true);
            })

            this.strategyNameSelect.change((event) => {
                this.strategyNameHiddenInput.val($(event.currentTarget).val());
            });

            this.minPriceModeSelect.change((event) => {
                const newValue = $(event.currentTarget).find('option:selected').attr('attribute_code');
                this.minPriceAttributeHiddenInput.val(newValue);
            });

            this.maxPriceModeSelect.change((event) => {
                const newValue = $(event.currentTarget).find('option:selected').attr('attribute_code');
                this.maxPriceAttributeHiddenInput.val(newValue);
            });
        },

        addValidation: function () {
            $.validator.addMethod('M2ePro-required-when-visible', (value, el) => {
                if (this.isElementHiddenFromPage(el)) {
                    return true;
                }

                if(typeof value === 'string' ) {
                    value = value.trim();
                }

                if (value === '0') {
                    value = null;
                }

                return value != null && value.length > 0;
            }, $t('This is a required field.'));
        },

        refreshStrategies: function (forceLoad) {
            this._getStrategies(forceLoad)
                    .then((strategies) => this._updateSelectOptions(strategies));
        },

        _getStrategies: function (forceLoad) {
            return $.ajax({
                url: this.getStrategiesUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    accountId: this.accountSelect.val(),
                    force_load: Number(forceLoad),
                },
                beforeSend: () => this.loader('show'),
                complete: () => this.loader('hide'),
            });
        },

        _updateSelectOptions: function (categories) {
            const strategySelect = this.strategyNameSelect
            const selectedValue = this.strategyNameHiddenInput.val();

            strategySelect.empty();
            if (selectedValue === '') {
                strategySelect.append($('<option>', {value: '', text: $t('None')}))
            }

            $.each(categories, function(index, item) {
                strategySelect.append($('<option>', {value: item.id, text: item.title}));
            });
            strategySelect.val(selectedValue);
        },

        loader: function (status) {
            $('body').loader(status)
        }
    });
});
