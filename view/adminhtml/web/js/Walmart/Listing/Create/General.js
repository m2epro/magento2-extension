define([
    'underscore',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function (_) {

    window.WalmartListingCreateGeneral = Class.create();
    WalmartListingCreateGeneral.prototype = {

        accounts: null,
        selectedAccountId: null,

        // ---------------------------------------

        initialize: function () {
            var self = this;

            CommonObj.setValidationCheckRepetitionValue(
                'M2ePro-listing-title',
                M2ePro.translator.translate('The specified Title is already used for other Listing. Listing Title must be unique.'),
                'Listing', 'title', 'id', null, M2ePro.php.constant('Ess_M2ePro_Helper_Component_Walmart::NICK')
            );

            self.initAccount();
        },

        initAccount: function () {
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

                WalmartListingSettingsObj.checkMessages();
            });

            self.renderAccounts();

            $('add_account_button').observe('click', function() {
                var win = window.open(M2ePro.url.get('walmart_account/newAction'));

                var intervalId = setInterval(function() {

                    if (!win.closed) {
                        return;
                    }

                    clearInterval(intervalId);

                    self.renderAccounts();

                }, 1000);
            });
        },

        renderAccounts: function (callback) {
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
                onSuccess: function(transport) {
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
                    accounts.each(function(account) {
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
                            var accountLink = M2ePro.url.get('walmart_account/edit', {'id': account.id, close_on_save: 1});
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

        store_id_change: function ()
        {
            WalmartListingSettingsObj.checkMessages();
        },

        // ---------------------------------------
    };
});