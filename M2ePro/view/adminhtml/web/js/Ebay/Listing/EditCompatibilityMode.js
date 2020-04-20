define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ePro/Plugin/Confirm',
    'M2ePro/Common'
], function (jQuery, modal, confirm) {

    window.EditCompatibilityMode = Class.create(Common, {

        // ---------------------------------------

        initialize: function (gridId) {
            this.gridId = gridId;
        },

        // ---------------------------------------

        openPopup: function(listingId)
        {
            var self = this;

            new Ajax.Request(M2ePro.url.get('ebay_listing/getChangePartsCompatibilityModePopupHtml'), {
                method: 'GET',
                parameters: {
                    listing_id: listingId
                },
                onSuccess: (function(transport) {

                    if ($('edit_mode_form')) {
                        $('edit_mode_form').remove();
                    }

                    $('html-body').insert({bottom: transport.responseText});

                    var form = jQuery('#edit_mode_form');

                    modal({
                        title: M2ePro.translator.translate('Edit Parts Compatibility Mode'),
                        type: 'popup',
                        modalClass: 'width-50',
                        buttons: [{
                            text: M2ePro.translator.translate('Cancel'),
                            class: 'action-secondary action-dismiss',
                            click: function () {
                                self.closePopup();
                            }
                        },{
                            text: M2ePro.translator.translate('Save'),
                            class: 'action-primary action-accept',
                            click: function () {
                                self.saveListingMode();
                            }
                        }]
                    }, form);

                    this.oldMode = trim($('listing_compatibility_mode_' + listingId).innerHTML);
                    this.listingId = listingId;

                    jQuery('#edit_mode_form').modal('openModal');
                }).bind(this)
            });
        },

        saveListingMode: function()
        {
            var self = this,
                newMode = $('edit_mode_form').select('#parts_compatibility_mode')[0].value;

            if (self.oldMode == newMode) {

                this.closePopup();
                return;
            }

            if (!jQuery('#edit_mode_form').valid()) {
                return false;
            }

            confirm({
                actions: {
                    confirm: function () {
                        new Ajax.Request(M2ePro.url.get('ebay_listing/savePartsCompatibilityMode'), {
                            method: 'post',
                            asynchronous: true,
                            parameters: {
                                listing_id: self.listingId,
                                mode:       newMode
                            },
                            onSuccess: function(transport) {
                                self.closePopup();
                            }.bind(this)
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        closePopup: function()
        {
            jQuery('#edit_mode_form').modal('closeModal');
            window[this.gridId + 'JsObject'].reload();

            return true;
        }

    })
        // ---------------------------------------
});