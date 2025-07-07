define([
    'underscore',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'M2ePro/Walmart/Listing/Create/General/MarketplaceSynchProgress',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function(_, modal) {

    window.WalmartListingCreateGeneral = Class.create({

        marketplaceSynchProgressObj: null,
        accounts: null,
        selectedAccountId: null,
        marketplacesSyncSettings: [],

        // ---------------------------------------

        initialize: function() {
            var self = this;

            self.marketplaceSynchProgressObj = new WalmartListingCreateGeneralMarketplaceSynchProgress(
                new ProgressBar('progress_bar'),
                new AreaWrapper('content_container')
            );

            CommonObj.setValidationCheckRepetitionValue(
                'M2ePro-listing-title',
                M2ePro.translator.translate('The specified Title is already used for other Listing. Listing Title must be unique.'),
                'Listing', 'title', 'id', null, M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK')
            );

            self.initAccount();
            self.initMarketplace();
        },

        initObservers: function() {
            $('store_id').observe('change', WalmartListingCreateGeneralObj.store_id_change);
            $('store_id').simulate('change');

            $('condition_mode')
                .observe('change', WalmartListingCreateGeneralObj.conditionModeChange)
                .simulate('change');
        },

        // ---------------------------------------

        initAccount: function() {
            var self = this;

            $('account_id').observe('change', function() {
                self.selectedAccountId = this.value;

                if (this.value == '') {
                    $('marketplace_info').hide();
                    return;
                }

                var account = _.findWhere(self.accounts, {id: this.value});
                if (!_.isUndefined(account)) {
                    $('marketplace_info').show();
                    $('marketplace_id').value = account.marketplace_id;
                    $('marketplace_title').innerHTML = account.marketplace_title;
                    $('marketplace_url').innerHTML = account.marketplace_url;
                }

                WalmartListingSettingsObj.checkSellingFormatMessages();
            });

            self.renderAccounts();

            $('add_account_button-account-ca').observe('click', function () {
                let popup = jQuery('#account_credentials');
                let specificEndUrl = jQuery(this).attr('data-specific_end_url');

                let input = popup.find('input[name="specific_end_url"]');
                if (input.length) {
                    input.val(specificEndUrl);
                }

                modal({
                    'type': 'popup',
                    'responsive': true,
                    'innerScroll': true,
                    'buttons': []
                }, popup);

                popup.modal('openModal');

                require(['jquery'], function ($) {
                    $('body').off('submit', '#account_credentials').on('submit', '#account_credentials', function (e) {
                        if (!$(this).valid()) {
                            e.preventDefault();
                            return false;
                        }
                    });
                });

                self.renderAccounts();
            });
        },

        initMarketplace: function() {
            var self = this;

            $('save_and_next').observe('click', function() {
                if (self.marketplaceSynchProgressObj.runningNow) {
                    alert({
                        content: M2ePro.translator.translate('Please wait while Synchronization is finished.')
                    });
                    return;
                }
                var marketplaceId = $('marketplace_id').value;
                if (jQuery('#edit_form').valid()) {
                    self.synchronizeMarketplace(marketplaceId)
                }
            });
        },

        setMarketplacesSyncSettings: function (marketplaces) {
            this.marketplacesSyncSettings = marketplaces;
        },

        isMarketplaceSyncWithProductType: function (marketplaceId) {
            return this.marketplacesSyncSettings.some((item) => {
                return item.marketplace_id === marketplaceId
                        && item.is_sync_with_product_type;
            });
        },

        renderAccounts: function(callback) {
            var self = this;

            var account_add_btn = $('add_account_button');
            var account_label_el = $('account_label');
            var account_select_el = $('account_id');
            var marketplace_info = $('marketplace_info');

            //firefox can't simulate events for disabled elements
            if (account_select_el.disabled) {
                account_select_el.enable();
                self.accountDisabled = true;
            }

            new Ajax.Request(M2ePro.url.get('general/getAccounts'), {
                method: 'get',
                parameters: {component: M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK')},
                onSuccess: function (transport) {
                    var accounts = transport.responseText.evalJSON();

                    if (_.isNull(self.accounts)) {
                        self.accounts = accounts;
                    }

                    if (_.isNull(self.selectedAccountId)) {
                        self.selectedAccountId = account_select_el.value;
                    }

                    var isAccountsChanged = !self.isAccountsEqual(accounts);

                    if (isAccountsChanged) {
                        self.accounts = accounts;
                    }

                    if (accounts.length == 0) {
                        account_add_btn.down('span').update(M2ePro.translator.translate('Add'));
                        account_label_el.update(M2ePro.translator.translate('Account not found, please create it.'));
                        account_label_el.show();
                        account_select_el.hide();
                        marketplace_info.hide();
                        return;
                    }

                    marketplace_info.show();

                    account_select_el.update();
                    account_select_el.appendChild(new Element('option', {style: 'display: none'}));
                    accounts.each(function (account) {
                        account_select_el.appendChild(new Element('option', {value: account.id})).insert(account.title);
                    });

                    account_add_btn.down('span').update(M2ePro.translator.translate('Add Another'));

                    if (accounts.length === 1) {
                        var account = _.first(accounts);

                        self.selectedAccountId = account.id;

                        var accountElement;

                        if (M2ePro.formData.wizard) {
                            accountElement = new Element('span').update(account.title);
                        } else {
                            var accountLink = M2ePro.url.get('walmart_account/edit', {
                                'id': account.id,
                                close_on_save: 1
                            });
                            accountElement = new Element('a', {
                                'href': accountLink,
                                'target': '_blank'
                            }).update(account.title);
                        }

                        account_label_el.update(accountElement);

                        account_label_el.show();
                        account_select_el.hide();
                    } else if (isAccountsChanged) {
                        self.selectedAccountId = _.last(accounts).id;

                        account_label_el.hide();
                        account_select_el.show();
                    }

                    account_select_el.setValue(self.selectedAccountId);

                    account_select_el.simulate('change');

                    //firefox can't simulate events for disabled elements
                    if (self.accountDisabled) {
                        account_select_el.disable();
                    }

                    callback && callback();
                }
            });
        },

        synchronizeMarketplace: function(marketplaceId) {
            var self = this;

            new Ajax.Request(M2ePro.url.get('general/isMarketplaceEnabled'), {
                method: 'get',
                parameters: {marketplace_id: marketplaceId},
                onSuccess: function(transport) {

                    var result = transport.responseText.evalJSON();
                    if (result.status) {
                        return self.marketplaceSynchProgressObj.end();
                    }

                    var params = {};
                    params['status_' + marketplaceId] = 1;

                    new Ajax.Request(M2ePro.url.get('walmart_marketplace/save'), {
                        method: 'post',
                        parameters: params,
                        onSuccess: function() {

                            var title = 'Walmart ' + $('marketplace_title').innerHTML;
                            $('save_and_next').disable();

                            if (self.isMarketplaceSyncWithProductType(parseInt(marketplaceId))) {
                                self.marketplaceSynchProgressObj.runTask(
                                        title,
                                        M2ePro.url.get(
                                                'walmart_marketplace_withProductType/runSynchNow',
                                                {marketplace_id: marketplaceId}
                                        ),
                                        M2ePro.url.get('walmart_marketplace_withProductType/synchGetExecutingInfo'),
                                        'WalmartListingCreateGeneralObj.marketplaceSynchProgressObj.end()'
                                );

                                return;
                            }

                            self.marketplaceSynchProgressObj.runTask(
                                title,
                                M2ePro.url.get('walmart_marketplace/runSynchNow', {marketplace_id: marketplaceId}),
                                M2ePro.url.get('walmart_marketplace/synchGetExecutingInfo'),
                                'WalmartListingCreateGeneralObj.marketplaceSynchProgressObj.end()'
                            );
                        }
                    });
                }
            });
        },

        isAccountsEqual: function (newAccounts) {
            if (!newAccounts.length && !this.accounts.length) {
                return true;
            }

            if (newAccounts.length !== this.accounts.length) {
                return false;
            }

            return _.every(this.accounts, function (account) {
                return _.where(newAccounts, account).length > 0;
            });
        },

        // ---------------------------------------

        store_id_change: function () {
            WalmartListingSettingsObj.checkSellingFormatMessages();
        },

        // ---------------------------------------

        conditionModeChange: function () {
            const conditionValue = $('condition_value');
            const conditionCustomAttribute = $('condition_custom_attribute');

            conditionValue.value = '';
            conditionCustomAttribute.value = '';

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Walmart_Listing::CONDITION_MODE_RECOMMENDED')) {
                WalmartListingSettingsObj.updateHiddenValue(this, conditionValue);
            } else {
                WalmartListingSettingsObj.updateHiddenValue(this, conditionCustomAttribute);
            }
        },

    });
});
