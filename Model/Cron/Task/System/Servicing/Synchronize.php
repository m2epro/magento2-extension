<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\Servicing;

class Synchronize extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'system/servicing/synchronize';

    // ----------------------------------------

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function performActions(): void
    {
        $dispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $dispatcher->process();
    }
}
