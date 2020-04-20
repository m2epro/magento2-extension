define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function (jQuery, modal) {

    window.EbayListingProductCategorySettingsSpecificWrapper = Class.create(Common, {

        // ---------------------------------------

        initialize: function (currentCategory, wrapperObj) {
            this.wrapperObj = wrapperObj;

            this.setCurrentCategory(currentCategory);
        },

        // ---------------------------------------

        refreshButtons: function () {
            $$('button.specifics_buttons').invoke('hide');

            if (this.getNextCategory()) {

                $$('button.next_category_button').invoke('show');

            } else {

                $$('button.continue').invoke('show');

            }
        },

        // ---------------------------------------

        setCurrentCategory: function (category) {
            $('categories_list').select('li').each(function (li) {
                li.removeClassName('selected');
            });

            this.currentCategory = category;
            $(category).addClassName('selected');

            this.refreshButtons();
        },

        getCurrentCategory: function () {
            return this.currentCategory;
        },

        getNextCategory: function () {
            var nextLi = $(this.currentCategory).next('li');

            if (!nextLi) {
                return false;
            }

            return nextLi.id;
        },

        getPrevCategory: function () {
            var prevLi = $(this.currentCategory).previous('li');

            if (!prevLi) {
                return false;
            }

            return prevLi.id;
        },

        // ---------------------------------------

        renderPrevCategory: function () {
            if (!this.getPrevCategory()) {
                var url = M2ePro.url.get('ebay_listing_product_category_settings');
                setLocation(url);
                return;
            }

            this.getSpecificsData(this.getPrevCategory(), function (transport) {

                var response = transport.responseText.evalJSON();

                try {
                    $('specifics_main_container').innerHTML = response.html;
                    $('specifics_main_container').innerHTML.evalScripts();
                } catch (e) {
                }

            });
        },

        renderNextCategory: function () {
            if (!EbayListingProductCategorySettingsSpecificObj.validate()) {
                return;
            }

            if (!!$('skip_optional_specifics').checked) {
                this.lock();
            }

            if (!this.getNextCategory()) {
                this.unlock();
                return this.showPopup();
            }

            this.saveCategory(function () {
                this.getSpecificsData(this.getNextCategory(), function (transport) {
                    var response = transport.responseText.evalJSON();

                    try {
                        $('specifics_main_container').innerHTML = response.html;
                        $('specifics_main_container').innerHTML.evalScripts();
                    } catch (e) {
                    }

                    if (!response.hasRequiredSpecifics && !!$('skip_optional_specifics').checked) {
                        this.renderNextCategory();
                    } else {
                        this.unlock();
                    }

                })
            }.bind(this));
        },

        // ---------------------------------------

        getSpecificsData: function (category, callback) {
            var url = M2ePro.url.get('ebay_listing_product_category_settings/stepThreeGetCategorySpecifics');
            new Ajax.Request(url, {
                method: 'get',
                parameters: {
                    category: category
                },
                onSuccess: function (transport) {
                    this.setCurrentCategory(category);
                    callback && callback.call(this, transport);
                }.bind(this)
            });
        },

        // ---------------------------------------

        saveCategory: function (callback) {
            if (!EbayListingProductCategorySettingsSpecificObj.validate()) {
                return;
            }

            var url = M2ePro.url.get('ebay_listing_product_category_settings/stepThreeSaveCategorySpecificsToSession');
            new Ajax.Request(url, {
                method: 'post',
                parameters: {
                    category: this.getCurrentCategory(),
                    data: Object.toJSON(EbayListingProductCategorySettingsSpecificObj.getInternalData())
                },
                onSuccess: function (transport) {
                    callback.call(this);
                }.bind(this)
            });
        },

        // ---------------------------------------

        save: function () {
            if (this.getNextCategory()) {
                return;
            }

            this.saveCategory(function () {

                var url = M2ePro.url.get('ebay_listing_product_category_settings/save');

                new Ajax.Request(url, {
                    method: 'post',
                    onSuccess: function (transport) {
                        setLocation(M2ePro.url.get('ebay_listing/review'))
                    }
                });
            });
        },

        // ---------------------------------------

        showPopup: function () {
            var self = this;

            this.popup = jQuery('#popup_content').modal({
                title: M2ePro.translator.translate('Set Item Specifics'),
                type: 'popup',
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept forward',
                    click: function () {
                        self.save();
                    }
                }]
            });

            this.popup.modal('openModal');
        },

        // ---------------------------------------

        lock: function () {
            $(this.wrapperObj.wrapperId).visible() || this.wrapperObj.lock();

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
            $(this.wrapperObj.wrapperId).update(
                '<div style="height: 46%"></div>' +
                '<div>' + M2ePro.translator.translate('Loading. Please wait') + ' ...</div>'
            );
        },

        unlock: function () {
            this.wrapperObj.unlock();
            $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});
        }

        // ---------------------------------------
    });
});