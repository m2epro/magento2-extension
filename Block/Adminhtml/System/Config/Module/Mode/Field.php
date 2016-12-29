<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Module\Mode;

class Field extends \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
{
    /**
     * @inheritdoc
     */

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $buttonHtml = $this->getLayout()->createBlock('\Magento\Backend\Block\Widget\Button')->setData([
            'label' => 'Proceed',
            'class' => 'action-primary',
            'onclick' => 'toggleM2EProModuleStatus()',
            'style' => 'margin-left: 15px;'
        ])->toHtml();

        if ($this->moduleHelper->isDisabled()) {
            $title = 'Confirmation';
            $confirmContent = 'Are you sure ?';
            $buttonText = 'Enable Module and Automatic Synchronization';
            $confirmBtn = 'Ok';
            $value = 1;
        } else {
            $title = 'Disable/Enable Module';
            $confirmContent = <<<HTML
                <p>In case you confirm the Module disabling, the M2E Pro dynamic tasks run by
                Cron will be stopped and the M2E Pro Interface will be blocked.</p>

                <p><b>Note</b>: You can re-enable it anytime you would like by clicking on the <strong>Proceed</strong>
                button for <strong>Enable Module and Automatic Synchronization</strong> option.</p>
HTML;
            $buttonText = 'Stop Module and Automatic Synchronization';
            $confirmBtn = 'Confirm';
            $value = 0;
        }

        $html = <<<HTML
<td class="value" colspan="3" style="padding: 2.2rem 1.5rem 0 0;">
    <div style="text-align: center">
        {$buttonText} {$buttonHtml}
        <input id="m2epro_module_mode_field" type="hidden"
            name="groups[module_mode][fields][module_mode_field][value]" value="{$value}">
    </div>
</td>

<div id="module_mode_confirmation_popup" style="display: none">
{$confirmContent}
</div>

<script>
    require([
        'Magento_Ui/js/modal/confirm'
    ], function(confirm) {
        toggleM2EProModuleStatus = function () {
            confirm({
                title: '{$title}',
                content: jQuery('#module_mode_confirmation_popup').html(),
                buttons: [{
                    text: 'Cancel',
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: '{$confirmBtn}',
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }],
                actions: {
                    confirm: function () {
                        jQuery('#m2epro_module_mode_field').val(+!{$value});
                        jQuery('#save').trigger('click');
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        }
    });
</script>
HTML;
        return $this->_decorateRowHtml($element, $html);
    }
}