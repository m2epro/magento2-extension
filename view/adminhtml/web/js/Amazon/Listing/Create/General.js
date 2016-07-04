define([
    'M2ePro/Amazon/Listing/Create/General/MarketplaceSynchProgress',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function () {

    window.AmazonListingCreateGeneral = Class.create();
    AmazonListingCreateGeneral.prototype = {

        // ---------------------------------------

        initialize: function () {
            var accountsList;
            var initAccount = function() {

                var renderAccounts = function (callback) {

                    var account_add_btn = $('add_account_button');
                    var account_label_el = $('account_label');
                    var account_select_el = $('account_id');
                    var marketplace_info = $('marketplace_info');

                    new Ajax.Request(M2ePro.url.get('general/getAccounts'), {
                        method: 'get',
                        onSuccess: function(transport) {
                            accountsList = transport.responseText.evalJSON();

                            if (accountsList.length == 0) {
                                account_add_btn.down('span').update(M2ePro.translator.translate('Add'));
                                account_label_el.update(M2ePro.translator.translate('Account not found, please create it.'));
                                account_label_el.show();
                                account_select_el.hide();
                                account_note.hide();
                                marketplace_info.hide();
                                return;
                            }

                            marketplace_info.show();

                            account_select_el.update();
                            account_select_el.appendChild(new Element('option', {style: 'display: none'}));
                            accountsList.each(function(account) {
                                account_select_el.appendChild(new Element('option', {value: account.id})).insert(account.title);
                            });

                            account_add_btn.down('span').update(M2ePro.translator.translate('Add Another'));

                            if (accountsList.length == 1) {
                                var account = accountsList[0];

                                account_select_el.setValue(account.id);
                                var accountElement;

                                if (M2ePro.formData.wizard) {
                                    accountElement = new Element('span').update(account.title);
                                } else {
                                    var accountLink = M2ePro.url.get('amazon_account/edit', {'id': account.id});
                                    accountElement = new Element('a', {
                                        'href': accountLink,
                                        'target': '_blank'
                                    }).update(account.title);
                                }

                                account_label_el.update(accountElement);

                                account_label_el.show();
                                account_select_el.hide();
                            } else {
                                account_select_el.setValue(accountsList[0].id);

                                account_label_el.hide();
                                account_select_el.show();
                            }

                            //firefox can't simulate events for disabled elements
                            if (account_select_el.disabled) {
                                account_select_el.enable();
                                account_select_el.simulate('change');
                                account_select_el.disable();
                            } else {
                                account_select_el.simulate('change');
                            }

                            callback && callback();
                        }
                    });
                };

                $('account_id').observe('change', function() {
                    if ($('account_id').getValue() == '') {
                        $('marketplace_info').hide();
                        return;
                    }

                    accountsList.each(function(account){
                        if (account.id != '' && account.id == $('account_id').getValue()) {
                            $('marketplace_info').show();
                            $('marketplace_id').value = account.marketplace_id;
                            $('marketplace_title').innerHTML = account.marketplace_title;
                            $('marketplace_url').innerHTML = account.marketplace_url;
                            throw $break;
                        }
                    });
                });

                renderAccounts(function() {
                    $('account_id').simulate('change');
                });

                $('add_account_button').observe('click', function() {
                    var win = window.open(M2ePro.url.get('amazon_account/newAction'));

                    var intervalId = setInterval(function() {

                        if (!win.closed) {
                            return;
                        }

                        clearInterval(intervalId);

                        renderAccounts();

                    }, 1000);
                });
            };

            var initMarketplace = function() {

                var marketplaceSynchProgressHandlerObj = new AmazonListingCreateGeneralMarketplaceSynchProgress(
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

                            var title = 'Amazon ' + $('marketplace_title').innerHTML;

                            $('save_and_next').disable();

                            marketplaceSynchProgressHandlerObj.runTask(
                                title,
                                M2ePro.url.get('amazon_marketplace/runSynchNow') + 'marketplace_id/' + marketplaceId
                            );

                        }
                    });
                };

                 $('save_and_next').observe('click',function() {
                    if (marketplaceSynchProgressHandlerObj.runningNow) {
                        return alert(M2ePro.translator.translate('Please wait while Synchronization is finished.'));
                    }
                    jQuery('#edit_form').valid() && synchronizeMarketplace($('marketplace_id').value);
                });
            };

            CommonObj.setValidationCheckRepetitionValue(
                'M2ePro-listing-title',
                M2ePro.translator.translate('The specified Title is already used for other Listing. Listing Title must be unique.'),
                'Listing', 'title', 'id', null, M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK')
            );

            initAccount();
            initMarketplace();
        },

        // ---------------------------------------
    };
});