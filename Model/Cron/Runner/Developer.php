<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\Developer
 */
class Developer extends AbstractModel
{
    private $allowedTasks = null;

    //########################################

    public function getNick()
    {
        return null;
    }

    public function getInitiator()
    {
        return \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER;
    }

    //########################################

    public function process()
    {
        // @codingStandardsIgnoreLine
        session_write_close();
        parent::process();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    protected function getStrategyObject()
    {
        $tasks = $this->allowedTasks;
        empty($tasks) && $tasks = $this->modelFactory->getObject('Cron_Task_Repository')->getRegisteredTasks();

        $strategyObject = $this->modelFactory->getObject('Cron_Strategy_Serial');
        $strategyObject->setAllowedTasks($tasks);

        return $strategyObject;
    }

    //########################################

    /**
     * @param array $tasks
     * @return $this
     */
    public function setAllowedTasks(array $tasks)
    {
        $this->allowedTasks = $tasks;
        return $this;
    }

    //########################################

    protected function isPossibleToRun()
    {
        return true;
    }

    protected function canProcessRunner()
    {
        return true;
    }

    //########################################

    protected function updateLastRun()
    {
        return null;
    }

    protected function updateLastAccess()
    {
        return null;
    }

    //########################################
}
