<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

/**
 * Class \Ess\M2ePro\Model\Synchronization\GlobalTask\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal
{
    //########################################

    protected function processTask($taskPath)
    {
        return parent::processTask('GlobalTask\\'.$taskPath);
    }

    //########################################

    protected function getFullSettingsPath()
    {
        $path = '/global/';
        $path .= $this->getType() ? strtolower($this->getType()).'/' : '';
        return $path.trim(strtolower($this->getNick()), '/').'/';
    }

    //########################################
}
