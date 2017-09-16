<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates;

class Synchronization extends \Ess\M2ePro\Model\Amazon\Synchronization\Templates\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner
     */
    private $runner = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\Inspector
     */
    private $inspector = NULL;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    //########################################

    protected function getNick()
    {
        return NULL;
    }

    protected function getTitle()
    {
        return 'Inventory';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 20;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function beforeStart()
    {
        parent::beforeStart();

        $this->runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');

        $this->runner->setConnectorModel('Amazon\Connector\Product\Dispatcher');
        $this->runner->setMaxProductsPerStep(100);

        $this->runner->setLockItem($this->getActualLockItem());
        $this->runner->setPercentsStart($this->getPercentsStart() + $this->getPercentsInterval()/2);
        $this->runner->setPercentsEnd($this->getPercentsEnd());

        $this->inspector = $this->modelFactory->getObject('Amazon\Synchronization\Templates\Synchronization\Inspector');
    }

    protected function afterEnd()
    {
        $this->executeRunner();
        parent::afterEnd();
    }

    // ---------------------------------------

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('Synchronization\ListActions') ? false : $result;
        $result = !$this->processTask('Synchronization\Relist') ? false : $result;
        $result = !$this->processTask('Synchronization\Stop') ? false : $result;
        $result = !$this->processTask('Synchronization\Revise') ? false : $result;

        return $result;
    }

    protected function makeTask($taskPath)
    {
        $task = parent::makeTask($taskPath);

        $task->setRunner($this->getRunner());
        $task->setInspector($this->getInspector());
        $task->setProductChangesManager($this->getProductChangesManager());

        return $task;
    }

    //########################################

    private function executeRunner()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Apply Products changes on Amazon');

        $this->getRunner()->execute();

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}