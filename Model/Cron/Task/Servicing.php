<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

final class Servicing extends AbstractTask
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
        return $this->modelFactory->getObject('Servicing\Dispatcher')->process();
    }

    //########################################
}