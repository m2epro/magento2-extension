define(
    [
        'jquery',
        'M2ePro/Common'
    ],
    function(jQuery) {
        window.AmazonTemplateProductTypeSearch = Class.create(Common, {
            productTypeList: [],
            currentProductType: null,

            initPopup: function (productTypeList)
            {
                this.productTypeList = productTypeList;

                this.setCurrentProductType(AmazonTemplateProductTypeObj.getProductType());
                this.applySearchFilter();

                $('product_type_reset_link').observe('click', this.resetCurrentProductType.bind(this));
                $('product_type_search_results').observe('change', this.updateProductTypeBySearchResult.bind(this));
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

            applySearchFilter: function ()
            {
                const title = $('product_type_search_query').value;
                const productTypes = this.searchProductTypeByTitle(title);

                const container = $('product_type_search_results');
                container.innerHTML = '';

                for (var i = 0; i < productTypes.length; i++) {
                    this.insertOption(container, productTypes[i].nick, productTypes[i].title);
                }
            },

            resetFilter: function ()
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

            updateProductTypeBySearchResult: function ()
            {
                this.setCurrentProductType($('product_type_search_results').value);
            },

            resetCurrentProductType: function ()
            {
                this.setCurrentProductType('');
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
