<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\GeneralId
 */
class GeneralId extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AbstractFilter
{
    //########################################

    protected function _renderOption($option, $value)
    {
        $selected = (($option['value'] == $value && ($value !== null)) ? ' selected="selected"' : '' );
        return '<option value="'. $this->escapeHtml($option['value']).'"'.$selected.'>'
            .$this->escapeHtml($option['label'])
            .'</option>';
    }

    public function getHtml()
    {
        $value = $this->getValue('select');

        $optionsHtml = '';
        foreach ($this->_getOptions() as $option) {
            $optionsHtml .= $this->_renderOption($option, $value);
        }

        $html = <<<HTML
<div>
    <input type="text" name="{$this->_getHtmlName()}[input]" id="{$this->_getHtmlId()}_input"
           value="{$this->getEscapedValue('input')}" class="input-text admin__control-text no-changes"/>
</div>
<div style="margin-top: 38px;">
    <label style="vertical-align: text-bottom;">{$this->__('ASIN Creator')}</label>
    <select class="admin__control-select"
            style="margin-left:6px; float:none; width:auto !important;"
            name="{$this->_getHtmlName()}[select]" id="{$this->_getHtmlId()}_select">
        {$optionsHtml}
    </select>
</div>

HTML;

        return parent::getHtml() . $html;
    }

    protected function _getOptions()
    {
        return [
            [
                'label' => $this->__('Any'),
                'value' => ''
            ],
            [
                'label' => $this->__('Yes'),
                'value' => 1
            ],
            [
                'label' => $this->__('No'),
                'value' => 0
            ],
        ];
    }

    //########################################
}
