<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset as MagentoFieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Fieldset extends MagentoFieldset
{
    protected function getTooltipHtml($content, $directionClass)
    {
        return <<<HTML
<div class="m2epro-field-tooltip m2epro-field-tooltip-{$directionClass} m2epro-fieldset-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$content}
    </div>
</div>
HTML;
    }

    public function render(AbstractElement $element)
    {
        $element->addClass('m2epro-fieldset');

        $tooltip = $element->getData('tooltip');

        if (is_null($tooltip)) {
            return parent::render($element);
        }

        $element->addField(
            'help_block_' . $element->getId(),
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::HELP_BLOCK,
            [
                'content' => $tooltip,
                'tooltiped' => true
            ],
            '^'
        );

        $directionClass = $element->getData('direction_class');

        $element->setLegend(
            $element->getLegend() . $this->getTooltipHtml($tooltip, empty($directionClass) ? 'right' : $directionClass )
        );

        return parent::render($element);
    }
}