<?php

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Cron extends AbstractForm
{
    private array $tasks;
    private \Ess\M2ePro\Model\Cron\Task\Repository $taskRepository;

    public function __construct(
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->taskRepository = $taskRepository;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelCron');
        $this->setTemplate('control_panel/tabs/cron.phtml');
        $this->css->addFile('controlPanel/cronTab.css');
    }

    public function getModuleIdentifier(): string
    {
        return \Ess\M2ePro\Helper\Module::IDENTIFIER;
    }

    public function getCronRunUrl(): string
    {
        return $this->getUrl('*/controlPanel_cron/run');
    }

    public function getTasks(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->tasks)) {
            $tasks = [];
            $extensionTasks = $this->getExtensionTasks();
            foreach ($extensionTasks as $task) {
                $group = $task->group;
                $nick = $task->nick;
                $tasks[ucfirst($group)][$task->code] = $this->generateTaskTitle($group, $nick);
            }

            foreach ($tasks as &$tasksByGroup) {
                asort($tasksByGroup);
            }

            unset($tasksByGroup);
            $this->tasks = $tasks;
        }

        return $this->tasks;
    }

    private function getExtensionTasks(): array
    {
        $tasks = [];
        foreach ($this->taskRepository->getRegisteredTasks() as $taskNick) {
            $tasks[] = (object)[
                'group' => $this->taskRepository->getTaskGroup($taskNick),
                'nick' => $taskNick,
                'code' => $taskNick,
            ];
        }

        return $tasks;
    }

    private function generateTaskTitle(string $group, string $nick): string
    {
        $titleParts = explode('/', $nick);

        if (reset($titleParts) === $group) {
            array_shift($titleParts);
        }

        return preg_replace_callback(
            '/_([a-z])/i',
            static fn($matches) => ucfirst($matches[1]),
            implode(' > ', array_map('ucfirst', $titleParts))
        );
    }
}
