<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Cron
 */
class Cron extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelCron');
        $this->setTemplate('control_panel/tabs/cron.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $tasks = [];

        foreach ($this->modelFactory->getObject('Cron_Strategy_Serial')->getAllowedTasks() as $taskCode) {
            $optGroup = 'system';
            $titleParts = explode('/', $taskCode);
            if (in_array(reset($titleParts), $this->helperFactory->getObject('Component')->getComponents())) {
                $optGroup = array_shift($titleParts);
            }

            $index = array_search('cron', $titleParts, true);
            if ($index !== false) {
                unset($titleParts[$index]);
            }

            $taskTitle = preg_replace_callback(
                '/_([a-z])/i',
                function ($matches) {
                    return ucfirst($matches[1]);
                },
                implode(' > ', array_map('ucfirst', $titleParts))
            );

            $tasks[ucfirst($optGroup)][$taskCode] = $taskTitle;
        }

        foreach ($tasks as $group => &$tasksByGroup) {
            asort($tasksByGroup);
        }

        unset($tasksByGroup);
        $this->tasks = $tasks;

        return parent::_beforeToHtml();
    }

    //########################################
}
