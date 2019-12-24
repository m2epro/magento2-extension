<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    /** @var Processor $processor */
    private $processor = null;

    //########################################

    public function getProcessor()
    {
        return $this->processor;
    }

    public function setProcessor($processor)
    {
        $this->processor = $processor;
        return $this;
    }

    //########################################

    public function process()
    {
        $this->validate();

        $this->check();
        $this->execute();
    }

    //########################################

    protected function validate()
    {
        if ($this->getProcessor() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Processor was not set.');
        }
    }

    // ---------------------------------------

    abstract protected function check();

    abstract protected function execute();

    //########################################
}
