<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\General;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\General\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Amazon\Synchronization\AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('General\\'.$taskPath);
    }

    //########################################
}
