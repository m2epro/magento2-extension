<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

class Synchronization extends AbstractModel
{
    const NICK = 'synchronization';

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return \Ess\M2ePro\Model\Synchronization\Dispatcher::MAX_MEMORY_LIMIT;
    }

    //########################################

    protected function performActions()
    {
        /** @var $dispatcher \Ess\M2ePro\Model\Synchronization\Dispatcher */
        $dispatcher = $this->modelFactory->getObject('Synchronization\Dispatcher');

        $dispatcher->setParentLockItem($this->getLockItem());
        $dispatcher->setParentOperationHistory($this->getOperationHistory());

        $dispatcher->setAllowedComponents(array(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Helper\Component\Amazon::NICK
        ));

        $dispatcher->setAllowedTasksTypes(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::STOP_QUEUE,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS
        ));

        $dispatcher->setInitiator($this->getInitiator());
        $dispatcher->setParams(array());

        return $dispatcher->process();
    }

    //########################################
}