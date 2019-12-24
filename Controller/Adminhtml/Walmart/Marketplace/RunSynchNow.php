<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace\RunSynchNow
 */
class RunSynchNow extends Marketplace
{
    //########################################

    public function execute()
    {
        session_write_close();

        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        $marketplaceObj = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        /** @var $dispatcher \Ess\M2ePro\Model\Synchronization\Dispatcher */
        $dispatcher = $this->modelFactory->getObject('Synchronization\Dispatcher');

        $dispatcher->setAllowedComponents([$marketplaceObj->getComponentMode()]);
        $dispatcher->setAllowedTasksTypes([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES
        ]);

        $dispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $dispatcher->setParams(['marketplace_id' => $marketplaceId]);

        $dispatcher->process();
    }

    //########################################
}
