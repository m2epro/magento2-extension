<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements\Checks;

/**
 * Class ExecutionTime
 * @package Ess\M2ePro\Model\Requirements\Checks
 */
class ExecutionTime extends AbstractCheck
{
    //########################################

    public function isMeet()
    {
        if ($this->getReal() <= 0) {
            return true;
        }

        return $this->getReal() >= $this->getMin();
    }

    //########################################

    public function getMin()
    {
        return $this->getReader()->getExecutionTimeData('min');
    }

    public function getReal()
    {
        return $this->getHelper('Client')->getExecutionTime();
    }

    //########################################
}
