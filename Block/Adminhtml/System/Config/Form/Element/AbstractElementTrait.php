<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element;

use Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

trait AbstractElementTrait
{
    use BlockTrait;

    /**
     * @param string $idSuffix
     * @param string $scopeLabel
     *
     * @return string
     */
    public function getLabelHtml($idSuffix = '', $scopeLabel = ''): string
    {
        $scopeLabel = $scopeLabel ? ' data-config-scope="' . $scopeLabel . '"' : '';

        if ($this->getLabel() !== null) {
            $html = '<label class="label admin__field-label" for="' .
                $this->getHtmlId() . $idSuffix . '"' . $this->_getUiId(
                    'label'
                ) . ' style="width: 35%"><span' . $scopeLabel . '>' . $this->_escape(
                    $this->getLabel()
                ) . '</span></label>' . "\n";
        } else {
            $html = '';
        }

        return $html;
    }

    /**
     * Serialize attributes
     *
     * @param array $attributes
     * @param string $valueSeparator
     * @param string $fieldSeparator
     * @param string $quote
     *
     * @return string
     */
    public function serialize($attributes = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"'): string
    {
        $data = [];
        foreach ($attributes as $attribute) {
            $value = $this->getData($attribute);
            if ($value !== null) {
                $data[] = $attribute . $valueSeparator . $quote . $value . $quote;
            }
        }

        return implode($fieldSeparator, $data);
    }
}
