<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Button;

class ProductTypeSwitchButton extends \Magento\Backend\Block\Widget
{
    protected function _construct()
    {
        if (!$this->hasTemplate()) {
            $this->setTemplate('Ess_M2ePro::productTypeToggle.phtml');
        }
        parent::_construct();
    }

    public function getLabel(): string
    {
        return $this->getData('label') ?? '';
    }

    public function getValue(): int
    {
        return $this->getData('value')
            ?? \Ess\M2ePro\Model\Amazon\Template\ProductType::VIEW_MODE_REQUIRED_ATTRIBUTES;
    }

    public function getStatus(): string
    {
        if ($this->getValue() === \Ess\M2ePro\Model\Amazon\Template\ProductType::VIEW_MODE_REQUIRED_ATTRIBUTES) {
            return 'checked';
        }

        return '';
    }
}
