<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\Servicing;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\Servicing\Synchronize
 */
class Synchronize extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/servicing/synchronize';

    //########################################

    protected function performActions()
    {
        $dispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $dispatcher->setInitiator($this->getInitiator());
        $dispatcher->process();
    }

    //########################################
}
