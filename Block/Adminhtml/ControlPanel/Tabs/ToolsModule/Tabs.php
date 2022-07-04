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

        $this->setId('controlPanelToolsModuleTabs');
        $this->setDestElementId('tools_module_tabs');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab(
            'magento',
            [
                'label' => __('Magento'),
                'title' => __('Magento'),
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group::class,
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
            'integration',
            [
                'label' => __('Integration'),
                'title' => __('Integration'),
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group::class,
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
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group::class,
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
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group::class,
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
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group::class,
                    '',
                    [
                        'data' => [
                            'controller_name' => Command::CONTROLLER_MODULE_INTEGRATION_WALMART
                        ]
                    ]
                )->toHtml()
            ]
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
