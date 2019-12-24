<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    public function mustBeShownIfSuccess()
    {
        return true;
    }

    //########################################

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return Result
     */
    abstract public function process();

    //########################################
}
