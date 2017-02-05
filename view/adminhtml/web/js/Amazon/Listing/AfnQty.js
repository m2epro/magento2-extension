define([
    'Magento_Ui/js/modal/alert'
], function (alert) {
    window.AmazonListingAfnQty = Class.create();
    AmazonListingAfnQty.prototype = {

        // ---------------------------------------

        initialize: function(containerId) {},

        // ---------------------------------------

        showAfnQty: function(el, selectedSku, selectedProductId, accountId) {

            var self = this,
                skus = [];

            var i = 50,
                hasSelected = false;
            el.up('table').select('.m2ePro-online-sku-value').each(function (skuEl) {
                if (i === 0) {
                    throw $break;
                }

                if (skuEl.innerHTML == '') {
                    return;
                }

                if (selectedSku == skuEl.innerHTML) {
                    hasSelected = true;
                }

                skus.push({
                    sku: skuEl.innerHTML,
                    productId: skuEl.getAttribute('productId')
                });
                i--;
            });

            if (!hasSelected) {
                skus.pop();
                skus.push({
                    sku: selectedSku,
                    productId: selectedProductId
                });
            }

            var skusStr = '';
            skus.each(function(item) {
                skusStr += item.sku + ','
            });

            skusStr = rtrim(skusStr, ',');

            new Ajax.Request(M2ePro.url.get('amazon_listing/getAFNQtyBySku'), {
                method: 'post',
                parameters: {
                    skus: skusStr,
                    account_id: accountId
                },
                onSuccess: function(transport) {
                    if (!transport.responseText.isJSON()) {
                        alert({
                            content: transport.responseText
                        });
                        return;
                    }

                    var response = transport.responseText.evalJSON();

                    var data = {};
                    skus.each(function (item) {
                        if (response.items[item.sku]) {
                            data[item.productId] = response.items[item.sku].qty;
                        } else {
                            data[item.productId] = 'empty';
                        }
                    });

                    self.renderAfnQty(data);
                }
            });
        },

        // ---------------------------------------

        renderAfnQty: function(data)
        {
            var self = this;

            $H(data).each(function(item) {
                var container = $('m2ePro_afn_qty_value_' + item.key);

                container.down('a').hide();
                container.down('.m2ePro-online-sku-value').remove();

                if (item.value != 'empty') {
                    container.down('.m2epro-afn-qty-data').show();

                    container.down('.total span').update(item.value.total);
                    container.down('.in-stock span').update(item.value.in_stock);
                } else {
                    container.down('.m2epro-empty-afn-qty-data').show();
                }
            });
        }

    };
});