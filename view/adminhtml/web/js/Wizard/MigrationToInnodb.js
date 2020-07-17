define([
    'M2ePro/Common'
], function () {

    window.WizardMigrationToInnodb = Class.create(Common, {

        // ---------------------------------------

        continueStep: function ()
        {
            if (WizardObj.steps.current.length) {
                this[WizardObj.steps.current + 'Step']();
            }
        },

        marketplacesSynchronizationStep: function (marketplaceId)
        {
            var button = $('update_all_marketplaces');
            button.addClassName('disabled');
            button.disable();

            if (marketplaceId) {
                this.marketplaceSynchComplete($('status_' + marketplaceId));
            }

            var nextMarketplace = $$('.marketplace-id:not([synchronized]):first');

            if (!nextMarketplace.length) {
                MarketplaceSynchProgressObj.end();
                return;
            }

            nextMarketplace[0].setAttribute('synchronized', 1);
            this.synchronizeMarketplace(nextMarketplace[0].getAttribute('component'), nextMarketplace[0].value);
            this.marketplaceSynchProcess($('status_' + nextMarketplace[0].value));
        },

        // ---------------------------------------

        synchronizeMarketplace: function (component, marketplaceId)
        {
            var title = component + ' ' + $('marketplace_' + marketplaceId).innerHTML;
            component = component.toLowerCase();
            var url = M2ePro.url.get('wizard_migrationToInnodb/runSynchNow')
                + 'component/' + component
                + '/marketplace_id/' + marketplaceId;

            MarketplaceSynchProgressObj.runTask(
                title,
                url,
                M2ePro.url.get(component + '_marketplace/synchGetExecutingInfo'),
                'MigrationToInnodbObj.marketplacesSynchronizationStep(' + marketplaceId + ')'
            );
        },

        // ---------------------------------------

        marketplaceSynchComplete: function (element)
        {
            var span = new Element('span', {class: 'synchComplete', style: 'color: green'});
            span.innerHTML = ' Completed';

            $$('.status-process').each(function(el) {
                el.hide();
            });

            element.appendChild(span);
            element.removeClassName('synchProcess');
            element.addClassName('synchComplete');
        },

        marketplaceSynchProcess: function (element)
        {
            var span = new Element('span', { class: 'synchProcess status-process', style: 'color: blue'});
            span.innerHTML = ' In Progress';
            element.appendChild(span);
            element.addClassName('synchProcess');
        }

        // ---------------------------------------
    });
});
