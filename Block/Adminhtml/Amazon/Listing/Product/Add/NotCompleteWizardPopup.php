<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class NotCompleteWizardPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected function _prepareLayout()
    {
        $notCompletedPopupTitle = __('Adding of New Products to the Listing was not competed');
        $notCompletedPopupText = __(
            "
            You didn't finish adding Products to the Listing.<br/><br/>
            To add selected Products to the Listing, you need to specify the required information first.
            Once you're done, click <strong>Continue</strong>.<br/><br/>
            If you don't want to add selected Products to the Listing, click <strong>Back</strong> to return
            to the previous step. Or <strong>Cancel</strong> the adding process to return to the Listing.
        "
        );

        $this->jsTranslator->addTranslations([
            'not_completed_popup_title' => $notCompletedPopupTitle,
            'not_completed_popup_text' => $notCompletedPopupText,
        ]);

        $this->js->add(<<<JS
require([
    'Magento_Ui/js/modal/modal'
], function (modal) {

    function showNotCompletedPopup() {

    if (!$('not_completed_popup')) {
        $('html-body').insert({bottom: '<div id="not_completed_popup">' + M2ePro.translator.translate('not_completed_popup_text') + '</div>'});
    }

    var popup = jQuery('#not_completed_popup');

    modal({
        title: M2ePro.translator.translate('not_completed_popup_title'),
        type: 'popup',
        buttons: [{
            text: M2ePro.translator.translate('Close'),
            class: 'action-secondary action-dismiss',
            click: function () {
                popup.modal('closeModal');
            }
        }]
    }, popup);

    popup.modal('openModal');
}

showNotCompletedPopup();
});
JS
        );

        parent::_prepareLayout();
    }
}
