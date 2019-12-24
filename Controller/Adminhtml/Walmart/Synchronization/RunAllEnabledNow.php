<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization\RunAllEnabledNow
 */
class RunAllEnabledNow extends Settings
{
    //########################################

    public function execute()
    {
        session_write_close();

        /** @var $dispatcher \Ess\M2ePro\Model\Synchronization\Dispatcher */
        $dispatcher = $this->modelFactory->getObject('Synchronization\Dispatcher');

        $dispatcher->setAllowedComponents([\Ess\M2ePro\Helper\Component\Walmart::NICK]);

        $dispatcher->setAllowedTasksTypes([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::STOP_QUEUE,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS
        ]);

        $dispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $dispatcher->setParams([]);

        $dispatcher->process();
    }

    //########################################
}
