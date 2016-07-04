<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    const TAB_ID_SYNCHRONIZATION   = 'synchronization';
    const TAB_ID_INTERFACE         = 'interface';
    const TAB_ID_MAGENTO_INVENTORY = 'magento_inventory';
    const TAB_ID_LOGS_CLEARING     = 'logs_clearing';
    const TAB_ID_LICENSE           = 'license';

    //########################################

    protected function _construct()
    {
        parent::_construct();
        $this->setId('configuration_settings_tabs');
        $this->setDestElementId('tabs_container');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('settings.css');

        // ---------------------------------------

        $tab = array(
            'label' => $this->__('Interface'),
            'title' => $this->__('Interface'),
            'content' => $this->createBlock('Settings\Tabs\InterfaceTab')->toHtml()
        );

        $this->addTab(self::TAB_ID_INTERFACE, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = array(
            'label' => $this->__('Magento Inventory'),
            'title' => $this->__('Magento Inventory'),
            'content' => $this->createBlock('Settings\Tabs\MagentoInventory')->toHtml()
        );

        $this->addTab(self::TAB_ID_MAGENTO_INVENTORY, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = array(
            'label' => $this->__('Logs Clearing'),
            'title' => $this->__('Logs Clearing'),
            'content' => $this->createBlock('Settings\Tabs\LogsClearing')->toHtml()
        );

        $this->addTab(self::TAB_ID_LOGS_CLEARING, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = array(
            'label' => $this->__('License'),
            'title' => $this->__('License'),
            'content' => $this->createBlock('Settings\Tabs\License')->toHtml()
        );

        $this->addTab(self::TAB_ID_LICENSE, $tab);

        // ---------------------------------------

        $this->setActiveTab($this->getData('active_tab'));

        return parent::_prepareLayout();
    }

    //########################################

    public function getActiveTabById($id)
    {
        return isset($this->_tabs[$id]) ? $this->_tabs[$id] : NULL;
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/*/index', ['active_tab' => self::TAB_ID_LICENSE]), 'licenseTab');
        $this->jsTranslator->addTranslations([
            'Settings successfully saved' => $this->__('Settings successfully saved'),
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

    //########################################
}