<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class MsiNotificationPopup extends AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->supportHelper = $supportHelper;
    }

    protected function _toHtml()
    {
        $this->js->addOnReadyJs(
            <<<JS
    require([
        'Magento_Ui/js/modal/modal'
    ],function(modal) {
        var modalDialogMessage = new Element('div');
        modalDialogMessage.innerHTML = "{$this->escapeJs(
                $this->__(
                    "Magento Inventory (MSI) is enabled.
                M2E Pro will update your product quantity based on Product Salable QTY. Read more
                <a target='_blank' href='%url%'>here</a>.",
                    $this->supportHelper->getSupportUrl('knowledgebase/1560897/')
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
