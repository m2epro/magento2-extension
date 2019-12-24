<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter\Category
 */
class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AbstractFilter
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
        $checkbox = $this->getValue('checkbox') ? 'checked' : '';

        $hideListingSettingsOverwritesFilter = 'display: none;';
        if ($this->getColumn()->getData('is_exists_listing_settings_overwrites')) {
            $hideListingSettingsOverwritesFilter = '';
        }

        $optionsHtml = '';
        foreach ($this->_getOptions() as $option) {
            $optionsHtml .= $this->_renderOption($option, $value);
        }

        //eBay Catalog Primary Category Assigned
        $label = $this->getHelper('Component_Ebay_Category')
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);
        $label = $this->__('%1% Assigned', $label);

        $html = <<<HTML
<div>
    <input type="text" name="{$this->_getHtmlName()}[input]" id="{$this->_getHtmlId()}_input"
           value="{$this->getEscapedValue('input')}" class="input-text admin__control-text no-changes"/>
</div>
<div style="padding: 5px 0; text-align: right; font-weight: normal">
    <label>{$label}</label>
    <select class="admin__control-select"
            style="width: 75px"
            name="{$this->_getHtmlName()}[select]" id="{$this->_getHtmlId()}_select">
        {$optionsHtml}
    </select>
</div>
<div style="padding: 5px 0; text-align: right; font-weight: normal; {$hideListingSettingsOverwritesFilter}">
        <label for="{$this->_getHtmlId()}_checkbox">
            {$this->__('With Listing Policies Overrides')}
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
