<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

class Templates extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager $productChangesManager */
    private $productChangesManager = NULL;

    //########################################

    protected function getProductChangesManager()
    {
        return $this->productChangesManager;
    }

    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES;
    }

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
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function beforeStart()
    {
        parent::beforeStart();

        $this->productChangesManager = $this->modelFactory->getObject(
            'Synchronization\Templates\ProductChanges\Manager'
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

        $result = !$this->processTask('Templates\Repricing') ? false : $result;
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