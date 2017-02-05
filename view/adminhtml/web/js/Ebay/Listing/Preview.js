define(function () {

    window.EbayListingPreviewItems = Class.create(Common, {

        initialize: function () {
            var self = this;

            jQuery('.price_container button').click(function () {
                self.alert(M2ePro.translator.translate('This is Item Preview Mode'));
            });

            self.onTabClick(jQuery('.tabs-horiz li:first'));
        },

        getVariation: function(chosenOptions) {
            var returnVariation = {};

            M2ePro.formData.variations.variations.forEach(function (variation) {

                var countOfOptions = 0;

                for (var variationSpecific in variation.specifics) {
                    chosenOptions.forEach(function (option) {
                        if (variationSpecific === option.attribute) {
                            if (variation.specifics[variationSpecific] === option.value) {
                                countOfOptions++;
                            } else {
                                return false;
                            }
                        }
                    });
                }

                if (countOfOptions === chosenOptions.length) {
                    returnVariation = variation;
                    return false;
                }

            });

            return returnVariation;
        },

        onChangeVariationSelect: function (currentSelect) {
            $$('option').each(function (option) {
                option.show();
            });

            choseSelects = $$("select[value!=]").length;

            if (choseSelects < $$('select').length - 1) {
                return;
            }

            if (choseSelects === $$('select').length - 1) {
                var otherSelectsValues = [];

                $$('select[value!=]').each(function (select) {
                    otherSelectsValues.push({
                        attribute: select.name,
                        value: select.value
                    });
                });

                $$("select[value=]")[0].select('option[value!=]').each(function (option) {
                    otherSelectsValues.push({
                        attribute: option.up().name,
                        value: option.value
                    });

                    variation = EbayListingPreviewItemsObj.getVariation(otherSelectsValues);

                    if (variation.data === undefined) {
                        option.hide();
                    }

                    otherSelectsValues.pop();
                });
            } else {
                $$('select').each(function (select) {
                    var otherSelectsValues = [];

                    $$('select').each(function (otherSelects) {
                        if (otherSelects !== select) {
                            otherSelectsValues.push({
                                attribute: otherSelects.name,
                                value: otherSelects.value
                            });
                        }
                    });

                    select.select('option[value!=]').each(function (option) {

                        otherSelectsValues.push({
                            attribute: option.up().name,
                            value: option.value
                        });

                        variation = EbayListingPreviewItemsObj.getVariation(otherSelectsValues);

                        if (variation.data === undefined) {
                            if (select.value === option.value) {
                                select.value = "";
                            }
                            option.hide();

                        }

                        otherSelectsValues.pop();
                    });
                });

                var otherSelectsValues = [];

                $$('select').each(function (otherSelects) {
                    otherSelectsValues.push({
                        attribute: otherSelects.name,
                        value: otherSelects.value
                    });
                });

                variation = EbayListingPreviewItemsObj.getVariation(otherSelectsValues);

                if($('product_discount_stp')) {
                    $('product_discount_stp').hide();
                    $('product_price_stp').hide();
                }
                if($('product_discount_map')) {
                    $('product_discount_map').hide();
                    $('product_price_map').hide();
                }

                if (variation.data.price_stp !== null) {
                    $('product_discount_stp').show();
                    $('product_price_stp').show().update(variation.data.price_stp);
                } else {
                    if (variation.data.price_map !== null) {
                        $('product_discount_map').show();
                        $('product_price_map').show().update(variation.data.price_map);
                    }
                }

                $('product_price').update(variation.data.price);
                $('product_qty').update(variation.data.qty);

            }

            if (M2ePro.formData.images.variations &&
                M2ePro.formData.images.variations.specific === currentSelect.name && currentSelect.value) {
                $('product_image').src = M2ePro.formData.images.variations.images[currentSelect.value][0];
            }
        },

        onClickGalleryImage: function (image) {
            $('product_image').src = image.src;
        },

        onTabClick: function (tab){
            jQuery('.' + jQuery('li.active').attr('data-tab') + '_container').hide();
            jQuery('li.active').removeClass('active');

            jQuery(tab).addClass('active');
            jQuery('.' + jQuery(tab).attr('data-tab') + '_container').show();
        },

        initVariations: function () {
            $$('select').each(function(currentSelect){
                if(currentSelect.select('option[value!=]').length === 1){
                    currentSelect.select('option[value!=]')[0].selected = true;
                    EbayListingPreviewItemsObj.onChangeVariationSelect(currentSelect);
                }
            });
        }
    });
});