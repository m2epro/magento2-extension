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
        $taskRepo = $this->modelFactory->getObject('Cron_Task_Repository');

        $tasks = [];
        foreach ($taskRepo->getRegisteredTasks() as $taskNick) {
            $group = $taskRepo->getTaskGroup($taskNick);
            $titleParts = explode('/', $taskNick);
            reset($titleParts) === $group && array_shift($titleParts);

            $taskTitle = preg_replace_callback(
                '/_([a-z])/i',
                function ($matches) {
                    return ucfirst($matches[1]);
                },
                implode(' > ', array_map('ucfirst', $titleParts))
            );

            $tasks[ucfirst($group)][$taskNick] = $taskTitle;
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
