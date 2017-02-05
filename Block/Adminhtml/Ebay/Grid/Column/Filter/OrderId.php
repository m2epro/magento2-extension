<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter;

class OrderId extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Text
{
    //########################################

    protected $helperFactory;

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

    public function getHelper($helper, array $arguments = [])
    {
        return $this->helperFactory->getObject($helper, $arguments);
    }

    //########################################

    public function getValue($index = null)
    {
        if (is_null($index)) {
            $value = $this->getData('value');
            return is_array($value) ? $value : array('value' => $value);
        }

        return $this->getData('value', $index);
    }

    public function getEscapedValue($index = null)
    {
        $value = $this->getValue($index);
        if (is_null($index)) {
            $value = $value['value'];
        }

        return $this->escapeHtml($value);
    }

    //########################################

    public function getHtml()
    {
        if (!$this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
            return parent::getHtml();
        }

        $html = '<input type="text" name="' .
            $this->_getHtmlName() .
            '[value]" id="' .
            $this->_getHtmlId() .
            '" value="' .
            $this->getEscapedValue('value') .
            '" class="input-text admin__control-text no-changes"' .
            $this->getUiId(
                'filter',
                $this->_getHtmlName()
            ) . ' />';

        return $html . $this->renderCheckboxHtml();
    }

    private function renderCheckboxHtml()
    {
        $isInStorePickup = ($this->getValue('is_in_store_pickup') == 1) ? 'checked="checked"' : '';

        return <<<HTML
        <div style="padding: 5px 0; text-align: right; font-weight: normal; position: relative;">
            <label for="{$this->_getHtmlId()}_checkbox"
                   style="width: 60%; text-align: right; display: inline-block; margin-right: 50%;">
                {$this->getHelper('Module\Translation')->translate(['In-Store Pickup'])}
            </label>
            <div style="display: inline-block; position: absolute; top: 1em; right: 0;">
                <input name="{$this->_getHtmlName()}[is_in_store_pickup]"
                       id="{$this->_getHtmlId()}_checkbox"
                       value="1" class="admin__control-checkbox"
                       type="checkbox" {$isInStorePickup}>
                <label style="margin: 0 0 -4px 2px;" class="addafter" for="{$this->_getHtmlId()}_checkbox">
                    <label for="{$this->_getHtmlId()}_checkbox"></label>
                </label>
            </div>
        </div>
HTML;
    }

    // ########################################
}
