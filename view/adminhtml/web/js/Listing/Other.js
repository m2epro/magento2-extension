define([
    'Magento_Ui/js/modal/modal',
    'M2ePro/Common'
], function (modal) {

    window.ListingOther = Class.create(Common, {

        // ---------------------------------------

        showResetPopup: function (url) {
            var self = this;

            self.resetPopup = jQuery('#reset_other_listings_popup_content');

            modal({
                title: M2ePro.translator.translate('Reset Unmanaged Listings'),
                type: 'popup',
                buttons: [{
                    class: 'action-secondary action-dismiss',
                    text: M2ePro.translator.translate('No'),
                    click: function () {
                        self.resetPopup.modal('closeModal');
                    }
                }, {
                    text: M2ePro.translator.translate('Yes'),
                    class: 'action-primary action-accept',
                    click: function () {
                        setLocation(url);
                        self.resetPopup.modal('closeModal');
                    }
                }]
            }, self.resetPopup);

            self.resetPopup.modal('openModal');
        },

        // ---------------------------------------
    });

});
