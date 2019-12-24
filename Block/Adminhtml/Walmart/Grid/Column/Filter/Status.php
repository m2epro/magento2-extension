<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter\Status
 */
class Status extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    ) {
        $this->helperFactory = $helperFactory;

        parent::__construct($context, $resourceHelper, $data);
    }

    //########################################

    public function getHtml()
    {
        $value = $this->getValue();
        $isResetChecked = !empty($value['is_reset']) ? 'checked="checked"' : '';

        $html = <<<HTML
<div class="range">
    <div class="range-line" style="width: auto; padding-top: 5px;">
        <input id="{$this->_getHtmlId()}_checkbox"
               type="checkbox"
               value="1"
               name="{$this->getColumn()->getId()}[is_reset]"
               {$isResetChecked}
               class="admin__control-checkbox">
        <label for="{$this->_getHtmlId()}_checkbox" class="admin__field-label">
            <span>{$this->helperFactory->getObject('Module\Translation')->__('Can be fixed')}</span>
        </label>
    </div>
</div>
HTML;

        return parent::getHtml() . $html;
    }

    //########################################

    public function getValue()
    {
        $value = $this->getData('value');

        if (is_array($value) &&
            (isset($value['value']) && $value['value'] !== null) ||
            (isset($value['is_reset']) && $value['is_reset'] == 1)) {
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
