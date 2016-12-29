<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    )
    {
        $this->helperFactory = $helperFactory;

        parent::__construct($context, $resourceHelper, $data);
    }

    //########################################

    public function getHtml()
    {
        $duplicateWord = $this->helperFactory->getObject('Module\Translation')->__('Duplicates');

        $value = $this->getValue();
        $isChecked = (!empty($value['is_duplicate']) && $value['is_duplicate'] == 1) ? 'checked="checked"' : '';

        return parent::getHtml() . <<<HTML
<div style="margin-top: 10px">
    <input id="{$this->_getHtmlId()}_checkbox"
           type="checkbox"
           value="1"
           name="{$this->getColumn()->getId()}[is_duplicate]"
           {$isChecked}
           class="admin__control-checkbox"
    >
    <label for="{$this->_getHtmlId()}_checkbox" class="admin__field-label">
        <span>{$duplicateWord}</span>
    </label>
</div>
HTML;
    }

    //########################################

    public function getValue($index = null)
    {
        if ($index) {
            return $this->getData('value', $index);
        }

        $value = $this->getData('value');

        if ((isset($value['value']) && !is_null($value['value'])) ||
            (isset($value['is_duplicate']) && $value['is_duplicate'] == 1)) {
            return $value;
        }

        return null;
    }

    //########################################

    protected function _renderOption($option, $value)
    {
        $value = isset($value['value']) ? $value['value'] : null;
        return parent::_renderOption($option, $value);
    }

    protected function _getHtmlName()
    {
        return "{$this->getColumn()->getId()}[value]";
    }

    //########################################
}