<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element as MagentoElement;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Form\Renderer\Element
 */
class Element extends MagentoElement
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

    protected function getTooltipHtml($content)
    {
        return <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$content}
    </div>
</div>
HTML;
    }

    public function render(AbstractElement $element)
    {
        $isRequired = $element->getData('required');

        if ($isRequired === true) {
            $element->removeClass('required-entry');
            $element->removeClass('_required');
            $element->setClass('M2ePro-required-when-visible ' . $element->getClass());
        }

        $tooltip = $element->getData('tooltip');

        if ($tooltip === null) {
            $element->addClass('m2epro-field-without-tooltip');
            return parent::render($element);
        }

        $element->setAfterElementHtml(
            $element->getAfterElementHtml() . $this->getTooltipHtml($tooltip)
        );

        $element->addClass('m2epro-field-with-tooltip');

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
