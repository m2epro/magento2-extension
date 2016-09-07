<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsGeneral;

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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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
                'content' => $this->getLayout()->createBlock(
                    '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group',
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