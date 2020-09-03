<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Module\Cron\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\System\Config\Module\Cron\Mode\Field
 */
class Field extends \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
{
    /** @var \Ess\M2ePro\Helper\Module\Cron */
    protected $cronHelper;

    //########################################

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Cron $cronHelper,
        array $data = []
    ) {
        parent::__construct($context, $moduleHelper, $data);

        $this->cronHelper = $cronHelper;
    }

    //########################################

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->moduleHelper->isDisabled()) {
            return '';
        }

        $isCronDisabled = (int)!$this->cronHelper->isModeEnabled();
        $cronButtonHtml = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)->setData([
            'label' => $isCronDisabled ? 'Enable' : 'Disable',
            'class' => 'action-primary',
            'onclick' => 'toggleCronStatus()',
            'style' => 'margin-left: 15px;'
        ])->toHtml();
        $cronButtonText = 'Automatic Synchronization';

        $toolTip = $this->getTooltipHtml(
            'Inventory and Order synchronization stops. The Module interface remains available.'
        );

        $html = <<<HTML
<td class="value" colspan="3" style="padding: 2.2rem 1.5rem 0 0;">
    <div style="text-align: left">
        {$cronButtonText} {$cronButtonHtml}
        <input id="m2epro_cron_mode_field" type="hidden" 
            name="groups[module_mode][fields][cron_mode_field][value]" value="{$isCronDisabled}">
        <span style="padding-left: 10px;">{$toolTip}</span>
    </div>
</td>

<script>
    toggleCronStatus = function () {
        jQuery('#save').trigger('click');
    }
</script>
HTML;


        return $this->_decorateRowHtml($element, $html);
    }

    //########################################
}
