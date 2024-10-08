define(
    [
        'jquery',
        'M2ePro/Common'
    ],
    function(jQuery) {
        window.WalmartProductTypeTabs = Class.create(Common, {
            tabsContainer: null,
            contentContainer: null,

            tabTemplate: '',
            contentTemplate: '',

            initialize: function()
            {
                this.tabsContainer = $$('#walmartProductTypeEditTabs > ul')[0];
                this.contentContainer = $('tabs_edit_form_data');

                this.tabTemplate = $('walmartProductTypeEditTabs_template_item').outerHTML;
                this.contentTemplate = $('walmartProductTypeEditTabs_template_content').outerHTML;
            },

            insertTab: function (nick, title)
            {
                const tabId = 'walmartProductTypeEditTabs_' + nick;
                if ($(tabId)) {
                    return;
                }

                var temp = new Element('div');
                temp.innerHTML = this.tabTemplate
                    .replaceAll('template', nick)
                    .replaceAll('%title%', title);
                const tab = temp.getElementsByTagName('li')[0];
                tab.style.display = 'block';

                temp = new Element('div');
                temp.innerHTML = this.contentTemplate
                    .replaceAll('template', nick);
                const content = temp.getElementsByTagName('div')[0];

                this.tabsContainer.appendChild(tab);
                this.contentContainer.appendChild(content);
            },

            refreshTabs: function ()
            {
                jQuery('#walmartProductTypeEditTabs').tabs('refresh')
            },

            resetTabs: function (tabs)
            {
                for (var i = 0; i < tabs.length; i++) {
                    if (tabs[i] !== 'general') {
                        this.removeTab(tabs[i]);
                    }
                }
            },

            removeTab: function (nick)
            {
                const tab = $('walmartProductTypeEditTabs_' + nick);
                if (tab !== null) {
                    tab.remove();
                }

                const tabContent = $('walmartProductTypeEditTabs_' + nick + '_content');
                if (tabContent !== null) {
                    tabContent.remove();
                }
            },

            addTabContent: function (tabNick, element)
            {
                $$('#walmartProductTypeEditTabs_' + tabNick + '_content > div')[0].appendChild(element);
            }
        });
    }
);
