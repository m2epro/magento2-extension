<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Synchronization\Templates\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner
     */
    protected $runner = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization\Inspector
     */
    protected $inspector = NULL;

    /**
     * @var \Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager
     */
    protected $productChangesManager = NULL;

    //########################################

    protected function processTask($taskPath)
    {
        return parent::processTask('Synchronization\\'.$taskPath);
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner $object
     */
    public function setRunner(\Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner $object)
    {
        $this->runner = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization\Inspector $object
     */
    public function setInspector(\Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization\Inspector $object)
    {
        $this->inspector = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization\Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager $object
     */
    public function setProductChangesManager(\Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager $object)
    {
        $this->productChangesManager = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager
     */
    public function getProductChangesManager()
    {
        return $this->productChangesManager;
    }

    //########################################
}