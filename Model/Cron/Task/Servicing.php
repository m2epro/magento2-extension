<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

class Servicing extends AbstractModel
{
    const NICK = 'servicing';

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return \Ess\M2ePro\Model\Servicing\Dispatcher::MAX_MEMORY_LIMIT;
    }

    //########################################

    protected function performActions()
    {
        $dispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $dispatcher->setInitiator($this->getInitiator());
        $dispatcher->process();
    }

    //########################################
}