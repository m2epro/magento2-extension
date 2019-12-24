<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

/**
 * Class \Ess\M2ePro\Model\Synchronization\GlobalTask\Launcher
 */
class Launcher extends \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal
{
    //########################################

    protected function getType()
    {
        return null;
    }

    protected function getNick()
    {
        return null;
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('GlobalTask\Processing') ? false : $result;
        $result = !$this->processTask('GlobalTask\MagentoProducts') ? false : $result;
        $result = !$this->processTask('GlobalTask\StopQueue') ? false : $result;

        return $result;
    }

    //########################################

    protected function getFullSettingsPath()
    {
        return '/global/';
    }

    //########################################
}
