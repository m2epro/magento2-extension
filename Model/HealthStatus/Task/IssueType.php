<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\IssueType
 */
abstract class IssueType extends AbstractModel
{
    const TYPE = 'issue';

    //########################################

    public function getType()
    {
        return self::TYPE;
    }

    public function mustBeShownIfSuccess()
    {
        return false;
    }

    //########################################
}
