<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\Renderer;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\Renderer\Editable
 */
class Editable extends AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    protected $translateInline;

    //########################################

    public function __construct(
        \Magento\Framework\Translate\Inline $translateInline,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->translateInline = $translateInline;
        parent::__construct($context, $data);
    }

    //########################################

    /**
     * Render element
     *
     * @param AbstractElement $element
     * @see \Magento\Framework\Data\Form\Element\Renderer\RendererInterface::render()
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('element-value-changer');
        $valueName = $element->getValueName();

        if ($element instanceof \Magento\Framework\Data\Form\Element\Select && $valueName == '...') {
            $optionValues = $element->getValues();

            foreach ($optionValues as $option) {
                if ($option['value'] === '') {
                    $valueName = $option['label'];
                }
            }
        }

        if (trim($valueName) === '') {
            $valueName = '...';
        }

        if ($element->getShowAsText()) {
            $html = ' <input type="hidden" class="hidden" id="' . $element->getHtmlId()
                . '" name="' . $element->getName() . '" value="' . $element->getValue() . '"/> '
                . htmlspecialchars($valueName) . '&nbsp;';
        } else {
            $html = ' <span class="rule-param"'
                . ($element->getParamId() ? ' id="' . $element->getParamId() . '"' : '') . '>'
                . '<a href="javascript:void(0)" class="label">';

            $html .= $this->translateInline->isAllowed() ? $this->escapeHtml($valueName) :
                $this->escapeHtml($this->filterManager->truncate($valueName, ['length' => 33]));

            $html .= '</a><span class="element"> ' . $element->getElementHtml();

            if ($element->getExplicitApply()) {
                $html .= ' <a href="javascript:void(0)" class="rule-param-apply"><img src="'
                . $this->_assetRepo->getUrl('Ess_M2ePro::images/rule_component_apply.gif')
                . '" class="v-middle" alt="'
                . $this->__('Apply') . '" title="' . $this->__('Apply') . '" /></a> ';
            }

            $html .= '</span></span>&nbsp;';
        }

        return $html;
    }

    //########################################
}
