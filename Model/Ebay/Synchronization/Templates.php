<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization;

use Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Templates
 */
class Templates extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    /** @var Manager $productChangesManager */
    private $productChangesManager = null;

    //########################################

    protected function getProductChangesManager()
    {
        return $this->productChangesManager;
    }

    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES;
    }

    /**
     * @return null
     */
    protected function getNick()
    {
        return null;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function beforeStart()
    {
        parent::beforeStart();

        $this->productChangesManager = $this->modelFactory->getObject(
            'Synchronization_Templates_ProductChanges_Manager'
        );
        $this->productChangesManager->setComponent($this->getComponent());
        $this->productChangesManager->init();
    }

    protected function afterEnd()
    {
        parent::afterEnd();
        $this->getProductChangesManager()->clearCache();
    }

    // ---------------------------------------

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('Templates\RemoveUnused') ? false : $result;
        $result = !$this->processTask('Templates\Synchronization') ? false : $result;

        return $result;
    }

    protected function makeTask($taskPath)
    {
        $task = parent::makeTask($taskPath);
        $task->setProductChangesManager($this->getProductChangesManager());

        return $task;
    }

    //########################################
}
