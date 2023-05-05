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
     * @param array $keys
     * @param string $valueSeparator
     * @param string $fieldSeparator
     * @param string $quote
     *
     * @return string
     */
    public function serialize($keys = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"'): string
    {
        $data = [];
        foreach ($keys as $key) {
            $value = $this->getData($key);
            if ($value !== null) {
                $data[] = $key . $valueSeparator . $quote . $value . $quote;
            }
        }

        return implode($fieldSeparator, $data);
    }
}
