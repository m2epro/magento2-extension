<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Price
 */
class Price extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Price
{
    /**
     * Overridden due to necessity to add class to the `select` element
     * @return string
     */
    protected function _getCurrencySelectHtml()
    {
        $value = $this->getEscapedValue('currency');
        if (!$value) {
            $value = $this->_getColumnCurrencyCode();
        }

        $html = '';
        $html .= '<select class="admin__control-select no-changes"
                          name="' . $this->_getHtmlName() . '[currency]"
                          id="' . $this->_getHtmlId() . '_currency">';
        foreach ($this->_getCurrencyList() as $currency) {
            $html .= '<option value="' . $currency . '" ' . ($currency ==
                $value ? 'selected="selected"' : '') . '>' . $currency . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}
