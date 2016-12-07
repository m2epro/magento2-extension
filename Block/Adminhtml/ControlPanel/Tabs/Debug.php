<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Debug extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDebug');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/debug.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->isMagentoDevelopmentModeEnabled = $this->getHelper('Magento')->isDeveloper();
        $this->isDevelopmentModeEnabled        = $this->getHelper('Module')->isDevelopmentMode();
        $this->isMaintenanceEnabled            = $this->getHelper('Module\Maintenance\Debug')->isEnabled();

        $this->commands = $this->getHelper('View\ControlPanel\Command')
            ->parseDebugCommandsData(\Ess\M2ePro\Helper\View\ControlPanel\Command::CONTROLLER_DEBUG);

        // ---------------------------------------
        $url = $this->getUrl('*/controlPanel_debug/enableMaintenance/');
        $data = array(
            'label'   => $this->__('Enable'),
            'onclick' => 'setLocation(\'' . $url . '\');',
            'class'   => 'enable_maintenance'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('enable_maintenance',$buttonBlock);

        $url = $this->getUrl('*/controlPanel_debug/disableMaintenance/');
        $data = array(
            'label'   => $this->__('Disable'),
            'onclick' => 'setLocation(\'' . $url . '\');',
            'class'   => 'disable_maintenance'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('disable_maintenance',$buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/controlPanel_debug/enableDevelopmentMode/');
        $data = array(
            'label'   => $this->__('Enable'),
            'onclick' => 'setLocation(\'' . $url . '\');',
            'class'   => 'enable_development_mode'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('enable_development_mode',$buttonBlock);

        $url = $this->getUrl('*/controlPanel_debug/disableDevelopmentMode/');
        $data = array(
            'label'   => $this->__('Disable'),
            'onclick' => 'setLocation(\'' . $url . '\');',
            'class'   => 'disable_development_mode'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('disable_development_mode',$buttonBlock);
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}