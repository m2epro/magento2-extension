<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsModule;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;
use Ess\M2ePro\Helper\View\ControlPanel\Command;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsModule\Tabs
 */
class Tabs extends AbstractTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelToolsModuleTabs');
        // ---------------------------------------

        $this->setDestElementId('tools_module_tabs');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab(
            'module',
            [
                'label' => __('Module'),
                'title' => __('Module'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_MODULE
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'synchronization',
            [
                'label' => __('Synchronization'),
                'title' => __('Synchronization'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_SYNCHRONIZATION
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'integration',
            [
                'label' => __('Integration'),
                'title' => __('Integration'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_INTEGRATION
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'integration_ebay',
            [
                'label' => __('Integration [eBay]'),
                'title' => __('Integration [eBay]'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_INTEGRATION_EBAY
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'integration_amazon',
            [
                'label' => __('Integration [Amazon]'),
                'title' => __('Integration [Amazon]'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_INTEGRATION_AMAZON
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'integration_walmart',
            [
                'label' => __('Integration [Walmart]'),
                'title' => __('Integration [Walmart]'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_INTEGRATION_WALMART
                        ]
                    ]
                )->toHtml()
            ]
        );

        $this->addTab(
            'servicing',
            [
                'label' => __('Servicing'),
                'title' => __('Servicing'),
                'content' => $this->createBlock(
                    'ControlPanel_Tabs_Command_Group',
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_SERVICING
                        ]
                    ]
                )->toHtml()
            ]
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
