define([
    'M2ePro/Ebay/Listing/Create/General/MarketplaceSynchProgress',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function () {

    window.EbayListingCreateGeneral = Class.create();
    EbayListingCreateGeneral.prototype = {

        // ---------------------------------------

        initialize: function (marketplaces) {
            var initAccount = function() {

                var renderAccounts = function (callback) {

                    var account_add_btn = $('add_account_button');
                    var account_label_el = $('account_label');
                    var account_select_el = $('account_id');

                    new Ajax.Request(M2ePro.url.get('general/getAccounts'), {
                        method: 'get',
                        onSuccess: function(transport) {
                            var accounts = transport.responseText.evalJSON();

                            if (accounts.length == 0) {
                                account_add_btn.down('span').update(M2ePro.translator.translate('Add'));
                                account_label_el.update(M2ePro.translator.translate('Account not found, please create it.'));
                                account_label_el.show();
                                account_select_el.hide();
                                return;
                            }

                            account_select_el.update();
                            account_select_el.appendChild(new Element('option', {style: 'display: none'}));
                            accounts.each(function(account) {
                                account_select_el.appendChild(new Element('option', {value: account.id})).insert(account.title);
                            });

                            account_add_btn.down('span').update(M2ePro.translator.translate('Add Another'));

                            if (accounts.length == 1) {
                                var account = accounts.shift();

                                account_select_el.setValue(account.id);

                                var accountElement;

                                if (M2ePro.formData.wizard) {
                                    accountElement = new Element('span').update(account.title);
                                } else {
                                    var accountLink = M2ePro.url.get('ebay_account/edit', {'id': account.id});
                                    accountElement = new Element('a', {
                                        'href': accountLink,
                                        'target': '_blank'
                                    }).update(account.title);
                                }

                                account_label_el.update(accountElement);

                                account_label_el.show();
                                account_select_el.hide();
                            } else {
                                account_select_el.setValue(accounts.pop().id);

                                account_label_el.hide();
                                account_select_el.show();
                            }

                            callback && callback();
                        }
                    });
                };

                $('add_account_button').observe('click', function() {
                    var win = window.open(M2ePro.url.get('ebay_account/newAction'));

                    var intervalId = setInterval(function() {

                        if (!win.closed) {
                            return;
                        }

                        clearInterval(intervalId);

                        renderAccounts();

                    }, 1000);
                });

                renderAccounts();
            };

            var initMarketplace = function(marketplaces) {

                var marketplaceSynchProgressHandlerObj = new EbayListingCreateGeneralMarketplaceSynchProgress(
                    new ProgressBar('progress_bar'),
                    new AreaWrapper('content_container')
                );

                var synchronizeMarketplace = function(marketplaceId) {

                    new Ajax.Request(M2ePro.url.get('general/isMarketplaceEnabled'), {
                        method: 'get',
                        parameters: { marketplace_id: marketplaceId },
                        onSuccess: function(transport) {

                            var result = transport.responseText.evalJSON();
                            if (result.status) {
                                return marketplaceSynchProgressHandlerObj.end();
                            }

                            var params = {};
                            params['status_' + marketplaceId] = 1;

                            new Ajax.Request(M2ePro.url.get('ebay_marketplace/save'), {
                                method: 'post',
                                parameters: params,
                                onSuccess: function() {

                                    var title = 'eBay ' + $('marketplace_id').down('option[value='+$('marketplace_id').value+']').innerHTML;

                                    $('next').disable();

                                    marketplaceSynchProgressHandlerObj.runTask(
                                        title,
                                        M2ePro.url.get('ebay_marketplace/runSynchNow') + 'marketplace_id/' + marketplaceId
                                    );

                                }
                            });
                        }
                    });
                };

                $$('.next_step_button').each(function(btn) { btn.observe('click',function() {
                    if (marketplaceSynchProgressHandlerObj.runningNow) {
                        return alert(M2ePro.translator.translate('Please wait while Synchronization is finished.'));
                    }
                    jQuery('#edit_form').valid() && synchronizeMarketplace($('marketplace_id').value);
                }) });

                $('marketplace_id')
                    .observe('click', function() {
                        if (!this.value) {
                            return;
                        }
                        $('marketplace_url_note').update(marketplaces[this.value].url).show();
                    })
                    .simulate('click')
                ;
            };

            CommonObj.setValidationCheckRepetitionValue(
                'M2ePro-listing-title',
                M2ePro.translator.translate('The specified Title is already used for other Listing. Listing Title must be unique.'),
                'Listing', 'title', 'id', null, M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay::NICK')
            );

            initAccount();
            initMarketplace(marketplaces);
        },

        // ---------------------------------------
    };
});