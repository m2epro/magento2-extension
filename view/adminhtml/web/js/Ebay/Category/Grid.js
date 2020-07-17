define([
    'M2ePro/Grid'
], function () {
    window.EbayCategoryGrid = Class.create(Grid, {

        // ---------------------------------------

        accountId     : null,
        marketplaceId : null,
        templateId    : null,

        // ---------------------------------------

        initialize: function($super, gridId, marketplaceId, accountId, templateId)
        {
            this.templateId    = templateId;
            this.marketplaceId = marketplaceId;
            this.accountId     = accountId;

            $super(gridId);
        },

        // ---------------------------------------

        prepareActions: function () {
            this.actions = {
                resetSpecificsToDefaultAction: function () {
                    this.resetSpecificsToDefault();
                }.bind(this),
                editEbayCategoryAction: function () {
                    EbayListingCategoryObj.editCategorySettings();
                }
            };
        },

        // ---------------------------------------

        confirm: function ($super, config) {

            var action = '';
            $$('select#'+this.gridId+'_massaction-select option').each(function(o) {
                if (o.selected && o.value != '') {
                    action = o.value;
                }
            });

            if (action === 'resetSpecificsToDefault') {
                if (!confirm(M2ePro.translator.translate('Are you sure?'))) {
                    return;
                }
            }

            if (config.actions && config.actions.confirm) {
                config.actions.confirm();
            }
        },

        // ---------------------------------------

        resetSpecificsToDefault: function () {
            new Ajax.Request(M2ePro.url.get('ebay_listing/resetSpecificsToDefault'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids : this.getSelectedProductsString(),
                    template_id  : this.templateId
                },
                onSuccess: function (transport) {
                    this.unselectAllAndReload();
                }.bind(this)
            });
        }

        // ---------------------------------------
    });

});