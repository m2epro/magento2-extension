define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/Moving'
], (MessagesObj) => {
    'use strict';

    window.EbayListingMoving = Class.create(ListingMoving, {
        submit: function(listingId, onSuccess) {
            const self = this;

            $$('.loading-mask').invoke('setStyle', {visibility: 'visible'});

            new Ajax.Request(M2ePro.url.get('moveToListing'), {
                method: 'post',
                parameters: {
                    componentMode: M2ePro.customData.componentMode,
                    listingId: listingId,
                },
                onSuccess: function(transport) {

                    self.popUp.modal('closeModal');
                    self.scrollPageToTop();

                    var response = transport.responseText.evalJSON();

                    $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
                    if (response.result) {
                        var wizardId = response.wizardId;
                        onSuccess.bind(self.gridHandler)(wizardId);
                        if (response.message) {
                            if (response.isFailed) {
                                MessagesObj.addError(response.message);
                            } else {
                                MessagesObj.addSuccess(response.message);
                            }
                        }
                        return;
                    }

                    self.gridHandler.unselectAllAndReload();
                    if (response.message) {
                        MessagesObj.addError(response.message);
                    }
                },
            });
        },
    });
});
