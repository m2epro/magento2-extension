<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class MsiNotificationPopup extends AbstractBlock
{
    protected function _toHtml()
    {
        $this->js->addOnReadyJs(
            <<<JS
    require([
        'Magento_Ui/js/modal/modal'
    ],function(modal) {
        var modalDialogMessage = new Element('div');
        modalDialogMessage.innerHTML = "{$this->escapeJs(
                (string) __(
                    "Magento Inventory (MSI) is enabled.
                M2E Pro will update your product quantity based on Product Salable QTY. Read more
                <a target='_blank' href='%1'>here</a>.",
                    'https://help.m2epro.com/support/solutions/articles/9000218949'
                )
            )}"

        var popupObj = jQuery(modalDialogMessage).modal({
                title: jQuery.mage.__('Attention'),
                type: 'popup',
                modalClass: 'width-50',
                buttons: [
                    {
                        text: 'Ok',
                        class: 'action primary',
                    }]
            });

        popupObj.modal('openModal').on('modalclosed', function() {
            new Ajax.Request("{$this->getUrl('*/general/MsiNotificationPopupClose')}",
            {
                method: 'post',
                asynchronous : true,
            });
        });
    });
JS
        );

        return parent::_toHtml();
    }
}
