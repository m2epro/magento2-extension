<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsGeneral;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;
use Ess\M2ePro\Helper\View\ControlPanel\Command;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsGeneral\Tabs
 */
class Tabs extends AbstractTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelToolsGeneralTabs');
        // ---------------------------------------

        $this->setDestElementId('tools_general_tabs');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab(
            'general',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_TOOLS_M2EPRO_GENERAL
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'install',
            [
                'label' => __('Install'),
                'title' => __('Install'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_TOOLS_M2EPRO_INSTALL
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'magento',
            [
                'label' => __('Magento'),
                'title' => __('Magento'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_TOOLS_MAGENTO
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'additional',
            [
                'label' => __('Additional'),
                'title' => __('Additional'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_TOOLS_ADDITIONAL
                        ]
                    ]
                )->toHtml()
            ]
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
