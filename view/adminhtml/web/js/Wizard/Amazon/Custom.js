define(function () {

    window.WizardAmazonCustom = Class.create();
    WizardAmazonCustom.prototype = {

        // ---------------------------------------

        initialize: function()
        {
            this.marketplaces = [];
            this.index = 0;
            this.marketplacesLastIndex = 0;
            this.percent = 0;
        },

        // ---------------------------------------

        marketplacesSynchronizationAction: function(obj)
        {
            obj.hide();
            var self = this;

            self.progressBarStartLoad(0);
            setTimeout(function() { self.synchronizeMarketplaces(); }, 0);
        },

        // ---------------------------------------

        setNextStep: function(nextStep)
        {
            var currentStep = WizardObj.steps.current;
            WizardObj.setStep(nextStep, function() {
                WizardObj.renderStep(currentStep);
            });

            return this;
        },

        setMarketplacesData: function(marketplaces)
        {
            this.marketplaces = marketplaces;
            this.marketplacesLastIndex = marketplaces.length - 1;
            return this;
        },

        getMarketplacesData: function()
        {
            return this.marketplaces;
        },

        completeStep: function(step)
        {
            WizardObj.skipStep(step);
            $('wizard_complete').show();
        },

        // ---------------------------------------

        synchronizeMarketplaces: function()
        {
            if (this.index > this.marketplacesLastIndex) {
                $('custom-progressbar').hide();

                this.completeStep('marketplacesSynchronization');
                return;
            }

            var self = this,
                marketplaces = this.getMarketplacesData(),
                marketplaceId = marketplaces[self.index] != undefined ?
                    marketplaces[self.index].id : 0,
                current = $$('.code-'+ marketplaces[self.index].code)[0];

            if (marketplaceId <= 0) {
                this.completeStep('marketplacesSynchronization');
            }

            ++this.index;

            var startPercent = self.percent;
            self.percent += Math.round(100 / marketplaces.length);
            self.marketplaceSynchProcess(current);

            new Ajax.Request(M2ePro.url.get('marketplacesSynchronization'), {
                method: 'get',
                parameters: {
                    id: marketplaceId
                },
                asynchronous: true,
                onSuccess: (function(transport) {

                    if (transport.responseText == 'success') {
                        self.progressBarStartLoad(
                            startPercent + 1, self.percent,
                            function() {
                                self.marketplaceSynchComplete(current);
                                self.synchronizeMarketplaces();
                            }
                        );
                    }

                    return false;
                }).bind(this)
            })
        },

        // ---------------------------------------

        progressBarStartLoad: function(from, to, callback)
        {
            var self = this,
                progressBar = $('custom-progressbar'),
                progressBarLoad = $('custom-progressbar-load'),
                progressBarPercent = $('custom-progressbar-percentage'),
                step = 2,
                total = from;

            if (from == 0 || to == 0) {
                progressBarLoad.style.width = '0px';
                progressBarPercent.innerHTML = '0%';
                progressBar.show();
                return;
            }

            progressBarLoad.style.width = 0 + 'px';
            progressBarPercent.innerHTML = 0 + '%';
            progressBar.show();

            var interval = setInterval(function() {
                progressBarLoad.style.width = total * 3 + 'px';
                progressBarPercent.innerHTML = total + '%';

                total += step;

                if (total >= to) {
                    clearInterval(interval);
                    callback && callback();
                }
            }, 100);
        },

        marketplaceSynchComplete: function(element)
        {
            var span = new Element('span', {class: 'synchComplete'});
            span.innerHTML = ' (Completed)';

            $$('.status-process').each(function(el) {
                el.hide();
            });

            element.appendChild(span);
            element.removeClassName('synchProcess');
            element.addClassName('synchComplete');
        },

        marketplaceSynchProcess: function(element)
        {
            var span = new Element('span', { class: 'synchProcess status-process'});
            span.innerHTML = ' (In Progress)';
            element.appendChild(span);
            element.addClassName('synchProcess');
        }

        // ---------------------------------------
    };
});