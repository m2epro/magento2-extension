<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

class Developer extends AbstractModel
{
    private $allowedTasks = NULL;

    //########################################

    protected function getNick()
    {
        return NULL;
    }

    protected function getInitiator()
    {
        return \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    protected function getStrategyObject()
    {
        /** @var \Ess\M2ePro\Model\Cron\Strategy\AbstractModel $strategyObject */
        $strategyObject = $this->modelFactory->getObject('Cron\Strategy\Serial');

        if (!empty($this->allowedTasks)) {
            $strategyObject->setAllowedTasks($this->allowedTasks);
        }

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

    protected function isPossibleToRun()
    {
        return true;
    }

    //########################################
}