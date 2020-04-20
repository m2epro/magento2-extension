<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter;

use Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price
 */
class Price extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Range
{
    //########################################

    public function getHtml()
    {
        $anySelected = $noSelected = $yesSelected = '';
        $filterValue = (string)$this->getValue('is_repricing');

        $filterValue === '' && $anySelected = ' selected="selected" ';
        $filterValue === '0' && $noSelected  = ' selected="selected" ';
        $filterValue === '1' && $yesSelected = ' selected="selected" ';

        $isEnabled  = AmazonListingProduct::IS_REPRICING_YES;
        $isDisabled = AmazonListingProduct::IS_REPRICING_NO;

        $html = <<<HTML
<div class="range" style="width: 145px;">
    <div class="range-line" style="padding-top: 5px">
        <label for="{$this->_getHtmlName()}"
               style="cursor: pointer;vertical-align: text-bottom;"
               class="admin__field-label">
            {$this->__('On Repricing')}
        </label>
        <select id="{$this->_getHtmlName()}"
                class="admin__control-select"
                style="margin-left:6px; float:none; width:auto !important;"
                name="{$this->_getHtmlName()}[is_repricing]"
            >
            <option {$anySelected} value="">{$this->__('Any')}</option>
            <option {$yesSelected} value="{$isEnabled}">{$this->__('Yes')}</option>
            <option {$noSelected}  value="{$isDisabled}">{$this->__('No')}</option>
        </select>
        <label style="vertical-align: text-bottom;" class="admin__field-label"></label>
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
        if ((isset($value['from']) && $value['from'] !== '') ||
            (isset($value['to']) && $value['to'] !== '') ||
            (isset($value['is_repricing']) && $value['is_repricing'] !== '')) {
            return $value;
        }
        return null;
    }

    //########################################
}
