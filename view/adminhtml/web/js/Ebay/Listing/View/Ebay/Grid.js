define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Ebay/Listing/View/Grid'
], function (modal) {

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
            var idField = M2ePro.php.constant('\\Ess\\M2ePro\\Block\\Adminhtml\\Log\\Listing\\Product\\AbstractGrid::LISTING_PRODUCT_ID_FIELD');

            var params = {};
            params[idField] = rowId;

            return M2ePro.url.get('ebay_log_listing_product/index', params);
        },

        // ---------------------------------------

        openFeePopUp: function(content, title)
        {
            var feePopup = $('fee_popup');

            if (feePopup) {
                feePopup.remove();
            }

            $('html-body').insert({bottom: '<div id="fee_popup"></div>'});

            $('fee_popup').update(content);

            var popup = jQuery('#fee_popup');

            modal({
                title: title,
                type: 'popup',
                buttons: [{
                    text: M2ePro.translator.translate('Close'),
                    class: 'action-secondary',
                    click: function () {
                        popup.modal('closeModal');
                    }
                }]
            }, popup);

            popup.modal('openModal');
        },

        getEstimatedFees: function(listingProductId)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing/getEstimatedFees'), {
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

                    self.openFeePopUp(response.html, response.title);
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