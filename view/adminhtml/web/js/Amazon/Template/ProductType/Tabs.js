define(
    [
        'jquery',
        'M2ePro/Common'
    ],
    function(jQuery) {
        window.AmazonTemplateProductTypeTabs = Class.create(Common, {
            tabsContainer: null,
            contentContainer: null,

            tabTemplate: '',
            contentTemplate: '',

            initialize: function()
            {
                this.tabsContainer = $$('#amazonTemplateProductTypeEditTabs > ul')[0];
                this.contentContainer = $('tabs_edit_form_data');

                this.tabTemplate = $('amazonTemplateProductTypeEditTabs_template_item').outerHTML;
                this.contentTemplate = $('amazonTemplateProductTypeEditTabs_template_content').outerHTML;
            },

            insertTab: function (nick, title)
            {
                const tabId = 'amazonTemplateProductTypeEditTabs_' + nick;
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
                jQuery('#amazonTemplateProductTypeEditTabs').tabs('refresh')
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
                const tab = $('amazonTemplateProductTypeEditTabs_' + nick);
                if (tab !== null) {
                    tab.remove();
                }

                const tabContent = $('amazonTemplateProductTypeEditTabs_' + nick + '_content');
                if (tabContent !== null) {
                    tabContent.remove();
                }
            },

            addTabContent: function (tabNick, element)
            {
                $$('#amazonTemplateProductTypeEditTabs_' + tabNick + '_content > div')[0].appendChild(element);
            }
        });
    }
);
