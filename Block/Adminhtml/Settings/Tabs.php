<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    const TAB_ID_SYNCHRONIZATION   = 'synchronization';

    protected function _construct()
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
        return isset($this->_tabs[$id]) ? $this->_tabs[$id] : null;
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations([
            'Settings saved' => $this->__('Settings saved'),
            'Error' => $this->__('Error'),
        ]);
        $this->js->addRequireJs([
            's' => 'M2ePro/Settings'
        ], <<<JS

        window.SettingsObj = new Settings();
JS
        );

        return parent::_beforeToHtml();
    }
}
