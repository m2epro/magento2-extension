<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task;

/**
 * Class InfoType
 * @package Ess\M2ePro\Model\HealthStatus\Task
 */
abstract class InfoType extends AbstractModel
{
    const TYPE = 'info';

    //########################################

    public function getType()
    {
        return self::TYPE;
    }

    //########################################
}
