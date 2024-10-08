define(
    [
        'jquery',
        'M2ePro/Plugin/Messages',
        'M2ePro/Common'
    ],
    function(jQuery) {
        window.WalmartProductTypeSearch = Class.create(Common, {
            productTypeList: [],
            currentProductType: null,

            initPopup: function (productTypeList)
            {
                this.productTypeList = productTypeList;

                this.setCurrentProductType(WalmartProductTypeObj.getProductType());
                this.applySearchFilter();

                $('product_type_reset_link').observe('click', this.resetCurrentProductType.bind(this));
                $('product_type_search_results').observe('change', this.updateProductTypeByResult.bind(this));

                jQuery('.product-type-confirm').prop('disabled', true);
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
                var productTypeId;

                for (var i = 0; i < productTypes.length; i++) {
                    productTypeId = productTypes[i]['exist_product_type_id'] !== undefined ?
                            productTypes[i]['exist_product_type_id'] : false;
                    this.insertOption(container, productTypes[i].nick, productTypes[i].title, productTypeId);
                }
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

            onClickPopupTab: function (item)
            {
                jQuery('#productTypeChooserTabs > ul > li').removeClass('ui-tabs-active ui-state-active');
                jQuery('#' + item.id).parent().addClass('ui-tabs-active ui-state-active');

                jQuery('#chooser_tabs_container > *').hide()
                $(item.id + '_content').style.display = 'block';

                this.resetTabsChanges();
            },

            resetTabsChanges: function ()
            {
                jQuery('.product-type-confirm').prop('disabled', true);
                this.resetCurrentProductType();

                const rootContainer = $('product_type_browse_results');
                WalmartProductTypeFinderObj.clearChildCategories(rootContainer);
                WalmartProductTypeFinderObj.clearFollowingContainers(rootContainer);

                jQuery('#product_type_browse_error_content').empty();
                jQuery('#product_type_search_error_content').empty();
            },

            updateProductTypeByResult: function (event)
            {
                const selectValue = event.target.value;
                const selectElement = event.target;
                const options = Array.from(selectElement.options)
                const selectOption = options.find(option => option.value === selectValue);
                const errorContentWrapper = jQuery('#product_type_search_error_content');
                const confirmButton = jQuery('.product-type-confirm');

                if (selectOption.dataset.existProductTypeId) {
                    confirmButton.prop('disabled', true);

                    if (errorContentWrapper.length > 0) {
                        const url = M2ePro.url.get(
                                'walmart_productType/edit',
                                {id: selectOption.dataset.existProductTypeId}
                        );
                        const errorContent = str_replace(
                                'exist_product_type_url',
                                url,
                                M2ePro.translator.translate('product_type_configured')
                        );
                        jQuery(errorContentWrapper).html(errorContent);
                    }
                } else {
                    confirmButton.prop('disabled', false);

                    if (errorContentWrapper.length > 0 && !errorContentWrapper.is(':empty')) {
                        errorContentWrapper.empty();
                    }
                    this.setCurrentProductType(selectValue);
                }
            },

            resetCurrentProductType: function ()
            {
                this.setCurrentProductType('');
                $('product_type_search_results').value = '';
                jQuery('.product-type-confirm').prop('disabled', true);
            },

            insertOption: function (container, value, title, typeId)
            {
                const productTypeOptions = {value: value};

                if (typeId) {
                    productTypeOptions['data-exist-product-type-id'] = typeId;
                }

                const option = new Element('option', productTypeOptions);
                option.innerHTML = title;
                container.appendChild(option);
            }
        });
    }
);
