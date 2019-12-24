<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates;

use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization
 */
class Synchronization extends AbstractModel
{
    /**
     * @var Runner
     */
    protected $runner = null;

    /**
     * @var Inspector
     */
    protected $inspector = null;

    //########################################

    /**
     * @return Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    // ---------------------------------------

    /**
     * @return Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    //########################################

    /**
     * @return null
     */
    protected function getNick()
    {
        return '/synchronization/';
    }

    /**
     * @return string
     */
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

        $this->runner = $this->modelFactory->getObject('Synchronization_Templates_Synchronization_Runner');
        $this->runner->setConnectorModel('Ebay_Connector_Item_Dispatcher');
        $this->runner->setMaxProductsPerStep(100);

        $this->runner->setLockItem($this->getActualLockItem());
        $this->runner->setPercentsStart($this->getPercentsStart() + $this->getPercentsInterval()/2);
        $this->runner->setPercentsEnd($this->getPercentsEnd());

        $this->inspector = $this->modelFactory->getObject('Ebay_Synchronization_Templates_Synchronization_Inspector');
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
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Apply Products changes on eBay');

        $this->getRunner()->execute();

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}
