<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Cron;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Cron\Run
 */
class Run extends \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main
{
    public function execute()
    {
        $taskCode = $this->getRequest()->getParam('task_code');

        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $taskCode && $cronRunner->setAllowedTasks([$taskCode]);

        $cronRunner->process();

        $this->getResponse()->setBody('<pre>' . $cronRunner->getOperationHistory()->getFullDataInfo() . '</pre>');
    }
}
