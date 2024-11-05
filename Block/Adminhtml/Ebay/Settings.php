<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

class Settings extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected function _construct(): void
    {
        parent::_construct();

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addButton('save', [
            'label' => $this->__('Save'),
            'onclick' => 'EbaySettingsObj.saveSettings()',
            'class' => 'primary',
        ]);
    }

    protected function _toHtml()
    {
        return parent::_toHtml() . '<div id="tabs_container"></div>';
    }
}
