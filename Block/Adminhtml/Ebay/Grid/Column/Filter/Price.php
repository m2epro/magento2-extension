<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter;

class Price extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Range
{
    public function getHtml(): string
    {
        $filterValue = (string)$this->getValue('on_promotion');

        $options = [
            ['value' => '', 'label' => __('Any')],
            ['value' => 0, 'label' => __('No')],
            ['value' => 1, 'label' => __('Yes')],
        ];

        $optionsHtml = '';
        foreach ($options as $option) {
            $optionsHtml .= sprintf(
                '<option %s value="%s">%s</option>',
                $filterValue == $option['value'] ? 'selected="selected"' : '',
                $option['value'],
                $option['label']
            );
        }

        $textOnPromotion = __('On Promotion');

        $html = <<<HTML
<div class="range">
    <div class="range-line" style="padding-top: 5px; display: flex; align-items: center">
        <label for="{$this->_getHtmlName()}"
               style="cursor: pointer;vertical-align: text-bottom; white-space: nowrap;"
               class="admin__field-label">
            {$textOnPromotion}
        </label>
        <select id="{$this->_getHtmlName()}"
                class="admin__control-select"
                style="margin-left:6px; float:none;"
                name="{$this->_getHtmlName()}[on_promotion]"
            >
            $optionsHtml
        </select>
        <label style="vertical-align: text-bottom;" class="admin__field-label"></label>
    </div>
</div>
HTML;

        return parent::getHtml() . $html;
    }

    public function getValue($index = null)
    {
        if ($index) {
            return $this->getData('value', $index);
        }
        $value = $this->getData('value');
        if (
            (isset($value['from']) && $value['from'] !== '')
            || (isset($value['to']) && $value['to'] !== '')
            || (isset($value['on_promotion']) && $value['on_promotion'] !== '')
        ) {
            return $value;
        }

        return null;
    }
}
