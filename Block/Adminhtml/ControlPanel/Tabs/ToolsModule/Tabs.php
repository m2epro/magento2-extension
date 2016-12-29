<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsModule;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;
use Ess\M2ePro\Helper\View\ControlPanel\Command;

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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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
            'servicing',
            [
                'label' => __('Servicing'),
                'title' => __('Servicing'),
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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