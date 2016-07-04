define([
    'M2ePro/Ebay/Listing/View/Grid'
], function () {
    
    window.EbayListingViewEbayGrid = Class.create(EbayListingViewGrid, {

        // ---------------------------------------

        afterInitPage: function($super)
        {
            $super();

            $(this.gridId+'_massaction-select').observe('change', function() {
                if (!$('get-estimated-fee')) {
                    return;
                }

                if (this.value == 'list') {
                    $('get-estimated-fee').show();
                } else {
                    $('get-estimated-fee').hide();
                }
            });
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 10;
        },

        // ---------------------------------------

        getLogViewUrl: function(rowId)
        {
            return M2ePro.url.get('ebay_listing_product_log/index', {
                listing_product_id: rowId
            });
        },

        // ---------------------------------------

        openFeePopUp: function(content)
        {
            Dialog.info(content, {
                draggable: true,
                resizable: true,
                closable: true,
                className: "magento",
                windowClassName: "popup-window",
                title: M2ePro.translator.translate('Estimated Fee Details'),
                width: 400,
                zIndex: 100,
                recenterAuto: true
            });

            Windows.getFocusedWindow().content.style.height = '';
            Windows.getFocusedWindow().content.style.maxHeight = '550px';
        },

        getEstimatedFees: function(listingProductId)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('adminhtml_ebay_listing/getEstimatedFees'), {
                method: 'get',
                asynchronous: true,
                parameters: {
                    listing_product_id: listingProductId
                },
                onSuccess: function(transport) {

                    var response = transport.responseText.evalJSON();

                    if (response.error) {
                        alert('Unable to receive estimated fee.');
                        return;
                    }

                    self.openFeePopUp(response.html);
                }
            });
        },

        // ---------------------------------------

        showMotorsNotificationPopUp: function(message)
        {
            var content = '<div style="padding: 10px 0;">'+message+'</div>' +
                          '<div style="float: right;">' +
                            '<button onclick="Windows.getFocusedWindow().close()">' +
                              '<span>OK</span>' +
                            '</button>' +
                          '</div> ';
            var config = {
                draggable: true,
                resizable: true,
                closable: true,
                className: "magento",
                windowClassName: "popup-window",
                title: M2ePro.translator.translate('Compatibility Attribute'),
                top: 250,
                height: 85,
                width: 420,
                zIndex: 100,
                recenterAuto: true,
                hideEffect: Element.hide,
                showEffect: Element.show
            };

            Dialog.info(content, config);
        }

        // ---------------------------------------
    });
});