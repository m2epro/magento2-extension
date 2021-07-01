<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Grid\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Grid\Column\Filter\ProductId
 */
class ProductId extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Range
{
    //########################################

    public function getHtml()
    {
        $anySelected = $noSelected = $yesSelected = '';
        $filterValue = (string)$this->getValue('is_mapped');

        $filterValue === '' && $anySelected = ' selected="selected" ';
        $filterValue === '0' && $noSelected  = ' selected="selected" ';
        $filterValue === '1' && $yesSelected = ' selected="selected" ';

        $isEnabled  = 1;
        $isDisabled = 0;

        $html = <<<HTML
<div class="range" style="width: 145px;">
    <div class="range-line" style="width: auto;">
        <span class="label" style="width: auto;">
            {$this->__('Linked')}:&nbsp;
        </span>
        <select id="{$this->_getHtmlName()}"
                style="margin-left:6px; margin-top:5px; float:none; width:auto !important;"
                name="{$this->_getHtmlName()}[is_mapped]"
            >
            <option {$anySelected} value="">{$this->__('Any')}</option>
            <option {$yesSelected} value="{$isEnabled}">{$this->__('Yes')}</option>
            <option {$noSelected}  value="{$isDisabled}">{$this->__('No')}</option>
        </select>
    </div>
</div>
HTML;

        return parent::getHtml() . $html;
    }

    //########################################

    public function getValue($index = null)
    {
        if ($index) {
            return $this->getData('value', $index);
        }

        $value = $this->getData('value');
        if ((isset($value['from']) && strlen($value['from']) > 0) ||
            (isset($value['to']) && strlen($value['to']) > 0) ||
            (isset($value['is_mapped']) && $value['is_mapped'] !== '')) {
            return $value;
        }

        return null;
    }

    //########################################
}
