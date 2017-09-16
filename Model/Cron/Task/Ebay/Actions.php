<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class Actions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/actions';
    const MAX_MEMORY_LIMIT = 512;

    //####################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //####################################

    protected function performActions()
    {
        $actionsProcessor = $this->modelFactory->getObject('Ebay\Actions\Processor');
        $actionsProcessor->setLockItem($this->getLockItem());
        $actionsProcessor->process();
    }

    //####################################
}