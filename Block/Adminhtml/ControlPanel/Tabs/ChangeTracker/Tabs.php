<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker;

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

        $this->setId('controlPanelChangeTrackerTabs');
        $this->setDestElementId('change_tracker_tabs');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab(
            'settings',
            [
                'label' => __('Settings'),
                'title' => __('Settings'),
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker\SettingsForm::class
                )->toHtml(),
            ]
        );

        $this->addTab(
            'statistic',
            [
                'label' => __('Executed Time'),
                'title' => __('Executed Time'),
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker\ExecutedTime::class
                )->toHtml(),
            ]
        );

        $this->addTab(
            'logs',
            [
                'label' => __('Logs'),
                'title' => __('Logs'),
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker\Logs::class
                )->toHtml(),
            ]
        );

        $this->initCss();

        return parent::_beforeToHtml();
    }

    /**
     * @return void
     */
    private function initCss(): void
    {
        $this->css->add(
            "
            .executed_time_table table {
                width: 100%;
                border: 1px solid #adadad;
                border-collapse: collapse;
                margin-bottom: 30px;
            }

            .executed_time_table td,
            .executed_time_table th {
                border: 1px solid #adadad;
                padding: 5px;
            }

            .executed_time_table td {
                text-align: center;
            }
            .log-context {
                width: 100%;
            }

            .log-box {
                border: 0.1rem dashed #d6d6d6;
                padding: 7px;
            }
            .odd {
                background-color: #f5f5f5;
            }

            .log-datetime {
                font-weight: 600;
            }

            .log-context {
                margin-top: 3px;
                border: 1px solid #d6d6d6;
                resize: vertical;
            }

            .log-level {
                font-weight: 600;
            }
        "
        );
    }
}
