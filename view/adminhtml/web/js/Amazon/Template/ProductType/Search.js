define(
    [
        'jquery',
        'M2ePro/Plugin/Messages',
        'M2ePro/Common'
    ],
    function(jQuery, messageObj) {
        window.AmazonTemplateProductTypeSearch = Class.create(Common, {
            productTypeList: [],
            currentProductType: null,

            initPopup: function (productTypeList)
            {
                this.productTypeList = productTypeList;

                this.setCurrentProductType(AmazonTemplateProductTypeObj.getProductType());
                this.applyBrowseFilter();

                $('product_type_reset_link').observe('click', this.resetCurrentProductType.bind(this));
                $('product_type_browse_results').observe('change', this.updateProductTypeByBrowseResult.bind(this));
                $('product_type_search_results').observe('change', this.updateProductTypeBySearchResult.bind(this));
                $('product_type_search_query').addEventListener('keyup', this.handleSearchInputKeyUps.bind(this));
            },

            getProductTypeTitle: function (productType)
            {
                for (var i = 0; i < this.productTypeList.length; i++) {
                    if (this.productTypeList[i].nick === productType) {
                        return this.productTypeList[i].title;
                    }
                }

                return '';
            },

            setCurrentProductType: function (productType)
            {
                this.currentProductType = productType;
                const title = this.getProductTypeTitle(productType);

                const selectedProductType = jQuery('#search_popup_selected_product_type_title');
                const productTypeNotSelected = jQuery('#search_popup_product_type_not_selected');
                const productTypeResetLink = jQuery('#product_type_reset_link');

                if (title) {
                    productTypeNotSelected.hide();
                    selectedProductType.show().text(title);
                    productTypeResetLink.show();
                } else {
                    productTypeNotSelected.show();
                    selectedProductType.hide();
                    productTypeResetLink.hide();
                }
            },

            applyBrowseFilter: function ()
            {
                const title = $('product_type_browse_query').value;
                const productTypes = this.searchProductTypeByTitle(title);

                const container = $('product_type_browse_results');
                container.innerHTML = '';

                for (var i = 0; i < productTypes.length; i++) {
                    this.insertOption(container, productTypes[i].nick, productTypes[i].title);
                }
            },

            applySearchFilter: function ()
            {
                const keywords = $('product_type_search_query').value;
                this.searchProductTypeByKeywords(keywords);
            },

            handleSearchInputKeyUps: function (event) {
                if (event.keyCode === 13) {
                    this.applySearchFilter();
                }
            },

            resetBrowseFilter: function ()
            {
                $('product_type_browse_query').value = '';
                this.applyBrowseFilter();
            },

            resetSearchFilter: function ()
            {
                $('product_type_search_query').value = '';
                this.applySearchFilter();
            },

            searchProductTypeByTitle: function (title)
            {
                if (!title) {
                    return this.productTypeList.clone();
                }

                const titleLowerCase = title.toLowerCase();
                return this.productTypeList.filter(
                    function (value) {
                        return value.title
                            .toLowerCase()
                            .indexOf(titleLowerCase) !== -1;
                    }
                );
            },

            searchProductTypeByKeywords: function(keywords) {
                var self = this;
                const container = $('product_type_search_results');

                if (!keywords) {
                    container.innerHTML = '';

                    return;
                }

                new Ajax.Request(M2ePro.url.get('amazon_template_productType/searchByKeywords'), {
                    method: 'post',
                    asynchronous: true,
                    parameters: {
                        marketplace_id: AmazonTemplateProductTypeObj.getMarketplaceId(),
                        keywords: keywords
                    },
                    onSuccess: function(transport) {
                        const response = transport.responseText.evalJSON();
                        if (!response.result) {
                            messageObj.clear();
                            messageObj.addError(response.message);
                            return;
                        }

                        container.innerHTML = '';

                        const productTypes = response.data;
                        for (var i = 0; i < productTypes.length; i++) {
                            self.insertOption(container, productTypes[i].nick, productTypes[i].title);
                        }
                    }
                });
            },

            onClickPopupTab: function (item)
            {
                jQuery('#productTypeChooserTabs > ul > li').removeClass('ui-tabs-active ui-state-active');
                jQuery('#' + item.id).parent().addClass('ui-tabs-active ui-state-active');

                jQuery('#chooser_tabs_container > *').hide()
                $(item.id + '_content').style.display = 'block';
            },

            updateProductTypeByBrowseResult: function ()
            {
                this.setCurrentProductType($('product_type_browse_results').value);
            },

            updateProductTypeBySearchResult: function ()
            {
                this.setCurrentProductType($('product_type_search_results').value);
            },

            resetCurrentProductType: function ()
            {
                this.setCurrentProductType('');
                $('product_type_browse_results').value = '';
                $('product_type_search_results').value = '';
            },

            insertOption: function (container, value, title)
            {
                const option = new Element(
                    'option',
                    {value: value}
                );
                option.innerHTML = title;

                container.appendChild(option);
            }
        });
    }
);
