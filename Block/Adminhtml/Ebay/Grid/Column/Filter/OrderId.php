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

    public function getHtml()
    {
        // TODO NOT SUPPORTED FEATURES
        if (!$this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
            return parent::getHtml();
        }
        $isInStorePickup = ($this->getValue('is_in_store_pickup') == 1) ? 'checked="checked"' : '';

        $html = '<div class="field-100"><input type="text" name="'.$this->_getHtmlName().'[value]"
                 id="'.$this->_getHtmlId().'" value="'.$this->getEscapedValue('value').'"
                 class="input-text no-changes"/></div>';

        return $html .
            '<span class="label">' .
                 '</span><input style="margin-left:1px;float:none;width:auto !important;" type="checkbox"
                 value="1" name="' . $this->_getHtmlName() . '[is_in_store_pickup]" ' . $isInStorePickup . '> '
                 .$this->getHelper('Module\Translation')->__('In-Store Pickup');
    }

    //########################################

    public function getValue($index=null)
    {
        // TODO NOT SUPPORTED FEATURES
        if (!$this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
            return $this->getData('value', $index);
        }

        if ($index) {
            return $this->getData('value', $index);
        }

        $value = $this->getData('value');
        if (isset($value['is_in_store_pickup']) && $value['is_in_store_pickup'] == 1) {
            return $value;
        }

        return null;
    }

    //########################################
}
