<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter;

class Price extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Range
{
    //########################################

    public function getHtml()
    {
        $checked = ($this->getValue('is_repricing') == 1) ? 'checked="checked"' : '';

        $html = <<<HTML
<div class="range">
    <div class="range-line" style="padding-top: 5px">
        <label for="{$this->_getHtmlName()}" 
               style="cursor: pointer;vertical-align: text-bottom;" 
               class="admin__field-label">
            {$this->__('Repricing')}
        </label>&nbsp;
        <input id="{$this->_getHtmlName()}" class="admin__control-checkbox" 
            style="margin-left:6px;float:none;width:auto !important;" 
            type="checkbox" value="1" name="{$this->_getHtmlName()}[is_repricing]" {$checked}>
        <label style="vertical-align: text-bottom;" class="admin__field-label"></label>
    </div>
</div>
HTML;

        return parent::getHtml() . $html;
    }

    //########################################

    public function getValue($index=null)
    {
        if ($index) {
            return $this->getData('value', $index);
        }
        $value = $this->getData('value');
        if ((isset($value['from']) && strlen($value['from']) > 0) ||
            (isset($value['to']) && strlen($value['to']) > 0) ||
            (isset($value['is_repricing']) && $value['is_repricing'] == 1)) {
            return $value;
        }
        return null;
    }

    //########################################
}
