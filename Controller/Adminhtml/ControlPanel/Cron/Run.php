<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Cron;

class Run extends \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main
{
    private \Ess\M2ePro\Model\Cron\Runner\Developer $cronRunner;

    public function __construct(
        \Ess\M2ePro\Model\Cron\Runner\Developer $cronRunner,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->cronRunner = $cronRunner;
    }

    public function execute()
    {
        $taskCodes = array_filter($this->getRequest()->getParam('task_codes', []));

        if (!empty($taskCodes)) {
            $this->cronRunner->setAllowedTasks($taskCodes);
        }

        $this->cronRunner->process();

        $this
            ->getResponse()
            ->setBody($this->cronRunner->getOperationHistory()->getFullDataInfo());
    }
}
