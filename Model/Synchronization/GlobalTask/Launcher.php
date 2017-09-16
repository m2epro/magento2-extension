<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

class Launcher extends \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal
{
    //########################################

    protected function getType()
    {
        return NULL;
    }

    protected function getNick()
    {
        return NULL;
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