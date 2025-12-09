<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Cron;

class Run extends \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main
{
    public function execute()
    {
        $cronRunner = $this->modelFactory
            ->getObjectByClass(\Ess\M2ePro\Model\Cron\Runner\Developer::class);

        $taskCode = $this->getRequest()->getParam('task_code');
        if (!empty($taskCode)) {
            $cronRunner->setAllowedTasks([$taskCode]);
        }

        $cronRunner->process();

        $cronRunInfo = sprintf(
            '<pre>%s</pre>',
            $cronRunner->getOperationHistory()->getFullDataInfo()
        );

        $this->getResponse()->setBody($cronRunInfo);
    }
}
