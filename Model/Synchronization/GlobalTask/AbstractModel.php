<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

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
        return $path.trim(strtolower($this->getNick()),'/').'/';
    }

    //########################################
}