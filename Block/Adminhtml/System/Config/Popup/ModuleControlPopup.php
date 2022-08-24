<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Popup;

class ModuleControlPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module */
    protected $moduleHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        array $data = []
    ) {
        $this->moduleHelper = $moduleHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        $isModuleDisabled = (int)$this->moduleHelper->isDisabled();
        if ($isModuleDisabled) {
            $confirmContent = 'Are you sure ?';
        } else {
            $confirmContent = <<<HTML
<p>In case you confirm the Module disabling, the M2E Pro dynamic tasks run by
Cron will be stopped and the M2E Pro Interface will be blocked.</p>

<p><b>Note</b>: You can re-enable it anytime you would like by clicking on the <strong>Proceed</strong>
button for <strong>Enable Module and Automatic Synchronization</strong> option.</p>
HTML;
        }

        $html = <<<HTML
<div id="module_mode_confirmation_popup" style="display: none">
    $confirmContent
</div>
HTML;

        return parent::_toHtml() . $html;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $isModuleDisabled = (int)$this->moduleHelper->isDisabled();
        if ($isModuleDisabled) {
            $title = 'Confirmation';
            $confirmBtn = 'Ok';
        } else {
            $title = 'Disable Module';
            $confirmBtn = 'Confirm';
        }

        $this->js->addRequireJs(
            ['confirm' => 'Magento_Ui/js/modal/confirm'],
            <<<JS
toggleM2EProModuleStatus = function () {
    var contentHtml = jQuery('#module_mode_confirmation_popup').html();
    confirm({
        title: '$title',
        content: contentHtml,
        buttons: [{
            text: 'Cancel',
            class: 'action-secondary action-dismiss',
            click: function (event) {
                this.closeModal(event);
            }
        }, {
            text: '$confirmBtn',
            class: 'action-primary action-accept',
            click: function (event) {
                this.closeModal(event, true);
            }
        }],
        actions: {
            confirm: function () {
                toggleStatus('module_mode_field');
            },
            cancel: function () {
                return false;
            }
        }
    });
}
JS
        );
    }
}
