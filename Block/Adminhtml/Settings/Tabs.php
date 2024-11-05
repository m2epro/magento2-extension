<?php

namespace Ess\M2ePro\Block\Adminhtml\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    public const TAB_ID_SYNCHRONIZATION = 'synchronization';

    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('configuration_settings_tabs');
        $this->setDestElementId('tabs_container');
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('settings.css');

        $this->setActiveTab($this->getData('active_tab'));

        return parent::_prepareLayout();
    }

    public function getActiveTabById($id)
    {
        return $this->_tabs[$id] ?? null;
    }

    protected function _beforeToHtml()
    {
        $this->js->addRequireJs(
            [
            's' => 'M2ePro/Settings',
            ],
            <<<JS

        window.SettingsObj = new Settings();
JS
        );

        return parent::_beforeToHtml();
    }
}
