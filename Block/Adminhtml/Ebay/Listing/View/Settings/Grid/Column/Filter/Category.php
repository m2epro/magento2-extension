<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter;

class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AbstractFilter
{
    //########################################

    protected function _renderOption($option, $value)
    {
        $selected = (($option['value'] == $value && (!is_null($value))) ? ' selected="selected"' : '' );
        return '<option value="'. $this->escapeHtml($option['value']).'"'.$selected.'>'
                    .$this->escapeHtml($option['label'])
              .'</option>';
    }

    public function getHtml()
    {
        $value = $this->getValue('select');
        $checkbox = $this->getValue('checkbox') ? 'checked' : '';

        $optionsHtml = '';
        foreach ($this->_getOptions() as $option) {
            $optionsHtml .= $this->_renderOption($option, $value);
        }

        //eBay Catalog Primary Category Assigned
        $label = $this->getHelper('Component\Ebay\Category')
            ->getCategoryTitles()[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN];
        $label = $this->__('%1% Assigned', $label);

        $html = <<<HTML
<div>
    <input type="text" name="{$this->_getHtmlName()}[input]" id="{$this->_getHtmlId()}_input"
           value="{$this->getEscapedValue('input')}" class="input-text no-changes"/>
</div>
<div style="padding: 5px 0; text-align: right; font-weight: normal">
    <label>{$label}</label>
    <select class="admin__control-select"
            style="width: 75px"
            name="{$this->_getHtmlName()}[select]" id="{$this->_getHtmlId()}_select">
        {$optionsHtml}
    </select>
</div>
<div style="padding: 5px 0; text-align: right; font-weight: normal">
        <label for="{$this->_getHtmlId()}_checkbox">
            {$this->__('Only Listing Products with Listing Settings Overwrites')}
        </label>
        <div style="display: inline-block; line-height: 2;">
            <input name="{$this->_getHtmlName()}[checkbox]" 
                   id="{$this->_getHtmlId()}_checkbox"
                   value="1" class="admin__control-checkbox" 
                   type="checkbox" {$checkbox}>
            <label style="margin: 0 0 -4px 2px;" class="addafter" for="{$this->_getHtmlId()}_checkbox">
                <label for="{$this->_getHtmlId()}_checkbox"></label>
            </label>
        </div> 
</div>

HTML;

        return parent::getHtml() . $html;
    }

    protected function _getOptions()
    {
        return array(
            array(
                'label' => $this->__('Any'),
                'value' => ''
            ),
            array(
                'label' => $this->__('Yes'),
                'value' => 1
            ),
            array(
                'label' => $this->__('No'),
                'value' => 0
            ),
        );
    }

    //########################################
}