<?php

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Cron extends AbstractForm
{
    public array $tasks = [];

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelCron');
        $this->setTemplate('control_panel/tabs/cron.phtml');
    }

    protected function _beforeToHtml()
    {
        /** @var \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo */
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

    public function getRunAllUrl(): string
    {
        return $this->getTaskUrl('');
    }

    public function getTaskUrl(string $taskCode): string
    {
        return $this->getUrl(
            '*/controlPanel_cron/run',
            [
                '_query' => [
                    'task_code' => $taskCode,
                ],
            ]
        );
    }
}
