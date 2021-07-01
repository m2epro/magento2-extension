<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter;

use Ess\M2ePro\Model\Ebay\Template\Manager;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter\Category
 */
class PolicySettings extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AbstractFilter
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

        $label = $this->__('Overrides');
        $html = <<<HTML
<div>
    <input type="text" name="{$this->_getHtmlName()}[input]" id="{$this->_getHtmlId()}_input"
           value="{$this->getEscapedValue('input')}" class="input-text admin__control-text no-changes"/>
</div>
<div style="padding: 5px 0; text-align: right; font-weight: normal">
    <label>{$label}</label>
    <select class="admin__control-select"
            style="width: 125px"
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
                'label' => $this->__('Policies'),
                'value' => Manager::MODE_TEMPLATE
            ],
            [
                'label' => $this->__('Custom Settings'),
                'value' => Manager::MODE_CUSTOM
            ],
            [
                'label' => $this->__('No'),
                'value' => Manager::MODE_PARENT
            ],
        ];
    }

    //########################################
}
