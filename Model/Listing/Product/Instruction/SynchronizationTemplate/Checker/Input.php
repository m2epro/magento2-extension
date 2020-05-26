<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\Input
 */
class Input extends \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input
{
    /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction */
    protected $scheduledAction = null;

    //########################################

    public function setScheduledAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction)
    {
        $this->scheduledAction = $scheduledAction;
        return $this;
    }

    public function getScheduledAction()
    {
        return $this->scheduledAction;
    }

    //########################################
}
