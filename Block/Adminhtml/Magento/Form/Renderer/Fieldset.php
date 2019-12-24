<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset as MagentoFieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Form\Renderer\Fieldset
 */
class Fieldset extends MagentoFieldset
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->helperFactory = $context->getHelperFactory();
        parent::__construct($context, $data);
    }

    //########################################

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

        if ($tooltip === null) {
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
            $element->getLegend() . $this->getTooltipHtml($tooltip, empty($directionClass) ? 'right' : $directionClass)
        );

        return parent::render($element);
    }

    //########################################

    /**
     * @param array|string $data
     * @param null $allowedTags
     * @return array|string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     *
     * Starting from version 2.2.3 Magento forcibly escapes content of tooltips. But we are using HTML there
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        return $this->helperFactory->getObject('Data')->escapeHtml(
            $data,
            ['div', 'a', 'strong', 'br', 'i', 'b', 'ul', 'li', 'p'],
            ENT_NOQUOTES
        );
    }

    //########################################
}
